<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());
});

it('loads the roles admin index', function () {
    $this->get(route('admin.roles.index'))->assertOk();
});

it('creates a role with selected permissions', function () {
    Livewire::test('pages::admin.roles.index')
        ->call('openCreate')
        ->set('name', 'fulfilment')
        ->set('selectedPermissions', ['orders.view', 'orders.manage'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $role = Role::firstWhere('name', 'fulfilment');

    expect($role)->not->toBeNull()
        ->and($role->permissions->pluck('name')->all())->toEqualCanonicalizing(['orders.view', 'orders.manage']);
});

it('updates the permissions on an existing role', function () {
    $role = Role::create(['name' => 'support', 'guard_name' => 'web']);
    $role->givePermissionTo('orders.view');

    Livewire::test('pages::admin.roles.index')
        ->call('openEdit', $role->id)
        ->assertSet('selectedPermissions', ['orders.view'])
        ->set('selectedPermissions', ['customers.view'])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['customers.view']);
});

it('rejects a duplicate role name', function () {
    Livewire::test('pages::admin.roles.index')
        ->call('openCreate')
        ->set('name', 'admin')
        ->call('save')
        ->assertHasErrors('name');
});

it('refuses to delete a protected role', function () {
    $admin = Role::firstWhere('name', 'admin');

    Livewire::test('pages::admin.roles.index')
        ->call('delete', $admin->id);

    expect(Role::find($admin->id))->not->toBeNull();
});

it('refuses to delete a role that still has members', function () {
    $role = Role::create(['name' => 'temp', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($role);

    Livewire::test('pages::admin.roles.index')
        ->call('delete', $role->id);

    expect(Role::find($role->id))->not->toBeNull();
});

it('deletes an unused custom role', function () {
    $role = Role::create(['name' => 'disposable', 'guard_name' => 'web']);

    Livewire::test('pages::admin.roles.index')
        ->call('delete', $role->id);

    expect(Role::find($role->id))->toBeNull();
});
