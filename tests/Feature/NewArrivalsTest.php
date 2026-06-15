<?php

use App\Enums\StockStatus;
use App\Models\Product;
use Livewire\Livewire;

/** Resolve the New Arrivals product IDs the home component would render. */
function newArrivalIds(): array
{
    return Livewire::test('pages::storefront.home')
        ->instance()
        ->newArrivals
        ->pluck('id')
        ->all();
}

it('includes products published within the window', function () {
    $recent = Product::factory()->published()->create(['published_at' => now()->subDays(10)]);

    expect(newArrivalIds())->toContain($recent->id);
});

it('excludes products published before the window', function () {
    // A fresh product keeps the window populated so the fallback does not kick in.
    Product::factory()->published()->create(['published_at' => now()->subDays(5)]);
    $stale = Product::factory()->published()->create(['published_at' => now()->subDays(120)]);

    expect(newArrivalIds())->not->toContain($stale->id);
});

it('pins a tagged product even when it is older than the window', function () {
    Product::factory()->published()->create(['published_at' => now()->subDays(5)]);
    $pinned = Product::factory()->published()->create(['published_at' => now()->subDays(200)]);
    $pinned->syncTags(['New Arrival']);

    expect(newArrivalIds())->toContain($pinned->id);
});

it('falls back to the latest products when nothing is within the window', function () {
    $old = Product::factory()->published()->create(['published_at' => now()->subDays(300)]);

    expect(newArrivalIds())->toContain($old->id);
});

it('omits unpublished, out-of-stock, and unpriced products', function () {
    $draft = Product::factory()->create(['published_at' => now()]); // DRAFT status
    $oos = Product::factory()->published()->create([
        'published_at' => now(),
        'stock_status' => StockStatus::OUT_OF_STOCK->value,
    ]);
    $free = Product::factory()->published()->create(['published_at' => now(), 'price' => 0]);

    $ids = newArrivalIds();

    expect($ids)->not->toContain($draft->id)
        ->and($ids)->not->toContain($oos->id)
        ->and($ids)->not->toContain($free->id);
});
