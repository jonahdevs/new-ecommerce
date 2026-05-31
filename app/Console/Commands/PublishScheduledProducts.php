<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:publish-scheduled')]
#[Description('Publish scheduled products whose publish time has passed.')]
class PublishScheduledProducts extends Command
{
    public function handle(): int
    {
        $count = Product::query()
            ->where('status', ProductStatus::SCHEDULED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => ProductStatus::PUBLISHED]);

        $this->info($count === 0
            ? 'No scheduled products were due.'
            : "Published {$count} scheduled product(s).");

        return self::SUCCESS;
    }
}
