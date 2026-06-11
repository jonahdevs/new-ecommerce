<?php

use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    actingAsAdmin();
});

it('loads the customers admin index', function () {
    $this->get(route('admin.customers.index'))->assertOk();
});

it('lists customers but excludes staff and admins', function () {
    User::factory()->create(['name' => 'Jane Customer']);

    $staff = User::factory()->create(['name' => 'Bob Staff']);
    $staff->assignRole(Role::firstWhere('name', 'staff'));

    Livewire::test('pages::admin.customers.index')
        ->assertSee('Jane Customer')
        ->assertDontSee('Bob Staff');
});

it('shows aggregated order totals on the customer detail page', function () {
    $customer = User::factory()->create();
    Order::factory()->create(['user_id' => $customer->id, 'total_cents' => 500000]);
    Order::factory()->create(['user_id' => $customer->id, 'total_cents' => 250000]);

    Livewire::test('pages::admin.customers.show', ['customer' => $customer])
        ->assertSet('totalSpentCents', 750000)
        ->assertSee('7,500');
});
