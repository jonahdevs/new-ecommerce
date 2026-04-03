<?php

namespace App\Observers;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class BrandObserver
{
    public function saved(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }

    public function deleted(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }

    public function forceDeleted(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }
}
