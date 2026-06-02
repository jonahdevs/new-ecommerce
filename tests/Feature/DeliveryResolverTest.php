<?php

use App\Models\CarrierRate;
use App\Models\CarrierZone;
use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Services\DeliveryResolver;

beforeEach(function () {
    $this->resolver = app(DeliveryResolver::class);

    // One zone around Nairobi CBD, one Sheffield carrier, one Standard method.
    $this->zone = DeliveryZone::factory()->centeredAt(-1.2921, 36.8219, 5000)->create(['name' => 'Central', 'priority' => 0]);

    $this->carrier = ShippingCarrier::create([
        'name' => 'Sheffield', 'slug' => 'sheffield', 'driver' => 'self_managed',
        'priority' => 10, 'is_active' => true, 'sort_order' => 1,
    ]);

    $this->method = ShippingMethod::create([
        'name' => 'Standard Delivery', 'slug' => 'standard-delivery', 'type' => 'delivery', 'is_active' => true, 'sort_order' => 1,
    ]);

    CarrierZone::create(['carrier_id' => $this->carrier->id, 'delivery_zone_id' => $this->zone->id, 'is_active' => true]);

    CarrierRate::create([
        'carrier_id' => $this->carrier->id,
        'delivery_zone_id' => $this->zone->id,
        'shipping_method_id' => $this->method->id,
        'rate_type' => 'fixed',
        'base_rate_cents' => 50000,
        'free_over_cents' => null,
        'eta_label' => 'Same day',
        'is_active' => true,
        'sort_order' => 1,
    ]);
});

it('returns no zone when coordinates are missing', function () {
    expect($this->resolver->resolveZone(null, null))->toBeNull();
});

it('returns no zone when the pin is outside every zone', function () {
    expect($this->resolver->resolveZone(-4.0435, 39.6682))->toBeNull();
});

it('resolves the zone that contains the pin', function () {
    expect($this->resolver->resolveZone(-1.2921, 36.8219)?->id)->toBe($this->zone->id);
});

it('prefers the higher-priority zone when zones overlap', function () {
    $priority = DeliveryZone::factory()->centeredAt(-1.2921, 36.8219, 5000)->create([
        'name' => 'Priority', 'priority' => 10,
    ]);

    $c2 = ShippingCarrier::create([
        'name' => 'Sheffield2', 'slug' => 'sheffield2', 'driver' => 'self_managed',
        'priority' => 10, 'is_active' => true, 'sort_order' => 2,
    ]);
    CarrierZone::create(['carrier_id' => $c2->id, 'delivery_zone_id' => $priority->id, 'is_active' => true]);
    CarrierRate::create([
        'carrier_id' => $c2->id, 'delivery_zone_id' => $priority->id,
        'shipping_method_id' => $this->method->id,
        'rate_type' => 'fixed', 'base_rate_cents' => 50000, 'is_active' => true, 'sort_order' => 1,
    ]);

    expect($this->resolver->resolveZone(-1.2921, 36.8219)?->id)->toBe($priority->id);
});

it('ignores inactive zones', function () {
    $this->zone->update(['is_active' => false]);
    expect($this->resolver->resolveZone(-1.2921, 36.8219))->toBeNull();
});

it('is unserviceable without a zone', function () {
    $quote = $this->resolver->quote(null, $this->method, 100000);
    expect($quote->serviceable)->toBeFalse()->and($quote->feeCents)->toBe(0);
});

it('is unserviceable when no carrier rate exists for the method', function () {
    $other = ShippingMethod::create([
        'name' => 'Express', 'slug' => 'express', 'type' => 'delivery', 'is_active' => true, 'sort_order' => 2,
    ]);
    expect($this->resolver->quote($this->zone, $other, 100000)->serviceable)->toBeFalse();
});

it('quotes the base fee when no promotion applies', function () {
    $quote = $this->resolver->quote($this->zone, $this->method, 100000);
    expect($quote->serviceable)->toBeTrue()
        ->and($quote->feeCents)->toBe(50000)
        ->and($quote->isFree)->toBeFalse();
});

it('applies a global free promotion', function () {
    DeliveryPromotion::factory()->create(['name' => 'Launch free delivery']);
    $quote = $this->resolver->quote($this->zone, $this->method, 100000);
    expect($quote->feeCents)->toBe(0)
        ->and($quote->isFree)->toBeTrue()
        ->and($quote->promotionName)->toBe('Launch free delivery');
});

it('applies a flat-fee promotion', function () {
    DeliveryPromotion::factory()->flatFee(20000)->create();
    expect($this->resolver->quote($this->zone, $this->method, 100000)->feeCents)->toBe(20000);
});

it('applies a percent-off promotion', function () {
    DeliveryPromotion::factory()->percentOff(50)->create();
    expect($this->resolver->quote($this->zone, $this->method, 100000)->feeCents)->toBe(25000);
});

it('ignores expired promotions', function () {
    DeliveryPromotion::factory()->expired()->create();
    expect($this->resolver->quote($this->zone, $this->method, 100000)->feeCents)->toBe(50000);
});

it('honors the free-over threshold on the carrier rate', function () {
    CarrierRate::where('carrier_id', $this->carrier->id)
        ->where('delivery_zone_id', $this->zone->id)
        ->where('shipping_method_id', $this->method->id)
        ->update(['free_over_cents' => 90000]);

    expect($this->resolver->quote($this->zone, $this->method, 100000)->feeCents)->toBe(0)
        ->and($this->resolver->quote($this->zone, $this->method, 80000)->feeCents)->toBe(50000);
});

it('only applies a zone-scoped promotion to its own zone', function () {
    $other = DeliveryZone::factory()->centeredAt(-1.2921, 36.8219, 5000)->create(['name' => 'Other']);

    DeliveryPromotion::factory()->create([
        'name' => 'Other zone free', 'scope' => 'zone', 'zone_id' => $other->id,
    ]);

    expect($this->resolver->quote($this->zone, $this->method, 100000)->feeCents)->toBe(50000);
});
