<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders each activity log page', function (string $logName) {
    $this->get(route('admin.activity.show', $logName))->assertOk();
})->with([
    'product',
    'product_variant',
    'order',
    'payment',
    'quote',
    'tax_class',
    'delivery_promotion',
    'shipping_method',
    'delivery_zone',
    'user',
    'category',
    'brand',
    'review',
    'page',
]);

it('aborts with 404 for an unknown log name', function () {
    $this->get(route('admin.activity.show', 'not_a_real_log'))->assertNotFound();
});

it('shows logged product activity in the table', function () {
    $product = Product::factory()->create(['name' => 'Original name']);
    $product->update(['name' => 'Renamed product']);

    $this->get(route('admin.activity.show', 'product'))
        ->assertOk()
        ->assertSee('Renamed product');
});

it('renders the per-item activity page scoped to one record', function () {
    $product = Product::factory()->create(['name' => 'Tracked widget', 'price' => 10000]);
    $product->update(['price' => 12000]);

    $other = Product::factory()->create(['name' => 'Unrelated widget']);
    $other->update(['price' => 99900]);

    $this->get(route('admin.activity.item', ['product', $product->id]))
        ->assertOk()
        ->assertSee('Tracked widget')
        ->assertDontSee('Unrelated widget');
});

it('aborts the per-item page for an unknown log name', function () {
    $this->get(route('admin.activity.item', ['not_a_real_log', 1]))->assertNotFound();
});

it('humanises money and enum values on the per-item page', function () {
    $product = Product::factory()->create(['price' => 1000000, 'status' => ProductStatus::DRAFT]);
    $product->update(['price' => 1500000, 'status' => ProductStatus::PUBLISHED]);

    $this->get(route('admin.activity.item', ['product', $product->id]))
        ->assertOk()
        ->assertSee('15,000')                          // formatted money, not raw cents
        ->assertDontSee('1500000')
        ->assertSee(ProductStatus::PUBLISHED->label()); // enum label, not the raw case value
});
