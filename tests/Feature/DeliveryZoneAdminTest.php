<?php

use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('loads the delivery zones admin page', function () {
    $this->get(route('admin.delivery-zones'))->assertOk();
});

it('creates a delivery zone with a fee entered in KES', function () {
    Livewire::test('pages::admin.delivery-zones')
        ->call('openCreateZone')
        ->set('name', 'Westlands')
        ->set('county', 'Nairobi')
        ->set('center_lat', -1.2649)
        ->set('center_lng', 36.8025)
        ->set('radius_meters', 4500)
        ->set('base_fee', 350)
        ->set('eta_label', 'Same day')
        ->call('saveZone')
        ->assertHasNoErrors()
        ->assertSet('showZoneModal', false);

    $zone = DeliveryZone::firstWhere('name', 'Westlands');

    expect($zone)->not->toBeNull()
        ->and($zone->base_fee_cents)->toBe(35000)
        ->and($zone->radius_meters)->toBe(4500);
});

it('requires a map pin when creating a zone', function () {
    Livewire::test('pages::admin.delivery-zones')
        ->call('openCreateZone')
        ->set('name', 'No Pin')
        ->set('base_fee', 100)
        ->call('saveZone')
        ->assertHasErrors(['center_lat', 'center_lng']);

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

it('creates a global free-delivery promotion', function () {
    Livewire::test('pages::admin.delivery-zones')
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
    Livewire::test('pages::admin.delivery-zones')
        ->call('openCreatePromo')
        ->set('pName', 'Bad promo')
        ->set('pScope', 'zone')
        ->set('pZoneId', null)
        ->call('savePromo')
        ->assertHasErrors('pZoneId');
});
