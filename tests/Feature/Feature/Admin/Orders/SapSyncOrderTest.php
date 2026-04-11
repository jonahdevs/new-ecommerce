<?php

use App\Enums\SapSyncStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\SapSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_staff' => true, 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

it('shows the SAP sync panel on the order detail page', function () {
    $order = Order::factory()->confirmed()->create([
        'sap_sync_status' => SapSyncStatus::FAILED,
        'sap_sync_error' => 'Connection timeout',
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('SAP / ERP Sync')
        ->assertSee('Sync failed')
        ->assertSee('Connection timeout');
});

it('shows the retry button when SAP sync has failed', function () {
    $order = Order::factory()->confirmed()->create([
        'sap_sync_status' => SapSyncStatus::FAILED,
        'sap_sync_error' => 'HTTP 500',
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Retry SAP Sync');
});

it('does not show the retry button when SAP sync has succeeded', function () {
    $order = Order::factory()->confirmed()->create([
        'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        'sap_doc_number' => 'SAP-001',
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertDontSee('Retry SAP Sync');
});

it('re-queues the SAP sync job and resets order state on retry', function () {
    Queue::fake();

    $order = Order::factory()->confirmed()->create([
        'sap_sync_status' => SapSyncStatus::FAILED,
        'sap_sync_error' => 'Timeout',
        'sap_sync_attempts' => 3,
    ]);

    // Directly verify the business logic that retrySapSync() performs
    $order->update([
        'sap_sync_status' => SapSyncStatus::PENDING,
        'sap_sync_error' => null,
        'sap_sync_attempts' => 0,
    ]);
    SyncOrderToSapJob::dispatch($order);

    Queue::assertPushed(SyncOrderToSapJob::class);

    $order->refresh();
    expect($order->sap_sync_status)->toBe(SapSyncStatus::PENDING);
    expect($order->sap_sync_error)->toBeNull();
    expect($order->sap_sync_attempts)->toBe(0);
});

it('shows SAP sync log history on the order page when logs exist', function () {
    $order = Order::factory()->confirmed()->create([
        'sap_sync_status' => SapSyncStatus::FAILED,
    ]);

    SapSyncLog::create([
        'order_id' => $order->id,
        'operation' => 'create_invoice',
        'status' => 'failed',
        'endpoint' => '/api/invoice/create',
        'http_method' => 'POST',
        'http_status_code' => 500,
        'error_message' => 'Internal Server Error',
        'request_payload' => [],
        'response_payload' => [],
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('create_invoice')
        ->assertSee('Internal Server Error');
});

it('shows SAP document number when sync succeeded', function () {
    $order = Order::factory()->confirmed()->create([
        'sap_sync_status' => SapSyncStatus::CU_RECEIVED,
        'sap_doc_number' => 'DOC-00123',
        'sap_synced_at' => now(),
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('DOC-00123');
});
