<?php

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('excludes staff members from the customer search results', function () {
    actingAsAdmin();

    $customer = User::factory()->create(['name' => 'Acme Catering', 'email' => 'buyer@acme.test']);

    $staff = User::factory()->create(['name' => 'Acme Staffer', 'email' => 'staff@acme.test']);
    $staff->assignRole('admin');

    $results = Livewire::test('pages::admin.quotes.create')
        ->set('customerSearch', 'Acme')
        ->get('customerResults');

    expect($results->pluck('id'))
        ->toContain($customer->id)
        ->not->toContain($staff->id);
});

it('refuses to select a staff member as the quote customer', function () {
    actingAsAdmin();

    $staff = User::factory()->create();
    $staff->assignRole('admin');

    Livewire::test('pages::admin.quotes.create')
        ->call('selectCustomer', $staff->id);
})->throws(ModelNotFoundException::class);

it('selects a real customer as the quote customer', function () {
    actingAsAdmin();

    $customer = User::factory()->create(['name' => 'Jane Buyer', 'email' => 'jane@buyer.test']);

    Livewire::test('pages::admin.quotes.create')
        ->call('selectCustomer', $customer->id)
        ->assertSet('selectedUserId', $customer->id)
        ->assertSet('contact_name', 'Jane Buyer')
        ->assertSet('contact_email', 'jane@buyer.test');
});
