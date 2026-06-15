<?php

namespace App\Jobs;

use App\Models\Address;
use App\Services\CountyResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Resolves an address pin to its county off the request cycle. Uses the local
 * GeoJSON boundary lookup (see CountyResolver) — no external API call.
 */
class ResolveAddressCounty implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $addressId) {}

    public function handle(CountyResolver $resolver): void
    {
        $address = Address::find($this->addressId);

        if (! $address || $address->latitude === null) {
            return;
        }

        $county = $resolver->countyFor((float) $address->latitude, (float) $address->longitude);

        if ($county !== null && $county !== $address->county) {
            // saveQuietly: the county change must not re-trigger the model hook.
            $address->county = $county;
            $address->saveQuietly();
        }
    }
}
