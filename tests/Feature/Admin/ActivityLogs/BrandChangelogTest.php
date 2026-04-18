<?php

use App\Models\Brand;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for Brand model changelog tracking
 * 
 * **Validates: Requirements 1.7, 1.8, 1.9**
 * 
 * Tests that the Brand model correctly tracks changes to:
 * - name
 * - is_active
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('brand model logs name changes', function () {
    $brand = Brand::factory()->create([
        'name' => 'Apple',
    ]);

    $brand->update(['name' => 'Apple Inc.']);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('Apple')
        ->and($activity->properties['attributes']['name'])->toBe('Apple Inc.')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('brand model logs is_active changes', function () {
    $brand = Brand::factory()->create([
        'is_active' => true,
    ]);

    $brand->update(['is_active' => false]);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['is_active'])->toBe(true)
        ->and($activity->properties['attributes']['is_active'])->toBe(false)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('brand model logs multiple field changes in single update', function () {
    $brand = Brand::factory()->create([
        'name' => 'Apple',
        'is_active' => true,
    ]);

    $brand->update([
        'name' => 'Apple Inc.',
        'is_active' => false,
    ]);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['name', 'is_active'])
        ->and($activity->properties['attributes'])->toHaveKeys(['name', 'is_active'])
        ->and($activity->properties['old']['name'])->toBe('Apple')
        ->and($activity->properties['attributes']['name'])->toBe('Apple Inc.')
        ->and($activity->properties['old']['is_active'])->toBe(true)
        ->and($activity->properties['attributes']['is_active'])->toBe(false);
});

test('brand model does not log changes to non-tracked fields', function () {
    $brand = Brand::factory()->create([
        'description' => 'Old description',
        'is_active' => true, // Explicitly set is_active to avoid default value logging
    ]);

    $brand->update(['description' => 'New description']);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    // No activity should be logged for non-tracked fields
    expect($activity)->toBeNull();
});

test('brand model does not create log entry when no tracked fields change', function () {
    $brand = Brand::factory()->create([
        'name' => 'Apple',
        'description' => 'Old description',
        'is_active' => true, // Explicitly set is_active to avoid default value logging
    ]);

    // Update only non-tracked field
    $brand->update(['description' => 'New description']);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    // No activity should be logged
    expect($activity)->toBeNull();
});

test('brand model uses correct log name', function () {
    $brand = Brand::factory()->create([
        'name' => 'Apple',
    ]);

    $brand->update(['name' => 'Apple Inc.']);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    expect($activity->log_name)->toBe('brand');
});

test('brand model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $brand = Brand::factory()->create([
        'name' => 'Apple',
    ]);

    $brand->update(['name' => 'Apple Inc.']);

    $activity = Activity::forSubject($brand)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('Apple')
        ->and($activity->properties['attributes']['name'])->toBe('Apple Inc.');
});
