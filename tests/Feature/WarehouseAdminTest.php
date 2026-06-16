<?php

use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the warehouse create and edit pages', function () {
    $warehouse = Warehouse::create([
        'name' => 'Main Depot',
        'slug' => 'main-depot',
        'address' => 'Industrial Area',
        'city' => 'Nairobi',
        'county' => 'Nairobi',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->get(route('admin.shipping.warehouses.create'))->assertOk();
    $this->get(route('admin.shipping.warehouses.edit', $warehouse))->assertOk();
});

it('creates a warehouse from the two-column form', function () {
    Livewire::test('pages::admin.shipping.warehouses.create')
        ->set('name', 'Mombasa Hub')
        ->set('address', 'Moi Avenue')
        ->set('city', 'Mombasa')
        ->set('county', 'Mombasa')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Warehouse::firstWhere('name', 'Mombasa Hub'))->not->toBeNull();
});
