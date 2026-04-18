<?php

use App\Models\Category;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for Category model changelog tracking
 * 
 * **Validates: Requirements 1.6, 1.8, 1.9**
 * 
 * Tests that the Category model correctly tracks changes to:
 * - name
 * - parent_id
 * - status
 * - sort_order
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('category model logs name changes', function () {
    $category = Category::factory()->create([
        'name' => 'Electronics',
    ]);

    $category->update(['name' => 'Consumer Electronics']);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('Electronics')
        ->and($activity->properties['attributes']['name'])->toBe('Consumer Electronics')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('category model logs parent_id changes', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $newParentCategory = Category::factory()->create(['name' => 'New Parent']);

    $category = Category::factory()->create([
        'name' => 'Child Category',
        'parent_id' => $parentCategory->id,
    ]);

    $category->update(['parent_id' => $newParentCategory->id]);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['parent_id'])->toBe($parentCategory->id)
        ->and($activity->properties['attributes']['parent_id'])->toBe($newParentCategory->id)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('category model logs status changes', function () {
    $category = Category::factory()->create([
        'status' => 'draft',
    ]);

    $category->update(['status' => 'active']);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['status'])->toBe('draft')
        ->and($activity->properties['attributes']['status'])->toBe('active')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('category model logs sort_order changes', function () {
    $category = Category::factory()->create([
        'sort_order' => 10,
    ]);

    $category->update(['sort_order' => 20]);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['sort_order'])->toBe(10)
        ->and($activity->properties['attributes']['sort_order'])->toBe(20)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('category model logs multiple field changes in single update', function () {
    $category = Category::factory()->create([
        'name' => 'Electronics',
        'status' => 'draft',
        'sort_order' => 10,
    ]);

    $category->update([
        'name' => 'Consumer Electronics',
        'status' => 'active',
        'sort_order' => 20,
    ]);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['name', 'status', 'sort_order'])
        ->and($activity->properties['attributes'])->toHaveKeys(['name', 'status', 'sort_order'])
        ->and($activity->properties['old']['name'])->toBe('Electronics')
        ->and($activity->properties['attributes']['name'])->toBe('Consumer Electronics')
        ->and($activity->properties['old']['status'])->toBe('draft')
        ->and($activity->properties['attributes']['status'])->toBe('active')
        ->and($activity->properties['old']['sort_order'])->toBe(10)
        ->and($activity->properties['attributes']['sort_order'])->toBe(20);
});

test('category model does not log changes to non-tracked fields', function () {
    $category = Category::factory()->create([
        'description' => 'Old description',
        'status' => 'draft', // Explicitly set status to avoid default value logging
    ]);

    $category->update(['description' => 'New description']);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    // No activity should be logged for non-tracked fields
    expect($activity)->toBeNull();
});

test('category model does not create log entry when no tracked fields change', function () {
    $category = Category::factory()->create([
        'name' => 'Electronics',
        'description' => 'Old description',
        'status' => 'draft', // Explicitly set status to avoid default value logging
    ]);

    // Update only non-tracked field
    $category->update(['description' => 'New description']);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    // No activity should be logged
    expect($activity)->toBeNull();
});

test('category model uses correct log name', function () {
    $category = Category::factory()->create([
        'name' => 'Electronics',
    ]);

    $category->update(['name' => 'Consumer Electronics']);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity->log_name)->toBe('category');
});

test('category model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $category = Category::factory()->create([
        'name' => 'Electronics',
    ]);

    $category->update(['name' => 'Consumer Electronics']);

    $activity = Activity::forSubject($category)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('Electronics')
        ->and($activity->properties['attributes']['name'])->toBe('Consumer Electronics');
});
