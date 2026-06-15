<?php

use App\Jobs\ResolveAddressCounty;
use App\Models\Address;
use App\Services\CountyResolver;

it('resolves Nairobi for a Nairobi coordinate', function () {
    expect(app(CountyResolver::class)->countyFor(-1.2921, 36.8219))->toBe('Nairobi');
});

it('resolves Mombasa for a Mombasa coordinate', function () {
    expect(app(CountyResolver::class)->countyFor(-4.0435, 39.6682))->toBe('Mombasa');
});

it('returns null for a coordinate outside Kenya', function () {
    // Gulf of Guinea (0,0) — not in any county polygon.
    expect(app(CountyResolver::class)->countyFor(0.0, 0.0))->toBeNull();
});

it('returns null when coordinates are missing', function () {
    expect(app(CountyResolver::class)->countyFor(null, null))->toBeNull();
});

it('persists the resolved county onto the address via the job', function () {
    $address = Address::factory()->create(['latitude' => -1.2921, 'longitude' => 36.8219, 'county' => null]);

    (new ResolveAddressCounty($address->id))->handle(app(CountyResolver::class));

    expect($address->fresh()->county)->toBe('Nairobi');
});
