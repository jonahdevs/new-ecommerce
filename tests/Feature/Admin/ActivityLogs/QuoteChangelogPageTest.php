<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    if (! Permission::where('name', 'edit.quotes')->exists()) {
        Permission::create(['name' => 'edit.quotes', 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->admin->givePermissionTo('edit.quotes');

    $this->actingAs($this->admin);
});

test('quote changelog page displays activities in reverse chronological order', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000001']);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    sleep(1);
    $quote->update(['status' => QuoteStatus::SENT]);
    sleep(1);
    $quote->update(['admin_notes' => 'First note']);
    sleep(1);
    $quote->update(['admin_notes' => 'Second note']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3)
        ->and($activities->first()->properties['attributes']['admin_notes'])->toBe('Second note')
        ->and($activities->last()->properties['attributes']['status'])->toBe(QuoteStatus::SENT->value);
});

test('quote changelog page paginates results with 20 entries per page', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000002']);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    for ($i = 1; $i <= 25; $i++) {
        $quote->update(['admin_notes' => "Note {$i}"]);
    }

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

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

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update(['status' => QuoteStatus::SENT, 'admin_notes' => 'Updated notes']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee($this->admin->name)
        ->assertSee($this->admin->email)
        ->assertSee('Status:')
        ->assertSee('Notes:')
        ->assertSee('Pending Review')
        ->assertSee('Quote Sent')
        ->assertSee('Original notes')
        ->assertSee('Updated notes');
});

test('quote changelog page displays dash for null values', function () {
    $quote = Quote::factory()->create(['admin_notes' => null]);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update(['admin_notes' => 'New notes']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee('Notes:')
        ->assertSee('—');
});

test('quote changelog page displays System when causer is null', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000004']);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    auth()->logout();

    $quote->update(['status' => QuoteStatus::SENT]);

    $activity = Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull();

    $this->actingAs($this->admin);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee('System');
});

test('quote changelog page shows empty state when no changes exist', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000005']);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this quote will appear here');
});

test('quote changelog page enforces authorization', function () {
    $quote = Quote::factory()->create(['reference' => 'QT-2026-000006']);

    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($unauthorizedUser);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id])
        ->assertForbidden();
});

test('quote changelog page returns 404 for non-existent quote', function () {
    $this->expectException(ModelNotFoundException::class);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => 99999]);
});

test('quote changelog page formats field labels correctly', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000007',
        'status' => QuoteStatus::PENDING,
        'admin_notes' => 'Original notes',
    ]);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update([
        'status' => QuoteStatus::SENT,
        'admin_notes' => 'Updated notes',
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee('Status:')
        ->assertSee('Notes:');
});

test('quote changelog page formats status values correctly', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::PENDING]);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update(['status' => QuoteStatus::SENT]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee('Status:')
        ->assertSee('Pending Review')
        ->assertSee('Quote Sent');
});

test('quote changelog page displays multiple field changes in single activity', function () {
    $quote = Quote::factory()->create([
        'reference' => 'QT-2026-000008',
        'status' => QuoteStatus::PENDING,
        'admin_notes' => 'Original notes',
    ]);

    Activity::where('subject_type', Quote::class)
        ->where('subject_id', $quote->id)
        ->delete();

    $quote->update([
        'status' => QuoteStatus::SENT,
        'admin_notes' => 'Updated notes',
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'quote', 'id' => $quote->id]);

    $component->assertSee('Status:')
        ->assertSee('Notes:')
        ->assertSee('Pending Review')
        ->assertSee('Quote Sent')
        ->assertSee('Original notes')
        ->assertSee('Updated notes');
});
