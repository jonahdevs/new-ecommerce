<?php

use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the delivery zones admin page', function () {
    $this->get(route('admin.delivery-zones'))->assertOk();
});

it('creates a delivery zone', function () {
    $polygon = [
        ['lat' => -1.24, 'lng' => 36.75],
        ['lat' => -1.24, 'lng' => 36.85],
        ['lat' => -1.34, 'lng' => 36.85],
        ['lat' => -1.34, 'lng' => 36.75],
    ];

    Livewire::test('pages::admin.delivery-zones')
        ->call('openCreateZone')
        ->set('name', 'Westlands')
        ->set('county', 'Nairobi')
        ->set('polygon', $polygon)
        ->call('saveZone')
        ->assertHasNoErrors()
        ->assertSet('showZoneModal', false);

    expect(DeliveryZone::firstWhere('name', 'Westlands'))->not->toBeNull();
});

it('requires a polygon with at least 3 points when creating a zone', function () {
    Livewire::test('pages::admin.delivery-zones')
        ->call('openCreateZone')
        ->set('name', 'No Polygon')
        ->call('saveZone')
        ->assertHasErrors(['polygon']);

    expect(DeliveryZone::count())->toBe(0);
});

it('toggles and deletes a zone', function () {
    $zone = DeliveryZone::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin.delivery-zones')
        ->call('toggleZoneActive', $zone->id);
    expect($zone->fresh()->is_active)->toBeFalse();

    Livewire::test('pages::admin.delivery-zones')
        ->call('deleteZone', $zone->id);
    expect(DeliveryZone::find($zone->id))->toBeNull();
});

it('loads the delivery promotions admin page', function () {
    $this->get(route('admin.delivery-promotions'))->assertOk();
});

it('creates a global free-delivery promotion', function () {
    Livewire::test('pages::admin.delivery-promotions')
        ->call('openCreatePromo')
        ->set('pName', 'Launch free delivery')
        ->set('pScope', 'global')
        ->set('pEffect', 'free')
        ->call('savePromo')
        ->assertHasNoErrors();

    $promo = DeliveryPromotion::firstWhere('name', 'Launch free delivery');

    expect($promo)->not->toBeNull()
        ->and($promo->isLiveNow())->toBeTrue();
});

it('requires a zone for a zone-scoped promotion', function () {
    Livewire::test('pages::admin.delivery-promotions')
        ->call('openCreatePromo')
        ->set('pName', 'Bad promo')
        ->set('pScope', 'zone')
        ->set('pZoneId', null)
        ->call('savePromo')
        ->assertHasErrors('pZoneId');
});
