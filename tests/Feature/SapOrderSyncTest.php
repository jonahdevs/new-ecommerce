<?php

use App\Enums\OrderStatus;
use App\Enums\SapSyncStatus;
use App\Jobs\RecoverSapInvoiceJob;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SapSyncLog;
use App\Models\User;
use App\Notifications\SapSyncFailedNotification;
use App\Services\Sap\DTOs\SapOrderPayload;
use App\Services\Sap\SapIntegrationService;
use App\Services\Sap\SapWebhookHandler;
use App\Services\Sap\ValueObjects\SapSyncResult;
use App\Settings\IntegrationSettings;
use App\Settings\NotificationSettings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\postJson;

// ==================================================
// WEBHOOK — CU NUMBER DELIVERY
// ==================================================

it('stores the CU number and marks the order completed when the webhook fires', function () {
    config(['sap.webhook_secret' => '']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::AWAITING_CU,
        'sap_doc_entry' => 'DOC-001',
    ]);

    $handler = app(SapWebhookHandler::class);
    $request = Request::create('/api/webhooks/sap', 'POST', [], [], [], [], json_encode([
        'event' => 'invoice.cu_number_generated',
        'data' => [
            'external_reference' => $order->order_number,
            'cu_number' => 'KRA-CU-12345',
            'validated_at' => '2026-06-06T10:00:00Z',
        ],
    ]));
    $request->headers->set('Content-Type', 'application/json');

    $handler->handle($request);

    $order->refresh();
    expect($order->cu_number)->toBe('KRA-CU-12345')
        ->and($order->sap_sync_status)->toBe(SapSyncStatus::COMPLETED)
        ->and($order->sap_synced_at)->not->toBeNull();
});

it('persists a sap_sync_log entry on CU webhook receipt', function () {
    config(['sap.webhook_secret' => '']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::AWAITING_CU,
    ]);

    $handler = app(SapWebhookHandler::class);
    $request = Request::create('/api/webhooks/sap', 'POST', [], [], [], [], json_encode([
        'event' => 'invoice.cu_number_generated',
        'data' => [
            'external_reference' => $order->order_number,
            'cu_number' => 'KRA-CU-99999',
        ],
    ]));
    $request->headers->set('Content-Type', 'application/json');

    $handler->handle($request);

    expect(SapSyncLog::where('order_id', $order->id)->where('operation', 'cu_webhook')->exists())->toBeTrue();
});

it('ignores a duplicate CU number webhook without overwriting', function () {
    config(['sap.webhook_secret' => '']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::COMPLETED,
        'cu_number' => 'KRA-EXISTING',
    ]);

    $handler = app(SapWebhookHandler::class);
    $request = Request::create('/api/webhooks/sap', 'POST', [], [], [], [], json_encode([
        'event' => 'invoice.cu_number_generated',
        'data' => [
            'external_reference' => $order->order_number,
            'cu_number' => 'KRA-EXISTING',
        ],
    ]));
    $request->headers->set('Content-Type', 'application/json');

    $handler->handle($request);

    expect($order->fresh()->cu_number)->toBe('KRA-EXISTING');
    expect(SapSyncLog::where('order_id', $order->id)->count())->toBe(0);
});

it('handles the legacy flat payload shape (cu_number at root)', function () {
    config(['sap.webhook_secret' => '']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::AWAITING_CU,
    ]);

    $handler = app(SapWebhookHandler::class);
    $request = Request::create('/api/webhooks/sap', 'POST', [], [], [], [], json_encode([
        'external_reference' => $order->order_number,
        'cu_number' => 'KRA-LEGACY-001',
    ]));
    $request->headers->set('Content-Type', 'application/json');

    $handler->handle($request);

    expect($order->fresh()->cu_number)->toBe('KRA-LEGACY-001');
});

// ==================================================
// WEBHOOK — RETURNED STATUS
// ==================================================

it('marks order as RETURNED when a return webhook arrives in a valid state', function () {
    config(['sap.webhook_secret' => '']);

    foreach ([SapSyncStatus::AWAITING_CU, SapSyncStatus::COMPLETED] as $state) {
        $order = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
            'sap_sync_status' => $state,
        ]);

        $handler = app(SapWebhookHandler::class);
        $request = Request::create('/api/webhooks/sap', 'POST', [], [], [], [], json_encode([
            'external_reference' => $order->order_number,
            'status' => 'returned',
        ]));
        $request->headers->set('Content-Type', 'application/json');

        $handler->handle($request);

        expect($order->fresh()->sap_sync_status)->toBe(SapSyncStatus::RETURNED);
    }
});

it('ignores a RETURNED webhook for orders not in a valid state', function () {
    config(['sap.webhook_secret' => '']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::SYNCING,
    ]);

    $handler = app(SapWebhookHandler::class);
    $request = Request::create('/api/webhooks/sap', 'POST', [], [], [], [], json_encode([
        'external_reference' => $order->order_number,
        'status' => 'returned',
    ]));
    $request->headers->set('Content-Type', 'application/json');

    $handler->handle($request);

    expect($order->fresh()->sap_sync_status)->toBe(SapSyncStatus::SYNCING);
});

// ==================================================
// WEBHOOK — CONTROLLER + AUTH
// ==================================================

it('returns 401 when the SAP webhook secret is wrong', function () {
    config(['sap.webhook_secret' => 'correct-secret']);

    postJson('/api/webhooks/sap', [], ['X-SAP-Secret' => 'wrong'])->assertUnauthorized();
});

it('returns 200 for valid SAP webhook requests', function () {
    config(['sap.webhook_secret' => 'test-secret']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::AWAITING_CU,
    ]);

    postJson('/api/webhooks/sap', [
        'event' => 'invoice.cu_number_generated',
        'data' => [
            'external_reference' => $order->order_number,
            'cu_number' => 'KRA-TEST-001',
        ],
    ], ['X-SAP-Secret' => 'test-secret'])
        ->assertOk()
        ->assertJson(['success' => true]);
});

// ==================================================
// SYNC JOB
// ==================================================

it('dispatches SyncOrderToSapJob when markConfirmed transitions PENDING to PROCESSING', function () {
    Queue::fake();
    $this->seed(PermissionSeeder::class);

    app(IntegrationSettings::class)->fill([
        'sap_enabled' => true,
        'sap_auto_sync_orders' => true,
    ])->save();

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $order->markConfirmed();

    Queue::assertPushed(SyncOrderToSapJob::class, fn ($job) => $job->order->is($order));
});

it('does not dispatch the SAP job when sap_auto_sync_orders is disabled', function () {
    Queue::fake();
    $this->seed(PermissionSeeder::class);

    app(IntegrationSettings::class)->fill([
        'sap_enabled' => true,
        'sap_auto_sync_orders' => false,
    ])->save();

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $order->markConfirmed();

    Queue::assertNotPushed(SyncOrderToSapJob::class);
});

it('does not dispatch the job when the order is already past PENDING', function () {
    Queue::fake();

    $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);
    $order->markConfirmed();

    Queue::assertNothingPushed();
});

it('handles the job: updates status to AWAITING_CU and dispatches RecoverSapInvoiceJob', function () {
    Queue::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::PENDING,
    ]);

    $sap = Mockery::mock(SapIntegrationService::class);
    $sap->shouldReceive('syncOrder')
        ->once()
        ->with(Mockery::type(Order::class))
        ->andReturn(new SapSyncResult('DOC-ENTRY-001', 'DOC-NUM-001', []));

    (new SyncOrderToSapJob($order))->handle($sap);

    $order->refresh();
    expect($order->sap_doc_entry)->toBe('DOC-ENTRY-001')
        ->and($order->sap_sync_status)->toBe(SapSyncStatus::AWAITING_CU);

    Queue::assertPushed(RecoverSapInvoiceJob::class);
});

it('skips create and re-dispatches RecoverSapInvoiceJob if doc_entry exists and status is AWAITING_CU', function () {
    Queue::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::AWAITING_CU,
        'sap_doc_entry' => 'DOC-EXISTING',
    ]);

    $sap = Mockery::mock(SapIntegrationService::class);
    $sap->shouldNotReceive('syncOrder');

    (new SyncOrderToSapJob($order))->handle($sap);

    Queue::assertPushed(RecoverSapInvoiceJob::class);
});

it('skips entirely when order is already COMPLETED', function () {
    Queue::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::COMPLETED,
    ]);

    $sap = Mockery::mock(SapIntegrationService::class);
    $sap->shouldNotReceive('syncOrder');

    (new SyncOrderToSapJob($order))->handle($sap);

    Queue::assertNothingPushed();
});

it('marks order FAILED and notifies staff when the job permanently fails', function () {
    Notification::fake();
    $this->seed(PermissionSeeder::class);

    // Fan out to individual staff; default seeded routing is 'central' (one inbox).
    app(NotificationSettings::class)->fill(['staff_email_routing' => 'individual'])->save();

    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::SYNCING,
    ]);

    $job = new SyncOrderToSapJob($order);
    $job->failed(new RuntimeException('SAP server error'));

    $order->refresh();
    expect($order->sap_sync_status)->toBe(SapSyncStatus::FAILED)
        ->and($order->sap_sync_error)->toBe('SAP server error');

    Notification::assertSentTo($staff, SapSyncFailedNotification::class);
});

it('does not double-flag a FAILED order in the failed callback', function () {
    Notification::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'sap_sync_status' => SapSyncStatus::FAILED,
        'sap_sync_error' => 'original error',
    ]);

    $job = new SyncOrderToSapJob($order);
    $job->failed(new RuntimeException('another error'));

    expect($order->fresh()->sap_sync_error)->toBe('original error');
    Notification::assertNothingSent();
});

// ==================================================
// SAP ORDER PAYLOAD DTO
// ==================================================

it('builds the SAP payload with order items and customer details', function () {
    $order = Order::factory()->create(['notes' => 'ring bell']);

    Payment::factory()->successful()->create([
        'order_id' => $order->id,
        'mpesa_receipt' => 'MPE123456789',
    ]);

    $payload = SapOrderPayload::fromOrder($order);

    expect($payload)->toHaveKeys(['credit_guard_response', 'customer', 'order'])
        ->and($payload['customer']['email'])->toBe($order->user->email)
        ->and($payload['customer']['note'])->toBe('ring bell')
        ->and($payload['credit_guard_response']['uid'])->toBe('MPE123456789')
        ->and($payload['order']['Orderid'])->toBe($order->id)
        ->and($payload['order']['payment_status'])->toBe('Paid');
});

// ==================================================
// SAP RESYNC COMMAND
// ==================================================

it('sap:resync dispatches a job for a single order by number', function () {
    Queue::fake();

    $order = Order::factory()->create(['sap_sync_status' => SapSyncStatus::FAILED]);

    $this->artisan('sap:resync', ['order' => $order->order_number])
        ->assertSuccessful();

    Queue::assertPushed(SyncOrderToSapJob::class, fn ($job) => $job->order->is($order));
    expect($order->fresh()->sap_sync_status)->toBe(SapSyncStatus::PENDING);
});

it('sap:resync --failed dispatches jobs for all failed orders', function () {
    Queue::fake();

    Order::factory()->count(3)->create(['sap_sync_status' => SapSyncStatus::FAILED]);
    Order::factory()->create(['sap_sync_status' => SapSyncStatus::COMPLETED]);

    $this->artisan('sap:resync', ['--failed' => true])->assertSuccessful();

    Queue::assertPushed(SyncOrderToSapJob::class, 3);
});

it('sap:resync --stuck dispatches jobs for orders stuck over an hour', function () {
    Queue::fake();

    Order::factory()->count(2)->create([
        'sap_sync_status' => SapSyncStatus::SYNCING,
        'updated_at' => now()->subHours(2),
    ]);
    Order::factory()->create([
        'sap_sync_status' => SapSyncStatus::SYNCING,
        'updated_at' => now()->subMinutes(30),
    ]);

    $this->artisan('sap:resync', ['--stuck' => true])->assertSuccessful();

    Queue::assertPushed(SyncOrderToSapJob::class, 2);
});
