<?php

use App\Models\Showroom;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the showrooms index page', function () {
    $this->get(route('admin.showrooms.index'))->assertOk();
});

it('loads the create and edit pages', function () {
    $showroom = Showroom::factory()->create();

    $this->get(route('admin.showrooms.create'))->assertOk();
    $this->get(route('admin.showrooms.edit', $showroom))->assertOk();
});

it('creates a showroom with coordinates and stores comma-separated phones as an array', function () {
    Livewire::test('pages::admin.showrooms.create')
        ->set('city', 'Nakuru')
        ->set('country', 'Kenya')
        ->set('address', 'Kenyatta Avenue')
        ->set('phonesInput', '+254 700 111 222, +254 700 333 444')
        ->set('email', 'nakuru@store.com')
        ->set('latitude', '-0.303099')
        ->set('longitude', '36.080026')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $showroom = Showroom::firstWhere('city', 'Nakuru');

    expect($showroom)->not->toBeNull()
        ->and($showroom->phones)->toBe(['+254 700 111 222', '+254 700 333 444'])
        ->and($showroom->email)->toBe('nakuru@store.com')
        ->and($showroom->latitude)->toBe(-0.303099)
        ->and($showroom->longitude)->toBe(36.080026);
});

it('requires at least one phone number', function () {
    Livewire::test('pages::admin.showrooms.create')
        ->set('city', 'Eldoret')
        ->set('address', 'Uganda Road')
        ->set('phonesInput', '   ')
        ->call('save')
        ->assertHasErrors('phonesInput');

    expect(Showroom::count())->toBe(0);
});

it('rejects out-of-range coordinates', function () {
    Livewire::test('pages::admin.showrooms.create')
        ->set('city', 'Kisumu')
        ->set('address', 'Oginga Odinga Street')
        ->set('phonesInput', '+254 700 000 000')
        ->set('latitude', '120')
        ->call('save')
        ->assertHasErrors('latitude');

    expect(Showroom::count())->toBe(0);
});

it('edits a showroom and updates its coordinates', function () {
    $showroom = Showroom::factory()->create(['city' => 'Thika']);

    Livewire::test('pages::admin.showrooms.edit', ['showroom' => $showroom])
        ->set('city', 'Thika Town')
        ->set('latitude', '-1.0333')
        ->set('longitude', '37.0693')
        ->call('save')
        ->assertHasNoErrors();

    $showroom->refresh();
    expect($showroom->city)->toBe('Thika Town')
        ->and($showroom->latitude)->toBe(-1.0333)
        ->and($showroom->longitude)->toBe(37.0693);
});

it('deletes a showroom from the index', function () {
    $showroom = Showroom::factory()->create();

    Livewire::test('pages::admin.showrooms.index')
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
