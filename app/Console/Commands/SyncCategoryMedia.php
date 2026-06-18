<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('categories:sync-media {--fresh : Clear existing category media before syncing}')]
#[Description('Backfill category images (banner/square/icon) from legacy columns into Media Library, generating all conversions.')]
class SyncCategoryMedia extends Command
{
    /** @var array<string, string> legacy column => media collection */
    private const MAP = [
        'image' => 'banner',
        'thumbnail' => 'square',
        'icon' => 'icon',
    ];

    public function handle(): int
    {
        $added = 0;
        $cleared = 0;
        $fresh = (bool) $this->option('fresh');

        Category::query()->each(function (Category $category) use (&$added, &$cleared, $fresh) {
            foreach (self::MAP as $column => $collection) {
                if ($fresh && $category->getFirstMedia($collection)) {
                    $category->clearMediaCollection($collection);
                    $cleared++;
                }

                $path = $category->getAttribute($column);

                if (! $path || ! Storage::disk('public')->exists($path)) {
                    continue;
                }

                if ($category->getFirstMedia($collection)) {
                    continue;
                }

                $category->addMediaFromDisk($path, 'public')
                    ->preservingOriginal()
                    ->toMediaCollection($collection);

                $added++;
            }
        });

        $this->info("Category media synced — added {$added}".($fresh ? ", cleared {$cleared}" : '').'.');

        return self::SUCCESS;
    }
}
