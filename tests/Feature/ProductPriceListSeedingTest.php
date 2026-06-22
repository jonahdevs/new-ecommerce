<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\File;

it('seeds each product with the status stamped in products.json', function () {
    $items = json_decode(File::get(database_path('data/products.json')), true);

    // products.json carries an explicit status for every item (published for the
    // approved e-commerce price-list SKUs, draft for the rest).
    expect(collect($items)->every(fn ($i) => isset($i['status'])))->toBeTrue();
    $expected = collect($items)->keyBy('sku');

    $this->seed([BrandSeeder::class, CategorySeeder::class, ProductSeeder::class]);

    Product::query()->get(['sku', 'status', 'price'])->each(function (Product $product) use ($expected) {
        $item = $expected->get($product->sku);

        expect($product->status->value)->toBe($item['status'])
            ->and($product->price)->toBe(
                $item['price'] === null ? null : (int) round(((float) $item['price']) * 100)
            );
    });

    // Every published priced product has a price; quote-only products (e.g. the
    // imported cold-room/laundry/healthcare items) may legitimately be published
    // with no price. The catalog has both published and draft states.
    expect(
        Product::where('status', ProductStatus::PUBLISHED)
            ->where('requires_quotation', false)
            ->whereNull('price')
            ->count()
    )->toBe(0)
        ->and(Product::where('status', ProductStatus::PUBLISHED)->count())->toBeGreaterThan(0)
        ->and(Product::where('status', ProductStatus::DRAFT)->count())->toBeGreaterThan(0);
});
