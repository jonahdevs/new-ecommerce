<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\TaxClass;
use App\Models\User;
use App\Settings\LocalizationSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('no longer exposes virtual or downloadable as product types', function () {
    $values = array_column(ProductType::cases(), 'value');

    expect($values)->not->toContain('virtual')
        ->and($values)->not->toContain('downloadable');
});

it('saves the virtual and downloadable flags and derives requires_shipping', function () {
    Livewire::test('pages::admin.products.form')
        ->set('name', 'Installation Service')
        ->set('slug', 'installation-service')
        ->set('type', ProductType::SIMPLE->value)
        ->set('is_virtual', true)
        ->set('is_downloadable', true)
        ->call('save');

    $product = Product::firstWhere('slug', 'installation-service');

    expect($product)->not->toBeNull()
        ->and($product->is_virtual)->toBeTrue()
        ->and($product->is_downloadable)->toBeTrue()
        ->and($product->type)->toBe(ProductType::SIMPLE)
        ->and($product->requires_shipping)->toBeFalse();
});

it('keeps requires_shipping true for a physical, non-virtual product', function () {
    Livewire::test('pages::admin.products.form')
        ->set('name', 'Steel Wok')
        ->set('slug', 'steel-wok')
        ->set('type', ProductType::SIMPLE->value)
        ->set('is_virtual', false)
        ->set('is_downloadable', false)
        ->call('save');

    expect(Product::firstWhere('slug', 'steel-wok')->requires_shipping)->toBeTrue();
});

it('loads the flags when editing an existing product', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::DRAFT,
        'is_virtual' => true,
        'is_downloadable' => true,
        'requires_shipping' => false,
    ]);

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->assertSet('is_virtual', true)
        ->assertSet('is_downloadable', true);
});

it('snapshots the current store units onto a new product', function () {
    $settings = app(LocalizationSettings::class);
    $settings->weight_unit = 'g';
    $settings->dimension_unit = 'mm';
    $settings->save();

    Livewire::test('pages::admin.products.form')
        ->assertSet('weight_unit', 'g')
        ->assertSet('dimension_unit', 'mm')
        ->set('name', 'Bag of Bolts')
        ->set('slug', 'bag-of-bolts')
        ->call('save');

    $product = Product::firstWhere('slug', 'bag-of-bolts');

    expect($product->weight_unit)->toBe('g')
        ->and($product->dimension_unit)->toBe('mm');
});

it('keeps a product on its original units after the store default changes', function () {
    // Product created under kilograms / centimetres.
    $settings = app(LocalizationSettings::class);
    $settings->weight_unit = 'kg';
    $settings->dimension_unit = 'cm';
    $settings->save();

    Livewire::test('pages::admin.products.form')
        ->set('name', 'Cast Iron Pan')
        ->set('slug', 'cast-iron-pan')
        ->set('weight', 2.5)
        ->call('save');

    // Store later switches to grams / millimetres.
    $settings->weight_unit = 'g';
    $settings->dimension_unit = 'mm';
    $settings->save();

    $product = Product::firstWhere('slug', 'cast-iron-pan');

    expect($product->weight_unit)->toBe('kg')
        ->and($product->dimension_unit)->toBe('cm');

    // Re-opening the editor shows the snapshot units, not the new global ones.
    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->assertSet('weight_unit', 'kg')
        ->assertSet('dimension_unit', 'cm');
});

it('saves the assigned tax class on a product', function () {
    $class = TaxClass::create(['name' => 'Standard', 'slug' => 'standard', 'rate' => 16, 'is_active' => true]);

    Livewire::test('pages::admin.products.form')
        ->set('name', 'Taxed Item')
        ->set('slug', 'taxed-item')
        ->set('tax_class_id', $class->id)
        ->call('save');

    expect(Product::firstWhere('slug', 'taxed-item')->tax_class_id)->toBe($class->id);
});
