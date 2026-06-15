<?php

use App\Models\Product;
use Livewire\Livewire;

/** Resolve the Featured product IDs the home component would render. */
function featuredIds(): array
{
    return Livewire::test('pages::storefront.home')
        ->instance()
        ->featuredProducts
        ->pluck('id')
        ->all();
}

it('shows products tagged Featured', function () {
    $tagged = Product::factory()->published()->create();
    $tagged->attachTag('Featured', 'feature');
    $untagged = Product::factory()->published()->create();

    $ids = featuredIds();

    expect($ids)->toContain($tagged->id)
        ->and($ids)->not->toContain($untagged->id);
});

it('falls back to other products when nothing is tagged Featured', function () {
    $product = Product::factory()->published()->create();

    expect(featuredIds())->toContain($product->id);
});
