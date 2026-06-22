<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;

it('seeds the imported quote products into their divisions with images', function () {
    // Full seed is expensive, so this single test asserts every facet of the import.
    $this->seed([BrandSeeder::class, CategorySeeder::class, ProductSeeder::class]);

    // 45 products were imported from the legacy site (SA-prefixed SKUs).
    $imported = Product::where('sku', 'like', 'SA%')->get();
    expect($imported)->toHaveCount(45);

    // They are published quote-only items with no price...
    expect($imported->every(fn (Product $p) => $p->status === ProductStatus::PUBLISHED))->toBeTrue()
        ->and($imported->every(fn (Product $p) => $p->requires_quotation === true))->toBeTrue()
        ->and($imported->every(fn (Product $p) => $p->price === null))->toBeTrue()
        // ...and every one carried a cover image.
        ->and($imported->every(fn (Product $p) => $p->getFirstMedia('images') !== null))->toBeTrue();

    // Each empty division now carries its 15 imported products, wired via primary_category_id.
    foreach (['cold-room', 'laundry', 'healthcare'] as $slug) {
        $division = Category::where('slug', $slug)->firstOrFail();

        expect(
            Product::where('primary_category_id', $division->id)
                ->where('sku', 'like', 'SA%')
                ->count()
        )->toBe(15);
    }
});
