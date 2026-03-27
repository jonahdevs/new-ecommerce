<?php

namespace App\Services\Sap;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Models\SapSyncLog;
use App\Services\Sap\ValueObjects\CuNumberResult;
use App\Services\Sap\ValueObjects\SapSyncResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SapIntegrationService
{
    private ?string $sessionId = null;
    private ?\Carbon\Carbon $sessionExpiresAt = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $companyDb,
        private readonly string $username,
        private readonly string $password,
        private readonly int $sessionTimeoutMinutes,
    ) {}

    // ================================================================
    // Authentication
    // SAP Service Layer uses cookie-based sessions. We authenticate
    // once and reuse the session until it expires, then re-auth.
    // ================================================================

    public function authenticate(): void
    {
        $start = microtime(true);

        $response = Http::withOptions(['verify' => false])
            ->post("{$this->baseUrl}/Login", [
                'CompanyDB' => $this->companyDb,
                'UserName'  => $this->username,
                'Password'  => $this->password,
            ]);

        $durationMs = (int) ((microtime(true) - $start) * 1000);

        if (!$response->successful()) {
            Log::error('SAP authentication failed', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'duration' => $durationMs,
            ]);
            throw new SapApiException(
                'SAP authentication failed: ' . $response->body(),
                $response->status(),
                endpoint: '/Login',
            );
        }

        // SAP returns the session cookie in the response headers.
        // We extract and store it for use on all subsequent requests.
        $cookie = $response->cookies()->getCookieByName('B1SESSION');

        if (!$cookie) {
            throw new SapApiException(
                'SAP login succeeded but no B1SESSION cookie returned.',
                $response->status(),
                endpoint: '/Login',
            );
        }

        $this->sessionId = $cookie->getValue();
        $this->sessionExpiresAt = now()->addMinutes($this->sessionTimeoutMinutes);

        Log::info('SAP session established', ['expires_at' => $this->sessionExpiresAt]);
    }

    public function isSessionValid(): bool
    {
        return $this->sessionId !== null
            && $this->sessionExpiresAt !== null
            && $this->sessionExpiresAt->isFuture();
    }

    // ================================================================
    // Core order sync flow
    // Three sequential SAP API calls, each returning a SapSyncResult
    // with the document number for use in the next step.
    // ================================================================

    /**
     * Step 1 — Create a Sales Order in SAP.
     */
    public function syncOrder(Order $order): SapSyncResult
    {
        $payload = $this->mapOrderToSapPayload($order);

        return $this->post(
            order: $order,
            operation: 'create_order',
            endpoint: '/Orders',
            payload: $payload,
        );
    }

    /**
     * Step 2 — Create an A/R Invoice in SAP linked to the Sales Order.
     *
     * @param string $sapOrderNumber The DocNum returned from syncOrder()
     */
    public function createInvoice(Order $order, string $sapOrderNumber): SapSyncResult
    {
        $payload = $this->mapOrderToSapPayload($order);

        // The invoice references the sales order via NumAtCard (our order reference).
        // SAP links them internally via that field.
        $payload['Comments'] = "Invoice for e-commerce order {$order->reference}";

        return $this->post(
            order: $order,
            operation: 'create_invoice',
            endpoint: '/Invoices',
            payload: $payload,
        );
    }

    /**
     * Step 3 — Record an Incoming Payment in SAP, linked to the invoice.
     *
     * @param string $sapInvoiceNumber The DocNum returned from createInvoice()
     */
    public function createIncomingPayment(Order $order, string $sapInvoiceNumber): SapSyncResult
    {
        $payment = $order->payment;
        $payload = [
            'CardCode'  => $this->resolveCardCode($order),
            'DocDate'   => now()->toDateString(),
            'CashSum'   => $this->convertCentsToCurrency($order->total_cents),
            'Remarks'   => "Payment for {$order->reference} via {$payment?->gateway}",
            'PaymentInvoices' => [
                [
                    'DocEntry'    => $sapInvoiceNumber,
                    'SumApplied'  => $this->convertCentsToCurrency($order->total_cents),
                    'InvoiceType' => 'it_Invoice',
                ],
            ],
            'PaymentMeans'      => $this->mapPaymentGateway($payment?->gateway),
            'TransferReference' => $payment?->gateway_transaction_id,
        ];

        return $this->post(
            order: $order,
            operation: 'create_payment',
            endpoint: '/IncomingPayments',
            payload: $payload,
        );
    }

    /**
     * Fallback CU number poll — called by PollSapCuNumberJob when the
     * webhook hasn't arrived. Returns null if the CU number isn't ready yet.
     */
    public function pollCuNumber(string $sapInvoiceNumber): ?CuNumberResult
    {
        $this->ensureAuthenticated();

        $start = microtime(true);
        $response = $this->client()
            ->get("{$this->baseUrl}/Invoices({$sapInvoiceNumber})");
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        // SAP stores the CU number in a custom user-defined field.
        // Adjust the field name to match your SAP configuration.
        $cuNumber = $data['U_KRA_CUNumber'] ?? null;

        if (!$cuNumber) {
            return null;
        }

        return new CuNumberResult(
            cuNumber: $cuNumber,
            kraInvoiceNumber: $data['U_KRA_InvoiceNumber'] ?? null,
            validatedAt: isset($data['U_KRA_ValidatedAt'])
                ? \Carbon\Carbon::parse($data['U_KRA_ValidatedAt'])
                : now(),
        );
    }

    // ================================================================
    // Data mapping
    // ================================================================

    /**
     * Maps an Order and its items to the SAP Sales Order / Invoice payload.
     * This is the central transformation — used for both /Orders and /Invoices.
     */
    public function mapOrderToSapPayload(Order $order): array
    {
        $order->loadMissing('items.product');

        return [
            'CardCode'    => $this->resolveCardCode($order),
            'DocDate'     => now()->toDateString(),
            'DocDueDate'  => now()->toDateString(),
            'NumAtCard'   => $order->reference,
            'Comments'    => "E-commerce order {$order->reference}",
            'DocumentLines' => $order->items->map(function ($item) {
                $sku = $item->product_snapshot['sku']
                    ?? $item->product?->sku
                    ?? 'UNKNOWN';

                return [
                    'ItemCode'      => $sku,
                    'Quantity'      => $item->quantity,
                    'UnitPrice'     => $this->convertCentsToCurrency($item->unit_price_cents),
                    'TaxCode'       => config('sap.default_tax_code', 'VAT16'),
                    'WarehouseCode' => config('sap.warehouse_code', '01'),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Converts an integer cent value to a decimal currency amount.
     * E.g. 150000 cents → 1500.00 KES
     *
     * Stored as cents throughout the e-commerce system to avoid
     * floating-point errors; SAP expects decimal amounts.
     */
    public function convertCentsToCurrency(int $cents): float
    {
        return round($cents / 100, 2);
    }

    // ================================================================
    // Internal helpers
    // ================================================================

    /**
     * Ensures we have a live SAP session before making any API call.
     * Re-authenticates transparently if the session has expired.
     */
    private function ensureAuthenticated(): void
    {
        if (!$this->isSessionValid()) {
            $this->authenticate();
        }
    }

    /**
     * Returns an HTTP client pre-configured with the SAP session cookie.
     */
    private function client(): PendingRequest
    {
        return Http::withOptions(['verify' => false])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Cookie'       => "B1SESSION={$this->sessionId}",
            ]);
    }

    /**
     * Central POST helper used by all three SAP write operations.
     *
     * Handles:
     *  - Session renewal on 401
     *  - Audit logging via sap_sync_logs
     *  - SapApiException on non-2xx responses
     */
    private function post(
        Order $order,
        string $operation,
        string $endpoint,
        array $payload,
    ): SapSyncResult {
        $this->ensureAuthenticated();

        $start = microtime(true);
        $response = $this->client()->post("{$this->baseUrl}{$endpoint}", $payload);
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        // Transparent session renewal on 401
        if ($response->status() === 401) {
            $this->authenticate();
            $start = microtime(true);
            $response = $this->client()->post("{$this->baseUrl}{$endpoint}", $payload);
            $durationMs = (int) ((microtime(true) - $start) * 1000);
        }

        $responseData = $response->json();
        $success = $response->successful();

        // Always write an audit log row — success or failure
        SapSyncLog::create([
            'order_id'            => $order->id,
            'operation'           => $operation,
            'status'              => $success ? 'success' : 'failed',
            'endpoint'            => $endpoint,
            'http_method'         => 'POST',
            'request_payload'     => $payload,
            'response_payload'    => $responseData,
            'http_status_code'    => $response->status(),
            'error_message'       => $success ? null : ($responseData['error']['message']['value'] ?? $response->body()),
            'sap_document_number' => $success ? ($responseData['DocNum'] ?? null) : null,
            'duration_ms'         => $durationMs,
        ]);

        if (!$success) {
            $errorMessage = $responseData['error']['message']['value']
                ?? "SAP {$operation} failed with HTTP {$response->status()}";

            throw new SapApiException(
                message: $errorMessage,
                httpStatus: $response->status(),
                sapError: $responseData['error'] ?? null,
                endpoint: $endpoint,
            );
        }

        return new SapSyncResult(
            documentNumber: (string) ($responseData['DocNum'] ?? $responseData['DocEntry']),
            documentEntry: (string) ($responseData['DocEntry'] ?? ''),
            rawResponse: $responseData,
        );
    }

    /**
     * Resolves the SAP Business Partner code for an order.
     *
     * Authenticated users: looks up sap_bp_code on the user record.
     * Guests: falls back to a configured catch-all guest BP code.
     */
    private function resolveCardCode(Order $order): string
    {
        return $order->user?->sap_bp_code
            ?? config('sap.guest_bp_code', 'C_GUEST');
    }

    /**
     * Maps e-commerce payment gateway slugs to SAP payment method names.
     * Extend this as you add more gateways.
     */
    private function mapPaymentGateway(?string $gateway): string
    {
        return match ($gateway) {
            'mpesa'    => 'M-Pesa',
            'stripe'   => 'Credit Card',
            'pesawise' => 'Pesawise',
            default    => 'Other',
        };
    }
}
