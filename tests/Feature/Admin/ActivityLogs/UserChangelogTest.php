<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for User model changelog tracking
 * 
 * **Validates: Requirements 1.5, 1.8, 1.9**
 * 
 * Tests that the User model correctly tracks changes to:
 * - name
 * - email
 * - status
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('user model logs name changes', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    $user->update(['name' => 'Jane Doe']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('John Doe')
        ->and($activity->properties['attributes']['name'])->toBe('Jane Doe')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('user model logs email changes', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
    ]);

    $user->update(['email' => 'new@example.com']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['email'])->toBe('old@example.com')
        ->and($activity->properties['attributes']['email'])->toBe('new@example.com')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('user model logs status changes', function () {
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->update(['status' => 'inactive']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['status'])->toBe('active')
        ->and($activity->properties['attributes']['status'])->toBe('inactive')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('user model logs multiple field changes in single update', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => 'active',
    ]);

    $user->update([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'status' => 'inactive',
    ]);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['name', 'email', 'status'])
        ->and($activity->properties['attributes'])->toHaveKeys(['name', 'email', 'status'])
        ->and($activity->properties['old']['name'])->toBe('John Doe')
        ->and($activity->properties['attributes']['name'])->toBe('Jane Doe')
        ->and($activity->properties['old']['email'])->toBe('john@example.com')
        ->and($activity->properties['attributes']['email'])->toBe('jane@example.com')
        ->and($activity->properties['old']['status'])->toBe('active')
        ->and($activity->properties['attributes']['status'])->toBe('inactive');
});

test('user model does not log changes to non-tracked fields', function () {
    $user = User::factory()->create([
        'phone_number' => '1234567890',
        'status' => 'active', // Explicitly set status to avoid default value logging
    ]);

    $user->update(['phone_number' => '0987654321']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    // No activity should be logged for non-tracked fields
    expect($activity)->toBeNull();
});

test('user model does not create log entry when no tracked fields change', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'phone_number' => '1234567890',
        'status' => 'active', // Explicitly set status to avoid default value logging
    ]);

    // Update only non-tracked field
    $user->update(['phone_number' => '0987654321']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    // No activity should be logged
    expect($activity)->toBeNull();
});

test('user model uses correct log name', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    $user->update(['name' => 'Jane Doe']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    expect($activity->log_name)->toBe('user');
});

test('user model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    $user->update(['name' => 'Jane Doe']);

    $activity = Activity::forSubject($user)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('John Doe')
        ->and($activity->properties['attributes']['name'])->toBe('Jane Doe');
});
