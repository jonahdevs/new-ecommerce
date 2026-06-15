<?php

namespace App\Console\Commands;

use App\Jobs\ResolveAddressCounty;
use App\Models\Address;
use Illuminate\Console\Command;

class GeocodeAddressCounties extends Command
{
    protected $signature = 'addresses:geocode-counties {--all : Re-resolve every address, not just those missing a county}';

    protected $description = 'Reverse-geocode address pins to their county (backfill for the sales-by-county report).';

    public function handle(): int
    {
        $query = Address::whereNotNull('latitude');

        if (! $this->option('all')) {
            $query->whereNull('county');
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No addresses need geocoding.');

            return self::SUCCESS;
        }

        $this->info("Queueing {$count} address(es) for county resolution…");

        $query->select('id')->chunkById(200, function ($addresses): void {
            foreach ($addresses as $address) {
                ResolveAddressCounty::dispatch($address->id);
            }
        });

        $this->info('Done. Counties will populate as the queue processes.');

        return self::SUCCESS;
    }
}
