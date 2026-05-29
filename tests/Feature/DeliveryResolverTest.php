<?php

use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use App\Services\DeliveryResolver;

beforeEach(function () {
    $this->resolver = app(DeliveryResolver::class);
    // A 5km zone centred on central Nairobi, base fee KES 500.
    $this->zone = DeliveryZone::factory()->centeredAt(-1.2921, 36.8219, 5000)->create([
        'name' => 'Central',
        'base_fee_cents' => 50000,
    ]);
});

it('returns no zone when coordinates are missing', function () {
    expect($this->resolver->resolveZone(null, null))->toBeNull();
});

it('returns no zone when the pin is outside every zone', function () {
    // Mombasa — far from Nairobi.
    expect($this->resolver->resolveZone(-4.0435, 39.6682))->toBeNull();
});

it('resolves the zone that contains the pin', function () {
    expect($this->resolver->resolveZone(-1.2921, 36.8219)?->id)->toBe($this->zone->id);
});

it('prefers the higher-priority zone when zones overlap', function () {
    $priority = DeliveryZone::factory()->centeredAt(-1.2921, 36.8219, 5000)->create([
        'name' => 'Priority',
        'priority' => 10,
    ]);

    expect($this->resolver->resolveZone(-1.2921, 36.8219)?->id)->toBe($priority->id);
});

it('ignores inactive zones', function () {
    $this->zone->update(['is_active' => false]);

    expect($this->resolver->resolveZone(-1.2921, 36.8219))->toBeNull();
});

it('quotes the base fee when no promotion applies', function () {
    $quote = $this->resolver->quote($this->zone, 100000);

    expect($quote->serviceable)->toBeTrue()
        ->and($quote->feeCents)->toBe(50000)
        ->and($quote->isFree)->toBeFalse();
});

it('is unserviceable without a zone', function () {
    $quote = $this->resolver->quote(null, 100000);

    expect($quote->serviceable)->toBeFalse()
        ->and($quote->feeCents)->toBe(0);
});

it('applies a global free promotion', function () {
    DeliveryPromotion::factory()->create(['name' => 'Launch free delivery']);

    $quote = $this->resolver->quote($this->zone, 100000);

    expect($quote->feeCents)->toBe(0)
        ->and($quote->isFree)->toBeTrue()
        ->and($quote->promotionName)->toBe('Launch free delivery');
});

it('applies a flat-fee promotion', function () {
    DeliveryPromotion::factory()->flatFee(20000)->create();

    expect($this->resolver->quote($this->zone, 100000)->feeCents)->toBe(20000);
});

it('applies a percent-off promotion', function () {
    DeliveryPromotion::factory()->percentOff(50)->create();

    expect($this->resolver->quote($this->zone, 100000)->feeCents)->toBe(25000);
});

it('ignores expired promotions', function () {
    DeliveryPromotion::factory()->expired()->create();

    expect($this->resolver->quote($this->zone, 100000)->feeCents)->toBe(50000);
});

it('honors the free-over threshold', function () {
    $this->zone->update(['free_over_cents' => 90000]);

    expect($this->resolver->quote($this->zone, 100000)->feeCents)->toBe(0)
        ->and($this->resolver->quote($this->zone, 80000)->feeCents)->toBe(50000);
});

it('only applies a zone-scoped promotion to its own zone', function () {
    $other = DeliveryZone::factory()->centeredAt(-1.2921, 36.8219, 5000)->create(['name' => 'Other']);
    DeliveryPromotion::factory()->create([
        'name' => 'Other zone free',
        'scope' => 'zone',
        'zone_id' => $other->id,
    ]);

    expect($this->resolver->quote($this->zone, 100000)->feeCents)->toBe(50000);
});
