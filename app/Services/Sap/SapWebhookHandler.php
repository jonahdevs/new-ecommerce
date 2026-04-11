<?php

namespace App\Services\Sap;

use App\Enums\SapSyncStatus;
use App\Models\Order;
use App\Models\SapSyncLog;
use App\Services\Sap\ValueObjects\CuNumberResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SapWebhookHandler
{
    public function __construct(
        private readonly KraReceiptService $receiptService,
    ) {}

    // ================================================================
    // Entry point — called by SapWebhookController
    // ================================================================

    public function handle(Request $request): void
    {
        $payload = $request->json()->all();

        $this->logWebhookRequest($request, $payload);

        if (! $this->validateSignature($request)) {
            Log::warning('SAP webhook rejected — invalid secret', [
                'ip' => $request->ip(),
            ]);
            abort(401, 'Invalid webhook secret.');
        }

        $this->processPayload($payload);
    }

    // ================================================================
    // Signature validation
    // SAP sends a simple secret header (X-SAP-Secret) compared with
    // our configured secret using hash_equals to prevent timing attacks.
    // ================================================================

    public function validateSignature(Request $request): bool
    {
        $secret = config('sap.webhook_secret');

        if (empty($secret)) {
            return true;
        }

        $providedSecret = $request->header('X-SAP-Secret');

        if (! $providedSecret) {
            return false;
        }

        return hash_equals($secret, $providedSecret);
    }

    // ================================================================
    // Payload processing
    //
    // Webhook payload shape:
    //   external_reference  — our order reference (always present)
    //   cu_number           — KRA CU number (present on KRA validation)
    //   status              — "returned" when the order is returned in SAP
    // ================================================================

    public function processPayload(array $payload): void
    {
        $reference = $payload['external_reference'] ?? null;

        if (! $reference) {
            Log::warning('SAP webhook: missing external_reference in payload');

            return;
        }

        $order = Order::where('reference', $reference)->first();

        if (! $order) {
            Log::warning('SAP webhook: unknown order reference', ['reference' => $reference]);

            return;
        }

        $status = $payload['status'] ?? null;

        if ($status === 'returned') {
            $this->handleReturn($order, $payload);

            return;
        }

        $this->handleCuNumber($order, $payload);
    }

    // ================================================================
    // CU number received — store it and send the receipt
    // ================================================================

    private function handleCuNumber(Order $order, array $payload): void
    {
        $cuNumber = $payload['cu_number'] ?? null;

        if (! $cuNumber) {
            Log::warning('SAP webhook: missing cu_number in payload', [
                'order_id' => $order->id,
            ]);

            return;
        }

        // Idempotency — skip if we already have this exact CU number
        if ($order->kra_cu_number && $order->kra_cu_number === $cuNumber) {
            Log::info('SAP webhook: duplicate delivery, CU number already stored', [
                'order_id' => $order->id,
                'kra_cu_number' => $cuNumber,
            ]);

            return;
        }

        $result = new CuNumberResult(
            cuNumber: $cuNumber,
            validatedAt: isset($payload['validated_at'])
                ? Carbon::parse($payload['validated_at'])
                : now(),
        );

        $order->update([
            'kra_cu_number' => $result->cuNumber,
            'kra_validated_at' => $result->validatedAt,
            'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        ]);

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'cu_webhook',
            'status' => 'success',
            'endpoint' => '/webhooks/sap',
            'http_method' => 'POST',
            'request_payload' => $payload,
            'response_payload' => null,
            'http_status_code' => 200,
            'duration_ms' => null,
        ]);

        Log::info('SAP webhook: CU number stored', [
            'order_id' => $order->id,
            'kra_cu_number' => $result->cuNumber,
            'kra_validated_at' => $result->validatedAt->toISOString(),
        ]);

        activity()
            ->performedOn($order)
            ->withProperties([
                'kra_cu_number' => $result->cuNumber,
                'kra_validated_at' => $result->validatedAt->toISOString(),
            ])
            ->log('sap_kra_validated');

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

    // ================================================================
    // Order returned in SAP
    // ================================================================

    private function handleReturn(Order $order, array $payload): void
    {
        $order->update([
            'sap_sync_status' => SapSyncStatus::RETURNED,
        ]);

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'return_webhook',
            'status' => 'success',
            'endpoint' => '/webhooks/sap',
            'http_method' => 'POST',
            'request_payload' => $payload,
            'response_payload' => null,
            'http_status_code' => 200,
            'duration_ms' => null,
        ]);

        Log::info('SAP webhook: order marked as returned', [
            'order_id' => $order->id,
            'reference' => $order->reference,
        ]);

        activity()
            ->performedOn($order)
            ->log('sap_order_returned');
    }

    // ================================================================
    // Audit log — written before any processing so even rejected
    // requests are captured.
    // ================================================================

    private function logWebhookRequest(Request $request, array $payload): void
    {
        $reference = $payload['external_reference'] ?? null;
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
