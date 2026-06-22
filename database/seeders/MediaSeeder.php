<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

/**
 * Builds all image conversions (thumb, card, zoom, webp, lqip …) for media that
 * the other seeders attached with the conversion queue disabled for speed.
 *
 * Runs LAST in DatabaseSeeder so a single `migrate:fresh --seed` leaves every
 * product and category image fully converted — no manual `media-library:regenerate`
 * follow-up, and the storefront LQIP blur-up works immediately.
 *
 * Conversions are forced to run inline on the `sync` connection so their results
 * are persisted to media.generated_conversions. Media Library dispatches its jobs
 * onto `media-library.queue_connection_name` (not `queue.default`), so that is the
 * key that must be overridden — otherwise the jobs are merely parked in the
 * `database` queue and never run.
 */
class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $previous = [
            'queue.default' => Config::get('queue.default'),
            'media-library.queue_connection_name' => Config::get('media-library.queue_connection_name'),
            'media-library.queue_conversions_after_database_commit' => Config::get('media-library.queue_conversions_after_database_commit'),
        ];

        Config::set('queue.default', 'sync');
        Config::set('media-library.queue_connection_name', 'sync');
        // Dispatch immediately rather than waiting for a DB commit that never comes
        // when conversions run synchronously inside the seeder.
        Config::set('media-library.queue_conversions_after_database_commit', false);

        try {
            // Backfill the media library from legacy image sources for every model
            // (categories + products). --fresh clears existing media first so a
            // re-seed rebuilds conversions from a clean slate.
            $this->command->info('Syncing media…');
            Artisan::call('media:sync', ['--model' => 'all', '--fresh' => true], $this->command->getOutput());

            // Generate every conversion for all attached media (products + categories).
            $this->command->info('Generating image conversions (this can take a minute)…');
            Artisan::call('media-library:regenerate', [], $this->command->getOutput());
        } finally {
            foreach ($previous as $key => $value) {
                Config::set($key, $value);
            }
        }
    }
}
