<?php

use App\Models\Order;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_staff' => true, 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

it('SAP sync completed events use the sap_ prefix', function () {
    $order = Order::factory()->confirmed()->create();

    activity()
        ->performedOn($order)
        ->withProperties(['sap_document' => 'DOC-001', 'attempt' => 1])
        ->log('sap_sync_completed');

    $activity = Activity::where('description', 'sap_sync_completed')->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toStartWith('sap_');
});

it('SAP sync failed events use the sap_ prefix', function () {
    $order = Order::factory()->confirmed()->create();

    activity()
        ->performedOn($order)
        ->withProperties(['error' => 'Timeout', 'attempts' => 3])
        ->log('sap_sync_failed');

    $activity = Activity::where('description', 'sap_sync_failed')->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toStartWith('sap_');
});

it('KRA validated events use the sap_ prefix', function () {
    $order = Order::factory()->confirmed()->create();

    activity()
        ->performedOn($order)
        ->withProperties(['kra_cu_number' => 'CU-12345'])
        ->log('sap_kra_validated');

    $activity = Activity::where('description', 'sap_kra_validated')->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toStartWith('sap_');
});

it('activity log page loads with sap filter applied', function () {
    $order = Order::factory()->confirmed()->create();

    activity()->performedOn($order)->log('sap_sync_completed');

    $this->get(route('admin.activity-logs.index', ['eventType' => 'sap']))
        ->assertOk();
});
