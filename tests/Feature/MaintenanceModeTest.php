<?php

use App\Models\User;
use App\Settings\MaintenanceSettings;
use Spatie\Permission\Models\Role;

it('serves the storefront normally when maintenance mode is off', function () {
    $this->get(route('home'))->assertOk();
});

it('shows the maintenance page to guests when maintenance mode is on', function () {
    app(MaintenanceSettings::class)->fill([
        'maintenance_mode' => true,
        'maintenance_message' => 'Back online at noon.',
    ])->save();

    $this->get(route('home'))
        ->assertStatus(503)
        ->assertSee('Back online at noon.');
});

it('keeps the login route reachable during maintenance', function () {
    app(MaintenanceSettings::class)->fill([
        'maintenance_mode' => true,
        'maintenance_message' => 'Down for now.',
    ])->save();

    $this->get('/login')->assertOk();
});

it('lets an admin browse the storefront during maintenance', function () {
    app(MaintenanceSettings::class)->fill([
        'maintenance_mode' => true,
        'maintenance_message' => 'Down for now.',
    ])->save();

    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin', 'web'));

    $this->actingAs($admin)->get(route('home'))->assertOk();
});
