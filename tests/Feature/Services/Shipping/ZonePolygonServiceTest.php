<?php

use App\Models\ShippingZone;
use App\Services\Shipping\ZonePolygonService;

// ─── pointInPolygon ──────────────────────────────────────────────────────────

describe('pointInPolygon', function () {
    beforeEach(function () {
        $this->service = new ZonePolygonService;

        // Unit square: lat -1→1, lng -1→1
        $this->square = [[-1, -1], [1, -1], [1, 1], [-1, 1]];
    });

    it('returns true for a point inside the polygon', function () {
        expect($this->service->pointInPolygon(0, 0, $this->square))->toBeTrue();
    });

    it('returns false for a point outside the polygon', function () {
        expect($this->service->pointInPolygon(0, 2, $this->square))->toBeFalse();
        expect($this->service->pointInPolygon(2, 0, $this->square))->toBeFalse();
        expect($this->service->pointInPolygon(-2, 0, $this->square))->toBeFalse();
    });

    it('returns false for a polygon with fewer than 3 vertices', function () {
        expect($this->service->pointInPolygon(0, 0, [[0, 0], [1, 1]]))->toBeFalse();
        expect($this->service->pointInPolygon(0, 0, []))->toBeFalse();
    });

    it('works with realistic Kenya coordinates', function () {
        // Rough bounding box around Nairobi CBD area
        $nairobiBox = [
            [-1.30, 36.80],
            [-1.25, 36.80],
            [-1.25, 36.85],
            [-1.30, 36.85],
        ];

        // Inside — near KICC
        expect($this->service->pointInPolygon(-1.28, 36.82, $nairobiBox))->toBeTrue();

        // Outside — Westlands
        expect($this->service->pointInPolygon(-1.26, 36.79, $nairobiBox))->toBeFalse();

        // Outside — Rongai
        expect($this->service->pointInPolygon(-1.40, 36.75, $nairobiBox))->toBeFalse();
    });
});

// ─── resolveByCoordinates ────────────────────────────────────────────────────

describe('resolveByCoordinates', function () {
    it('returns the matching zone when point falls inside its polygon', function () {
        $zone = ShippingZone::factory()->create([
            'status' => 'active',
            'geometry' => [[-1, -1], [1, -1], [1, 1], [-1, 1]],
        ]);

        $service = new ZonePolygonService;

        expect($service->resolveByCoordinates(0, 0)?->id)->toBe($zone->id);
    });

    it('returns null when point is outside all polygons', function () {
        ShippingZone::factory()->create([
            'status' => 'active',
            'geometry' => [[-1, -1], [1, -1], [1, 1], [-1, 1]],
        ]);

        $service = new ZonePolygonService;

        expect($service->resolveByCoordinates(5, 5))->toBeNull();
    });

    it('ignores zones with no geometry', function () {
        ShippingZone::factory()->create([
            'status' => 'active',
            'geometry' => null,
        ]);

        $service = new ZonePolygonService;

        expect($service->resolveByCoordinates(0, 0))->toBeNull();
    });

    it('ignores inactive zones', function () {
        ShippingZone::factory()->create([
            'status' => 'inactive',
            'geometry' => [[-1, -1], [1, -1], [1, 1], [-1, 1]],
        ]);

        $service = new ZonePolygonService;

        expect($service->resolveByCoordinates(0, 0))->toBeNull();
    });

    it('returns the zone with the lowest id when polygons overlap', function () {
        $first = ShippingZone::factory()->create(['status' => 'active', 'geometry' => [[-2, -2], [2, -2], [2, 2], [-2, 2]]]);
        $second = ShippingZone::factory()->create(['status' => 'active', 'geometry' => [[-1, -1], [1, -1], [1, 1], [-1, 1]]]);

        $service = new ZonePolygonService;

        // Both polygons contain (0, 0) — first created zone should win.
        expect($service->resolveByCoordinates(0, 0)?->id)->toBe($first->id);
    });
});
