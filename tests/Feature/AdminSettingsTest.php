<?php

use App\Models\User;
use App\Settings\GeneralSettings;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ── General Settings ────────────────────────────────────────────────────────

test('admin can view settings page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.index'))
        ->assertOk();
});

test('admin can save general settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.index')
        ->set('site_name', 'Test Store')
        ->set('contact_email', 'test@example.com')
        ->call('saveGeneral')
        ->assertHasNoErrors();

    expect(app(GeneralSettings::class)->site_name)->toBe('Test Store');
    expect(app(GeneralSettings::class)->contact_email)->toBe('test@example.com');
});

test('general settings validates required site name', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.index')
        ->set('site_name', '')
        ->call('saveGeneral')
        ->assertHasErrors(['site_name' => 'required']);
});

test('admin can save localisation settings', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.index')
        ->set('currency', 'USD')
        ->set('timezone', 'UTC')
        ->call('saveLocalisation')
        ->assertHasNoErrors();

    expect(app(GeneralSettings::class)->currency)->toBe('USD');
    expect(app(GeneralSettings::class)->timezone)->toBe('UTC');
});

test('admin can toggle maintenance mode', function () {
    $this->actingAs($this->admin);

    $settings = app(GeneralSettings::class);
    $initial = $settings->maintenance_mode;

    Livewire::test('pages::admin.settings.index')
        ->call('toggleMaintenance');

    expect(app(GeneralSettings::class)->maintenance_mode)->toBe(! $initial);
});

// ── Staff Management ─────────────────────────────────────────────────────────

test('admin can view staff page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.staff.index'))
        ->assertOk();
});

test('admin can invite a new staff member', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('openCreate')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('role', 'staff')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'jane@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('staff'))->toBeTrue();
});

test('invite validates unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('openCreate')
        ->set('name', 'Test')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('admin can edit a staff member role', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('openEdit', $staff->id)
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect($staff->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin cannot remove themselves', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('remove', $this->admin->id);

    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can revoke staff access', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.staff.index')
        ->call('remove', $staff->id);

    expect($staff->fresh()->hasAnyRole(['admin', 'staff']))->toBeFalse();
});
