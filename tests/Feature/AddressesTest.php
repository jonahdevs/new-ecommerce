<?php

use App\Models\Address;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the addresses page', function () {
    Livewire::test('pages::account.addresses.index')
        ->assertSee('Addresses');
});

it('creates an address with a pinned location and marks the first one default', function () {
    Livewire::test('pages::account.addresses.index')
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('name', 'Anita Wanjiru')
        ->set('line1', '12 Riverside Drive')
        ->set('latitude', -1.2921)
        ->set('longitude', 36.8219)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $address = $this->user->addresses()->first();

    expect($address)->not->toBeNull()
        ->and($address->is_default)->toBeTrue()
        ->and($address->name)->toBe('Anita Wanjiru')
        ->and($address->latitude)->toEqual(-1.2921)
        ->and($address->longitude)->toEqual(36.8219);
});

it('loads an existing address into the form for editing', function () {
    $address = Address::factory()->create(['user_id' => $this->user->id, 'label' => 'Home', 'line1' => '5 Old Lane']);

    Livewire::test('pages::account.addresses.index')
        ->call('openEdit', $address->id)
        ->assertSet('editingId', $address->id)
        ->assertSet('line1', '5 Old Lane')
        ->set('line1', '7 New Avenue')
        ->call('save')
        ->assertHasNoErrors();

    expect($address->fresh()->line1)->toBe('7 New Avenue');
});
