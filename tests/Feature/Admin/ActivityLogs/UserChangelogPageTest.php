<?php

use App\Models\User;
use App\Enums\UserStatus;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for User Changelog Page
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**
 * 
 * Tests the User changelog Livewire component that displays
 * change history for users.
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('user changelog page displays user changes', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'status' => UserStatus::ACTIVE]);

    // Delete any auto-generated activities from creation
    Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->delete();

    // Make a change to create activity log
    $user->update(['name' => 'Jane Doe']);

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->subject_id)->toBe($user->id)
        ->and($activities->first()->subject_type)->toBe(User::class);
});

test('user changelog page displays multiple changes', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com', 'status' => UserStatus::ACTIVE]);

    // Delete any auto-generated activities from creation
    Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->delete();

    // Make multiple changes
    $user->update(['name' => 'Jane Doe']);
    $user->update(['email' => 'jane@example.com']);
    $user->update(['status' => UserStatus::INACTIVE]);

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3);
});

test('user changelog page shows empty state when no changes', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    // Delete any auto-generated activities
    Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->delete();

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this user will appear here');
});

test('user changelog page displays causer information', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    $this->actingAs($this->admin);

    $user->update(['name' => 'Jane Doe']);

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $component->assertSee($this->admin->name)
        ->assertSee($this->admin->email);
});

test('user changelog page displays system changes', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    // Log out to simulate system change
    auth()->logout();

    $user->update(['name' => 'Jane Doe']);

    $this->actingAs($this->admin);

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $component->assertSee('System');
});

test('user changelog page throws 404 for non-existent user', function () {
    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test('admin.changelog.user-changelog', ['id' => 99999]);
});

test('user changelog page formats field labels correctly', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => UserStatus::ACTIVE,
    ]);

    $user->update([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'status' => UserStatus::INACTIVE,
    ]);

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $component->assertSee('Name:')
        ->assertSee('Email:')
        ->assertSee('Status:');
});

test('user changelog page formats enum values correctly', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'status' => UserStatus::ACTIVE,
    ]);

    $user->update([
        'status' => UserStatus::INACTIVE,
    ]);

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    // Check that enum labels are displayed (not raw values)
    $component->assertSee(UserStatus::ACTIVE->label())
        ->assertSee(UserStatus::INACTIVE->label());
});

test('user changelog page paginates results', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'status' => UserStatus::ACTIVE]);

    // Delete any auto-generated activities from creation
    Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->delete();

    // Create 25 changes (more than the 20 per page limit)
    for ($i = 0; $i < 25; $i++) {
        $user->update(['name' => "User {$i}"]);
    }

    $component = Livewire::test('admin.changelog.user-changelog', ['id' => $user->id]);

    $activities = $component->get('activities');

    // Should only show 20 per page
    expect($activities)->toHaveCount(20)
        ->and($activities->total())->toBe(25);
});
