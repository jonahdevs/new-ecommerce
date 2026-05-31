<?php

use App\Models\Product;
use App\Models\TaxClass;
use App\Settings\TaxSettings;
use App\Support\TaxCalculator;

function configureTax(array $attributes): void
{
    $settings = app(TaxSettings::class);

    foreach ($attributes as $key => $value) {
        $settings->{$key} = $value;
    }

    $settings->save();
}

/** Create a tax class at the given rate and make it the store default. */
function setDefaultTaxClass(float $rate): TaxClass
{
    $class = TaxClass::updateOrCreate(
        ['slug' => 'default-test'],
        ['name' => 'Default', 'rate' => $rate, 'is_active' => true],
    );

    configureTax(['default_tax_class_id' => $class->id]);

    return $class;
}

it('falls back to the default tax class for a taxable product without its own', function () {
    configureTax(['tax_enabled' => true]);
    setDefaultTaxClass(16.0);

    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => null]);

    expect(app(TaxCalculator::class)->rateForProduct($product))->toBe(16.0);
});

it('prefers the product tax class rate over the default', function () {
    configureTax(['tax_enabled' => true]);
    setDefaultTaxClass(16.0);

    $class = TaxClass::create(['name' => 'Zero', 'slug' => 'zero', 'rate' => 0, 'is_active' => true]);
    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => $class->id]);

    expect(app(TaxCalculator::class)->rateForProduct($product))->toBe(0.0);
});

it('returns a zero rate when there is no default tax class', function () {
    configureTax(['tax_enabled' => true, 'default_tax_class_id' => null]);

    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => null]);

    expect(app(TaxCalculator::class)->rateForProduct($product))->toBe(0.0);
});

it('returns a zero rate for a non-taxable product', function () {
    configureTax(['tax_enabled' => true]);
    setDefaultTaxClass(16.0);

    $product = Product::factory()->create(['is_taxable' => false]);

    expect(app(TaxCalculator::class)->rateForProduct($product))->toBe(0.0);
});

it('returns a zero rate when tax is disabled store-wide', function () {
    configureTax(['tax_enabled' => false]);
    setDefaultTaxClass(16.0);

    $product = Product::factory()->create(['is_taxable' => true]);

    expect(app(TaxCalculator::class)->rateForProduct($product))->toBe(0.0);
});

it('extracts embedded VAT when prices include tax', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => true]);

    // 11,600 inclusive of 16% → 1,600 tax, 10,000 net.
    expect(app(TaxCalculator::class)->taxForLine(11600, 16.0))->toBe(1600);
});

it('adds VAT on top when prices exclude tax', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => false]);

    expect(app(TaxCalculator::class)->taxForLine(10000, 16.0))->toBe(1600);
});

it('leaves the display price unchanged when storage and display modes match', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => true, 'price_display' => 'including']);

    $product = Product::factory()->create(['is_taxable' => true]);

    expect(app(TaxCalculator::class)->displayPriceCents($product, 11600))->toBe(11600);
});

it('strips tax for display when prices are stored inclusive but shown excluding', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => true, 'price_display' => 'excluding']);
    setDefaultTaxClass(16.0);

    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => null]);

    // 11,600 incl → 10,000 net.
    expect(app(TaxCalculator::class)->displayPriceCents($product, 11600))->toBe(10000);
});

it('adds tax for display when prices are stored excluding but shown including', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => false, 'price_display' => 'including']);
    setDefaultTaxClass(16.0);

    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => null]);

    expect(app(TaxCalculator::class)->displayPriceCents($product, 10000))->toBe(11600);
});

it('does not convert the display price of a non-taxable product', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => true, 'price_display' => 'excluding']);
    setDefaultTaxClass(16.0);

    $product = Product::factory()->create(['is_taxable' => false]);

    expect(app(TaxCalculator::class)->displayPriceCents($product, 11600))->toBe(11600);
});

it('sums tax across mixed-rate cart lines', function () {
    configureTax(['tax_enabled' => true, 'prices_include_tax' => false]);
    setDefaultTaxClass(16.0);

    $standard = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => null]);
    $exempt = Product::factory()->create(['is_taxable' => false]);

    $lines = [
        ['product' => $standard, 'line_total_cents' => 10000], // 1,600 tax
        ['product' => $exempt, 'line_total_cents' => 5000],    // 0 tax
    ];

    expect(app(TaxCalculator::class)->taxForCart($lines))->toBe(1600);
});
