<?php

use App\Models\Showroom;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the showrooms admin page', function () {
    $this->get(route('admin.showrooms.index'))->assertOk();
});

it('creates a showroom and stores comma-separated phones as an array', function () {
    Livewire::test('pages::admin.showrooms')
        ->call('openCreate')
        ->set('city', 'Nakuru')
        ->set('country', 'Kenya')
        ->set('address', 'Kenyatta Avenue')
        ->set('phonesInput', '+254 700 111 222, +254 700 333 444')
        ->set('email', 'nakuru@store.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $showroom = Showroom::firstWhere('city', 'Nakuru');

    expect($showroom)->not->toBeNull()
        ->and($showroom->phones)->toBe(['+254 700 111 222', '+254 700 333 444'])
        ->and($showroom->email)->toBe('nakuru@store.com');
});

it('requires at least one phone number', function () {
    Livewire::test('pages::admin.showrooms')
        ->call('openCreate')
        ->set('city', 'Eldoret')
        ->set('address', 'Uganda Road')
        ->set('phonesInput', '   ')
        ->call('save')
        ->assertHasErrors('phonesInput');

    expect(Showroom::count())->toBe(0);
});

it('edits and deletes a showroom', function () {
    $showroom = Showroom::factory()->create(['city' => 'Thika']);

    Livewire::test('pages::admin.showrooms')
        ->call('openEdit', $showroom->id)
        ->set('city', 'Thika Town')
        ->call('save')
        ->assertHasNoErrors();
    expect($showroom->fresh()->city)->toBe('Thika Town');

    Livewire::test('pages::admin.showrooms')
        ->call('delete', $showroom->id);
    expect(Showroom::find($showroom->id))->toBeNull();
});

it('renders showrooms in the storefront footer', function () {
    Showroom::factory()->headquarters()->create([
        'city' => 'Nairobi',
        'country' => 'Kenya',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Nairobi')
        ->assertSee('Showrooms');
});
