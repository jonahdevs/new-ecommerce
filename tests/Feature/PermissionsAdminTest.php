<?php

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    actingAsAdmin();
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

it('shows the roles each permission is assigned to', function () {
    $role = Role::firstWhere('name', 'admin');
    $permission = $role->permissions->first();

    Livewire::test('pages::admin.permissions.index')
        ->set('search', $permission->name)
        ->assertSee(Str::headline($role->name));
});
