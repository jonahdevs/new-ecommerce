<?php

namespace App\Services\Sap;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Models\SapSyncLog;
use App\Services\Sap\ValueObjects\CuNumberResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SapWebhookHandler
{
    public function __construct(
        private readonly KraReceiptService $receiptService,
    ) {
    }

    // ================================================================
    // Entry point — called by SapWebhookController
    // ================================================================

    public function handle(Request $request): void
    {
        $payload = $request->json()->all();

        $this->logWebhookRequest($request, $payload);

        // Property 4: reject anything with an invalid or missing secret header
        if (!$this->validateSignature($request)) {
            Log::warning('SAP webhook rejected — invalid secret', [
                'ip' => $request->ip(),
            ]);
            abort(401, 'Invalid webhook secret.');
        }

        $this->processPayload($payload);
    }

    // ================================================================
    // Signature validation
    // SAP sends a simple secret header (X-SAP-Secret) that we compare
    // with our configured secret using hash_equals to prevent timing attacks.
    // ================================================================

    public function validateSignature(Request $request): bool
    {
        $secret = config('sap.webhook_secret');

        // If no secret configured, skip validation (not recommended for production)
        if (empty($secret)) {
            return true;
        }

        $providedSecret = $request->header('X-SAP-Secret');

        if (!$providedSecret) {
            return false;
        }

        return hash_equals($secret, $providedSecret);
    }

    // ================================================================
    // Payload processing
    // ================================================================

    public function processPayload(array $payload): void
    {
        $event = $payload['event'] ?? null;

        if ($event !== 'invoice.cu_number_generated') {
            Log::info('SAP webhook: unhandled event type, ignoring', ['event' => $event]);
            return;
        }

        $data = $payload['data'] ?? [];
        $reference = $data['external_reference'] ?? null;

        // Property 6: reject webhooks with unknown order references
        if (!$reference) {
            Log::warning('SAP webhook: missing external_reference in payload');
            return;
        }

        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            Log::warning('SAP webhook: unknown order reference', ['reference' => $reference]);
            return;
        }

        // Property 5: idempotency — if we already have this exact CU number, skip
        $incomingCuNumber = $data['cu_number'] ?? null;

        if ($order->kra_cu_number && $order->kra_cu_number === $incomingCuNumber) {
            Log::info('SAP webhook: duplicate delivery, CU number already stored', [
                'order_id' => $order->id,
                'kra_cu_number' => $incomingCuNumber,
            ]);
            return;
        }

        $cuResult = new CuNumberResult(
            cuNumber: $incomingCuNumber,
            kraInvoiceNumber: $data['kra_invoice_number'] ?? null,
            validatedAt: isset($data['validated_at'])
            ? \Carbon\Carbon::parse($data['validated_at'])
            : now(),
        );

        $this->storeCuNumber($order, $cuResult, $payload);
        $this->generateAndSendReceipt($order);
    }

    // ================================================================
    // Private helpers
    // ================================================================

    /**
     * Property 7: stores all three KRA fields atomically and updates
     * sap_sync_status to cu_received.
     */
    private function storeCuNumber(Order $order, CuNumberResult $result, array $rawPayload): void
    {
        $order->update([
            'kra_cu_number' => $result->cuNumber,
            'kra_invoice_number' => $result->kraInvoiceNumber,
            'kra_validated_at' => $result->validatedAt,
            'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        ]);

        // Log the webhook as a successful inbound operation
        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'cu_webhook',
            'status' => 'success',
            'endpoint' => '/webhooks/sap',
            'http_method' => 'POST',
            'request_payload' => $rawPayload,
            'response_payload' => null,
            'http_status_code' => 200,
            'duration_ms' => null,
        ]);

        Log::info('SAP webhook: CU number stored', [
            'order_id' => $order->id,
            'kra_cu_number' => $result->cuNumber,
        ]);

        // Activity log for audit trail
        activity()
            ->performedOn($order)
            ->withProperties([
                'kra_cu_number' => $result->cuNumber,
                'kra_invoice_number' => $result->kraInvoiceNumber,
                'kra_validated_at' => $result->validatedAt?->toISOString(),
            ])
            ->log('kra_cu_number_received');
    }

    private function generateAndSendReceipt(Order $order): void
    {
        try {
            $this->receiptService->generate($order);
            $this->receiptService->sendToCustomer($order);
        } catch (\Throwable $e) {
            // Receipt failure must never cause the webhook to return 500 —
            // SAP would keep retrying the webhook instead of the real issue.
            Log::error('SAP webhook: receipt generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Property 13: logs every inbound webhook request for audit purposes,
     * before any processing so even rejected requests are captured.
     */
    private function logWebhookRequest(Request $request, array $payload): void
    {
        $reference = $payload['data']['external_reference'] ?? null;
        $order = $reference ? Order::where('reference', $reference)->first() : null;

        if ($order) {
            SapSyncLog::create([
                'order_id' => $order->id,
                'operation' => 'cu_webhook',
                'status' => 'pending',
                'endpoint' => '/webhooks/sap',
                'http_method' => 'POST',
                'request_payload' => $payload,
                'response_payload' => null,
                'http_status_code' => null,
                'duration_ms' => null,
            ]);
        }
    }
}
