<?php

use App\Models\County;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use App\Models\TownBoundary;
use App\Services\TownResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTown(array $ring, array $townAttrs = []): Town
{
    static $seq = 0;
    $seq++;

    $zone = ShippingZone::create(['name' => "Zone {$seq}", 'status' => 'active']);
    $county = County::create(['name' => "County {$seq}", 'code' => (string) (900 + $seq), 'shipping_zone_id' => $zone->id]);
    $subCounty = SubCounty::create(['name' => "SubCounty {$seq}", 'county_id' => $county->id]);

    $town = Town::create(array_merge([
        'name' => 'Test Town',
        'sub_county_id' => $subCounty->id,
        'county_id' => $county->id,
    ], $townAttrs));

    $lats = array_column($ring, 1);
    $lngs = array_column($ring, 0);

    TownBoundary::create([
        'town_id' => $town->id,
        'geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [$ring]]),
        'bbox_min_lat' => min($lats),
        'bbox_max_lat' => max($lats),
        'bbox_min_lng' => min($lngs),
        'bbox_max_lng' => max($lngs),
    ]);

    return $town;
}

it('resolves a point inside a town boundary', function () {
    // Simple square around Nairobi
    $ring = [
        [36.7, -1.4],
        [36.9, -1.4],
        [36.9, -1.2],
        [36.7, -1.2],
        [36.7, -1.4],
    ];

    $town = makeTown($ring, ['name' => 'Westlands Ward']);

    $resolved = app(TownResolver::class)->resolve(-1.3, 36.8);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($town->id)
        ->and($resolved->name)->toBe('Westlands Ward');
});

it('returns null for a point outside all boundaries', function () {
    $ring = [
        [36.7, -1.4],
        [36.9, -1.4],
        [36.9, -1.2],
        [36.7, -1.2],
        [36.7, -1.4],
    ];
    makeTown($ring);

    // Mombasa — far outside the ring
    $resolved = app(TownResolver::class)->resolve(-4.04, 39.67);

    expect($resolved)->toBeNull();
});

it('resolves the correct town when multiple boundaries exist', function () {
    $ringA = [
        [36.7, -1.4], [36.8, -1.4], [36.8, -1.3], [36.7, -1.3], [36.7, -1.4],
    ];
    $ringB = [
        [36.85, -1.4], [36.95, -1.4], [36.95, -1.3], [36.85, -1.3], [36.85, -1.4],
    ];

    $townA = makeTown($ringA, ['name' => 'Ward A']);
    $townB = makeTown($ringB, ['name' => 'Ward B']);

    expect(app(TownResolver::class)->resolve(-1.35, 36.75)?->id)->toBe($townA->id);
    expect(app(TownResolver::class)->resolve(-1.35, 36.90)?->id)->toBe($townB->id);
});

it('eager-loads town shipping zone relationships', function () {
    $zone = ShippingZone::create(['name' => 'Zone X', 'status' => 'active']);
    $county = County::create(['name' => 'County X', 'code' => '98', 'shipping_zone_id' => $zone->id]);
    $subCounty = SubCounty::create(['name' => 'SubCounty X', 'county_id' => $county->id]);
    $town = Town::create([
        'name' => 'Town X',
        'sub_county_id' => $subCounty->id,
        'county_id' => $county->id,
        'shipping_zone_id' => $zone->id,
    ]);

    $ring = [
        [36.7, -1.4], [36.9, -1.4], [36.9, -1.2], [36.7, -1.2], [36.7, -1.4],
    ];
    TownBoundary::create([
        'town_id' => $town->id,
        'geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [$ring]]),
        'bbox_min_lat' => -1.4,
        'bbox_max_lat' => -1.2,
        'bbox_min_lng' => 36.7,
        'bbox_max_lng' => 36.9,
    ]);

    $resolved = app(TownResolver::class)->resolve(-1.3, 36.8);

    expect($resolved)->not->toBeNull()
        ->and($resolved->relationLoaded('shippingZone'))->toBeTrue()
        ->and($resolved->shippingZone?->id)->toBe($zone->id);
});
