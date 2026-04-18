<?php

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Spatie\Activitylog\Models\Activity;
use Livewire\Livewire;

/**
 * Integration tests for Quote changelog page
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**
 * 
 * Tests that the Quote changelog page correctly:
 * - Displays activities in reverse chronological order
 * - Paginates results with 20 entries per page
 * - Shows timestamp, causer name, and field changes
 * - Displays "—" for null/missing values
 * - Enforces authorization
 */

beforeEach(function () {
    // Create the permission if it doesn't exist
    if (!\Spatie\Permission\Models\Permission::where('name', 'edit.quotes')->exists()) {
        \Spatie\Permission\Models\Permission::create(['name' => 'edit.quotes', 'guard_name' => 'web']);
    }

    // Create a staff user with quote edit permission
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    // Give the admin permission to edit quotes
    $this->admin->givePermissionTo('edit.quotes');

    $this->actingAs($this->admin);
});

test('quote changelog page displays activities in reverse chronological order', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000001']);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    // Create multiple changes
    sleep(1);
    $quote->update(['status' => QuoteStatus::SENT]);
    sleep(1);
    $quote->update(['admin_notes' => 'First note']);
    sleep(1);
    $quote->update(['admin_notes' => 'Second note']);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3)
        ->and($activities->first()->properties['attributes']['admin_notes'])->toBe('Second note')
        ->and($activities->last()->properties['attributes']['status'])->toBe(QuoteStatus::SENT->value);
});

test('quote changelog page paginates results with 20 entries per page', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000002']);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    // Create 25 changes
    for ($i = 1; $i <= 25; $i++) {
        $quote->update(['admin_notes' => "Note {$i}"]);
    }

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(20)
        ->and($activities->hasMorePages())->toBeTrue();
});

test('quote changelog page shows timestamp, causer name, and field changes', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000003',
        'status' => QuoteStatus::PENDING,
        'admin_notes' => 'Original notes',
    ]);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update(['status' => QuoteStatus::SENT, 'admin_notes' => 'Updated notes']);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $component->assertSee($this->admin->name)
        ->assertSee($this->admin->email)
        ->assertSee('Quote Status:')
        ->assertSee('Notes:')
        ->assertSee('Pending Review')
        ->assertSee('Quote Sent')
        ->assertSee('Original notes')
        ->assertSee('Updated notes');
});

test('quote changelog page displays dash for null values', function () {
    $quote = Quote::factory()->create(['admin_notes' => null]);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update(['admin_notes' => 'New notes']);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $component->assertSee('Notes:')
        ->assertSee('—');
});

test('quote changelog page displays System when causer is null', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000004']);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    // Log out to simulate system change
    auth()->logout();

    $quote->update(['status' => QuoteStatus::SENT]);

    // Verify activity was created
    $activity = Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull();

    // Log back in to view the changelog
    $this->actingAs($this->admin);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $component->assertSee('System');
});

test('quote changelog page shows empty state when no changes exist', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000005']);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this quote will appear here');
});

test('quote changelog page enforces authorization', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000006']);

    // Create a user without edit permission
    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($unauthorizedUser);

    Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id])
        ->assertForbidden();
});

test('quote changelog page returns 404 for non-existent quote', function () {
    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test('admin.changelog.quote-changelog', ['id' => 99999]);
});

test('quote changelog page formats field labels correctly', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000007',
        'status' => QuoteStatus::PENDING,
        'admin_notes' => 'Original notes',
    ]);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update([
        'status' => QuoteStatus::SENT,
        'admin_notes' => 'Updated notes',
    ]);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $component->assertSee('Quote Status:')
        ->assertSee('Notes:');
});

test('quote changelog page formats status values correctly', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::PENDING]);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update(['status' => QuoteStatus::SENT]);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    // Check that status formatting is applied (QuoteStatus::label())
    $component->assertSee('Quote Status:')
        ->assertSee('Pending Review')
        ->assertSee('Quote Sent');
});

test('quote changelog page displays multiple field changes in single activity', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000008',
        'status' => QuoteStatus::PENDING,
        'admin_notes' => 'Original notes',
    ]);

    // Clear any initial activity logs from quote creation
    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update([
        'status' => QuoteStatus::SENT,
        'admin_notes' => 'Updated notes',
    ]);

    $component = Livewire::test('admin.changelog.quote-changelog', ['id' => $quote->id]);

    $component->assertSee('Quote Status:')
        ->assertSee('Notes:')
        ->assertSee('Pending Review')
        ->assertSee('Quote Sent')
        ->assertSee('Original notes')
        ->assertSee('Updated notes');
});
