<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for Quote model changelog tracking
 *
 * **Validates: Requirements 1.4, 1.8, 1.9**
 *
 * Tests that the Quote model correctly tracks changes to:
 * - status
 * - admin_notes
 */
beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('quote model logs status changes', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000001',
        'status' => QuoteStatus::PENDING,
    ]);

    $quote->update(['status' => QuoteStatus::SENT]);

    $activity = Activity::forSubject($quote)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['status'])->toBe(QuoteStatus::PENDING->value)
        ->and($activity->properties['attributes']['status'])->toBe(QuoteStatus::SENT->value)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('quote model logs admin_notes changes', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000002',
        'admin_notes' => 'Original notes',
    ]);

    $quote->update(['admin_notes' => 'Updated notes']);

    $activity = Activity::forSubject($quote)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['admin_notes'])->toBe('Original notes')
        ->and($activity->properties['attributes']['admin_notes'])->toBe('Updated notes')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('quote model logs multiple field changes in single update', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000003',
        'status' => QuoteStatus::PENDING,
        'admin_notes' => 'Original notes',
    ]);

    $quote->update([
        'status' => QuoteStatus::SENT,
        'admin_notes' => 'Updated notes',
    ]);

    $activity = Activity::forSubject($quote)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['status', 'admin_notes'])
        ->and($activity->properties['attributes'])->toHaveKeys(['status', 'admin_notes'])
        ->and($activity->properties['old']['status'])->toBe(QuoteStatus::PENDING->value)
        ->and($activity->properties['attributes']['status'])->toBe(QuoteStatus::SENT->value)
        ->and($activity->properties['old']['admin_notes'])->toBe('Original notes')
        ->and($activity->properties['attributes']['admin_notes'])->toBe('Updated notes');
});

test('quote model does not log changes to non-tracked fields', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000004',
    ]);

    $quote->update(['expires_at' => now()->addDays(30)]);

    // No updated event for non-tracked field
    expect(Activity::forSubject($quote)->where('event', 'updated')->count())->toBe(0);
});

test('quote model does not create log entry when no tracked fields change', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000005',
        'status' => QuoteStatus::PENDING,
    ]);

    $quote->update(['expires_at' => now()->addDays(14)]);

    expect(Activity::forSubject($quote)->where('event', 'updated')->count())->toBe(0);
});

test('quote model uses correct log name', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000006',
        'status' => QuoteStatus::PENDING,
    ]);

    $quote->update(['status' => QuoteStatus::SENT]);

    $activity = Activity::forSubject($quote)->where('event', 'updated')->first();

    expect($activity->log_name)->toBe('quote');
});

test('quote model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000007',
        'status' => QuoteStatus::PENDING,
    ]);

    $quote->update(['status' => QuoteStatus::SENT]);

    $activity = Activity::forSubject($quote)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['status'])->toBe(QuoteStatus::PENDING->value)
        ->and($activity->properties['attributes']['status'])->toBe(QuoteStatus::SENT->value);
});
