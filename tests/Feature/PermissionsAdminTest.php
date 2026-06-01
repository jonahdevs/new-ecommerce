<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());
});

// Permissions are code-defined and developer-only; the admin UI is read-only.

it('loads the permissions admin index', function () {
    $this->get(route('admin.permissions.index'))->assertOk();
});

it('filters permissions by group', function () {
    Livewire::test('pages::admin.permissions.index')
        ->set('filterGroup', 'orders')
        ->assertSee('orders.view')
        ->assertDontSee('roles.manage');
});
