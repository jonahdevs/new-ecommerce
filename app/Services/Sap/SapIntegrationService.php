<?php

namespace App\Services\Sap;

use App\Models\Order;
use App\Models\SapSyncLog;
use App\Services\Sap\ValueObjects\SapSyncResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a confirmed, paid order to the SAP middleware API.
 *
 * The middleware handles Sales Order + A/R Invoice + Incoming Payment
 * creation internally — we just POST a single combined payload.
 *
 * Endpoint: POST /api/invoice/create
 * Payload shape mirrors the credit_guard_response / customer / order
 * structure shared by the SAP developer.
 */
class SapIntegrationService
{
    private readonly string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('sap.base_url');
    }

    // ================================================================
    // Public API — called by SyncOrderToSapJob
    // ================================================================

    /**
     * Sync a confirmed, paid order to SAP.
     * Returns a SapSyncResult with the SAP document number on success.
     * Throws SapApiException on any non-2xx response.
     */
    public function syncOrder(Order $order): SapSyncResult
    {
        $order->loadMissing(['items.product', 'payment', 'user']);

        $payload = $this->buildPayload($order);

        $start = microtime(true);

        $http = Http::withOptions(['verify' => false])->timeout(60);

        if ($apiKey = config('sap.api_key')) {
            $http = $http->withHeaders(['x-api-key' => $apiKey]);
        }

        $response = $http->post("{$this->baseUrl}/api/invoice/create", $payload);

        $durationMs = (int) ((microtime(true) - $start) * 1000);
        $responseData = $response->json() ?? [];

        // SAP returns HTTP 200 even on business logic failures.
        // We must check both the HTTP status and the `success` flag in the body.
        $success = $response->successful() && ($responseData['success'] ?? false) === true;
        $docEntry = (string) ($responseData['docEntry'] ?? '');
        $docNumber = isset($responseData['docNumber']) ? (string) $responseData['docNumber'] : null;

        // Audit log — always written regardless of outcome
        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'create_invoice',
            'status' => $success ? 'success' : 'failed',
            'endpoint' => '/api/invoice/create',
            'http_method' => 'POST',
            'request_payload' => $payload,
            'response_payload' => $responseData,
            'http_status_code' => $response->status(),
            'error_message' => $success ? null : ($responseData['message'] ?? $response->body()),
            'sap_document_number' => $success ? ($docEntry ?: null) : null,
            'duration_ms' => $durationMs,
        ]);

        if (! $success) {
            $error = $responseData['message'] ?? "SAP API returned HTTP {$response->status()}";

            Log::error('SAP invoice creation failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'error' => $error,
                'duration' => $durationMs,
            ]);

            throw new SapApiException(
                message: $error,
                httpStatus: $response->status(),
                endpoint: '/api/invoice/create',
            );
        }

        Log::info('SAP invoice created', [
            'order_id' => $order->id,
            'doc_number' => $docNumber,
            'doc_entry' => $docEntry,
            'duration_ms' => $durationMs,
        ]);

        return new SapSyncResult(
            docEntry: $docEntry,
            docNumber: $docNumber,
            rawResponse: $responseData,
        );
    }

    // ================================================================
    // Payload builder
    // ================================================================

    private function buildPayload(Order $order): array
    {
        $payment = $order->payment;
        $user = $order->user;
        $meta = $payment?->meta ?? [];

        return [
            // Payment / card details from the gateway response
            'credit_guard_response' => [
                'authNumber' => $meta['auth_number'] ?? $meta['authorization_code'] ?? '',
                'cardBrand' => $meta['card_brand'] ?? $payment?->card_brand ?? '',
                'cardExpiration' => $meta['card_expiration'] ?? '',
                'cardId' => $meta['card_id'] ?? '',
                'cardNo' => $meta['last4'] ?? $meta['card_last4'] ?? '',
                'cgUid' => $meta['cg_uid'] ?? '',
                'creditCardToken' => $meta['credit_card_token'] ?? $payment?->gateway_order_id ?? '',
                'numberOfPayments' => $meta['number_of_payments'] ?? '0',
                'personalId' => $meta['personal_id'] ?? '',
                'uid' => $meta['transaction_id'] ?? $payment?->transaction_id ?? '',
            ],

            // Customer details
            'customer' => [
                'created_at' => $user?->created_at?->toISOString() ?? now()->toISOString(),
                'email' => $user?->email ?? $order->guest_info['email'] ?? '',
                'full_address' => $this->resolveAddress($order),
                'full_name' => $user?->name ?? $order->guest_info['name'] ?? '',
                'mobile_phone' => $this->resolvePhone($order),
                'note' => $order->customer_notes,
                'updated_at' => $user?->updated_at?->toISOString() ?? now()->toISOString(),
            ],

            // Order details
            'order' => [
                'cart' => [
                    'debit_total_price' => $order->total_cents / 100,
                    'lines'             => $order->items->map(fn($item) => [
                        'code'         => $item->product_snapshot['sku'] ?? $item->product?->sku ?? 'UNKNOWN',
                        'item_id'      => $item->product_id,
                        'line_item_id' => $item->id,
                        'price'        => $item->unit_price_cents / 100,
                        'quantity'     => $item->quantity,
                        'linetotal'    => $item->total_cents / 100,
                    ])->values()->toArray(),

                    // "lines" => [
                    //     [
                    //         "code" => "FAB/SPE/00468",
                    //         "item_id" => 8813071,
                    //         "line_item_id" => 164693573,
                    //         "price" => 449,
                    //         "quantity" => 100,
                    //         "linetotal" => 44900
                    //     ],
                    //     [
                    //         "code" => "IMG/REF/00044",
                    //         "item_id" => 8813071,
                    //         "line_item_id" => 164693573,
                    //         "price" => 1000,
                    //         "quantity" => 3,
                    //         "linetotal" => 1000
                    //     ]

                    // ],

                ],
                'Orderid' => $order->id,
                'name' => $user?->name ?? $order->guest_info['name'] ?? '',
                'payment_status' => 'Paid',
                'phone' => $this->resolvePhone($order),
            ],
        ];
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function resolvePhone(Order $order): string
    {
        return $order->user?->phone
            ?? $order->shipping_address['phone_number']
            ?? $order->guest_info['phone']
            ?? '';
    }

    private function resolveAddress(Order $order): ?string
    {
        $addr = $order->shipping_address;
        if (! $addr) {
            return null;
        }

        return implode(', ', array_filter([
            $addr['address'] ?? null,
            $addr['area'] ?? null,
            $addr['county'] ?? null,
        ]));
    }
}
