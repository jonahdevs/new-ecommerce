<?php

namespace App\Services\Sap;

use App\Models\Order;
use App\Models\SapSyncLog;
use App\Services\Sap\DTOs\SapOrderPayload;
use App\Services\Sap\ValueObjects\SapSyncResult;
use App\Services\Sap\ValueObjects\SapValidationResult;
use Illuminate\Support\Facades\Log;

class SapIntegrationService
{
    public function __construct(private readonly SapClient $client) {}

    /**
     * Phase 1 — POST /api/invoice/create.
     * Sends the confirmed order to SAP and returns the document references.
     *
     * @throws SapApiException
     */
    public function syncOrder(Order $order): SapSyncResult
    {
        $payload = SapOrderPayload::fromOrder($order);
        $start = microtime(true);

        $response = $this->client->post('/api/invoice/create', $payload);

        $durationMs = (int) ((microtime(true) - $start) * 1000);
        $data = $response->json() ?? [];

        $success = $response->successful() && ($data['success'] ?? false) === true;
        $docEntry = (string) ($data['docEntry'] ?? '');
        $docNumber = isset($data['docNumber']) ? (string) $data['docNumber'] : null;

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'create_invoice',
            'status' => $success ? 'success' : 'failed',
            'endpoint' => '/api/invoice/create',
            'http_method' => 'POST',
            'request_payload' => $this->redactPayload($payload),
            'response_payload' => $data,
            'http_status_code' => $response->status(),
            'error_message' => $success ? null : ($data['message'] ?? $response->body()),
            'sap_document_number' => $success ? ($docEntry ?: null) : null,
            'duration_ms' => $durationMs,
        ]);

        if (! $success) {
            $error = $data['message'] ?? "SAP returned HTTP {$response->status()}";

            Log::error('SAP invoice creation failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'http_status' => $response->status(),
                'error' => $error,
                'duration_ms' => $durationMs,
            ]);

            throw new SapApiException(
                message: $error,
                httpStatus: $response->status(),
                endpoint: '/api/invoice/create',
            );
        }

        Log::info('SAP invoice created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'doc_entry' => $docEntry,
            'doc_number' => $docNumber,
            'duration_ms' => $durationMs,
        ]);

        return new SapSyncResult($docEntry, $docNumber, $data);
    }

    /**
     * Phase 2 — POST /api/invoice/validate/{docEntry}.
     * Called by RecoverSapInvoiceJob if no webhook arrived within the delay window.
     *
     * @throws SapApiException
     */
    public function validateInvoice(Order $order): SapValidationResult
    {
        $docEntry = $order->sap_doc_entry;
        $path = "/api/invoice/validate/{$docEntry}";
        $start = microtime(true);

        // 4-minute timeout — KRA validation can be slow, but we don't want to
        // block a worker indefinitely. The webhook is always the primary path.
        $response = $this->client->post($path, [], timeoutSeconds: 240);

        $durationMs = (int) ((microtime(true) - $start) * 1000);
        $data = $response->json() ?? [];

        $cuNumber = $data['cuNumber'] ?? null;
        $success = $response->successful() && ($data['success'] ?? false) === true && $cuNumber;

        SapSyncLog::create([
            'order_id' => $order->id,
            'operation' => 'validate_invoice',
            'status' => $success ? 'success' : 'failed',
            'endpoint' => $path,
            'http_method' => 'POST',
            'request_payload' => ['doc_entry' => $docEntry],
            'response_payload' => $data,
            'http_status_code' => $response->status(),
            'error_message' => $success ? null : ($data['message'] ?? $response->body()),
            'sap_document_number' => $success ? $cuNumber : null,
            'duration_ms' => $durationMs,
        ]);

        if (! $success) {
            $error = $data['message'] ?? "SAP validate returned HTTP {$response->status()} without cuNumber";

            Log::error('SAP invoice validation failed', [
                'order_id' => $order->id,
                'doc_entry' => $docEntry,
                'http_status' => $response->status(),
                'error' => $error,
                'duration_ms' => $durationMs,
            ]);

            throw new SapApiException(
                message: $error,
                httpStatus: $response->status(),
                endpoint: $path,
            );
        }

        Log::info('SAP invoice validated', [
            'order_id' => $order->id,
            'cu_number' => $cuNumber,
            'duration_ms' => $durationMs,
        ]);

        return new SapValidationResult($cuNumber, $docEntry, $data);
    }

    /**
     * Mask card/payment tokens before persisting to sap_sync_logs.
     * The audit trail needs to show the request happened, not store credentials.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactPayload(array $payload): array
    {
        if (isset($payload['credit_guard_response'])) {
            $payload['credit_guard_response'] = array_map(
                fn ($v) => filled($v) ? '[redacted]' : $v,
                $payload['credit_guard_response'],
            );
        }

        return $payload;
    }
}
