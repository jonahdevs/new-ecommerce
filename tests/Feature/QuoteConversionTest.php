<?php

use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\TaxClass;
use App\Models\User;
use App\Settings\TaxSettings;
use Livewire\Livewire;

function applyTaxSettings(array $attributes): void
{
    $settings = app(TaxSettings::class);

    foreach ($attributes as $key => $value) {
        $settings->{$key} = $value;
    }

    $settings->save();
}

/** A quote with a single product line at the given total, owned by a fresh user. */
function quoteWithLine(int $lineCents, ?Product $product): Quote
{
    $quote = Quote::factory()->create([
        'user_id' => User::factory(),
        'status' => QuoteStatus::APPROVED,
        'total_cents' => $lineCents,
    ]);

    $quote->items()->create([
        'product_id' => $product?->id,
        'product_name' => $product?->name ?? 'Custom fabrication',
        'product_sku' => $product?->sku,
        'unit_price_cents' => $lineCents,
        'quantity' => 1,
        'line_total_cents' => $lineCents,
    ]);

    return $quote->load('items');
}

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('extracts VAT from the quote total when prices include tax', function () {
    applyTaxSettings(['tax_enabled' => true, 'prices_include_tax' => true]);
    $class = TaxClass::create(['name' => 'Standard', 'slug' => 'std-16', 'rate' => 16, 'is_active' => true]);
    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => $class->id]);

    $quote = quoteWithLine(116000, $product);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])->call('convertToOrder');

    $order = Order::first();

    expect($order->subtotal_cents)->toBe(116000)
        ->and($order->vat_cents)->toBe(16000)      // 116000 − 116000/1.16
        ->and($order->total_cents)->toBe(116000)   // inclusive: total stays the subtotal
        ->and((float) $order->items->first()->tax_rate)->toBe(16.0)
        ->and($order->items->first()->tax_cents)->toBe(16000);
});

it('adds VAT on top of the quote total when prices exclude tax', function () {
    applyTaxSettings(['tax_enabled' => true, 'prices_include_tax' => false]);
    $class = TaxClass::create(['name' => 'Standard', 'slug' => 'std-16', 'rate' => 16, 'is_active' => true]);
    $product = Product::factory()->create(['is_taxable' => true, 'tax_class_id' => $class->id]);

    $quote = quoteWithLine(100000, $product);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])->call('convertToOrder');

    $order = Order::first();

    expect($order->subtotal_cents)->toBe(100000)
        ->and($order->vat_cents)->toBe(16000)       // 100000 × 16%
        ->and($order->total_cents)->toBe(116000);
});

it('records zero VAT when tax is disabled', function () {
    applyTaxSettings(['tax_enabled' => false]);
    $product = Product::factory()->create(['is_taxable' => true]);

    $quote = quoteWithLine(100000, $product);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])->call('convertToOrder');

    $order = Order::first();

    expect($order->vat_cents)->toBe(0)
        ->and($order->total_cents)->toBe(100000);
});

it('falls back to the default tax rate for a manual line with no product', function () {
    $class = TaxClass::create(['name' => 'Standard', 'slug' => 'std-16', 'rate' => 16, 'is_active' => true]);
    applyTaxSettings(['tax_enabled' => true, 'prices_include_tax' => false, 'default_tax_class_id' => $class->id]);

    $quote = quoteWithLine(100000, null);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])->call('convertToOrder');

    $order = Order::first();

    expect($order->vat_cents)->toBe(16000)
        ->and((float) $order->items->first()->tax_rate)->toBe(16.0)
        ->and($order->items->first()->product_id)->toBeNull();
});
