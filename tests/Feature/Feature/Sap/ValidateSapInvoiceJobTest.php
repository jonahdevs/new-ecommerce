<?php

use App\Enums\SapSyncStatus;
use App\Jobs\ValidateSapInvoiceJob;
use App\Models\Order;
use App\Notifications\SapSyncFailedNotification;
use App\Services\Sap\KraReceiptService;
use App\Services\Sap\SapApiException;
use App\Services\Sap\SapIntegrationService;
use App\Services\Sap\ValueObjects\SapValidationResult;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\mock;

beforeEach(function () {
    Notification::fake();
});

it('stores the cuNumber, moves order to CU_RECEIVED and generates the receipt on success', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::VALIDATING,
        'sap_doc_entry' => '23321',
        'sap_doc_number' => '28291',
    ]);

    $result = new SapValidationResult(
        cuNumber: 'Test 1234',
        docEntry: '23321',
        rawResponse: ['success' => true, 'cuNumber' => 'Test 1234'],
    );

    mock(SapIntegrationService::class)
        ->shouldReceive('validateInvoice')
        ->once()
        ->andReturn($result);

    mock(KraReceiptService::class)
        ->shouldReceive('generate')
        ->once();

    (new ValidateSapInvoiceJob($order))->handle(
        app(SapIntegrationService::class),
        app(KraReceiptService::class),
    );

    $order->refresh();
    expect($order->kra_cu_number)->toBe('Test 1234');
    expect($order->sap_sync_status)->toBe(SapSyncStatus::CU_RECEIVED);
    expect($order->kra_validated_at)->not->toBeNull();
});

it('skips processing when order is already CU_RECEIVED (webhook arrived first)', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        'kra_cu_number' => 'WEBHOOK-1234',
        'sap_doc_entry' => '23321',
    ]);

    mock(SapIntegrationService::class)
        ->shouldReceive('validateInvoice')
        ->never();

    (new ValidateSapInvoiceJob($order))->handle(
        app(SapIntegrationService::class),
        app(KraReceiptService::class),
    );

    $order->refresh();
    expect($order->kra_cu_number)->toBe('WEBHOOK-1234');
});

it('retries on a retryable SAP error (HTTP 500)', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::VALIDATING,
        'sap_doc_entry' => '23321',
    ]);

    mock(SapIntegrationService::class)
        ->shouldReceive('validateInvoice')
        ->andThrow(new SapApiException('Server error', 500));

    expect(fn () => (new ValidateSapInvoiceJob($order))->handle(
        app(SapIntegrationService::class),
        app(KraReceiptService::class),
    ))->toThrow(SapApiException::class);
});

it('marks order as FAILED and sends alert after exhausting all retries', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::VALIDATING,
        'sap_doc_entry' => '23321',
    ]);

    $exception = new SapApiException('KRA validation timeout', 504);

    $job = new ValidateSapInvoiceJob($order);
    $job->failed($exception);

    $order->refresh();
    expect($order->sap_sync_status)->toBe(SapSyncStatus::FAILED);
    expect($order->sap_sync_error)->toBe('KRA validation timeout');

    Notification::assertSentOnDemand(SapSyncFailedNotification::class);
});

it('does not send a duplicate failure alert if order is already marked FAILED', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::FAILED,
        'sap_sync_error' => 'previous error',
    ]);

    $job = new ValidateSapInvoiceJob($order);
    $job->failed(new Exception('new error'));

    Notification::assertNothingSent();
});

it('does not mark as FAILED if webhook resolved the order between retries', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        'kra_cu_number' => 'WEBHOOK-5678',
    ]);

    $job = new ValidateSapInvoiceJob($order);
    $job->failed(new Exception('timeout'));

    $order->refresh();
    expect($order->sap_sync_status)->toBe(SapSyncStatus::CU_RECEIVED);

    Notification::assertNothingSent();
});

it('continues to CU_RECEIVED even when receipt generation fails', function () {
    $order = Order::factory()->processing()->create([
        'sap_sync_status' => SapSyncStatus::VALIDATING,
        'sap_doc_entry' => '23321',
    ]);

    $result = new SapValidationResult(
        cuNumber: 'Test 1234',
        docEntry: '23321',
        rawResponse: [],
    );

    mock(SapIntegrationService::class)
        ->shouldReceive('validateInvoice')
        ->andReturn($result);

    mock(KraReceiptService::class)
        ->shouldReceive('generate')
        ->andThrow(new RuntimeException('PDF driver unavailable'));

    (new ValidateSapInvoiceJob($order))->handle(
        app(SapIntegrationService::class),
        app(KraReceiptService::class),
    );

    $order->refresh();
    expect($order->sap_sync_status)->toBe(SapSyncStatus::CU_RECEIVED);
    expect($order->kra_cu_number)->toBe('Test 1234');
});
