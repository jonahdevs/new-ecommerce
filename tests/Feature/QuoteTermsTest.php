<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use App\Enums\QuoteStatus;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Settings\QuotationSettings;
use App\Support\StorefrontSession;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    app(QuotationSettings::class)->fill([
        'quotes_enabled' => true,
        'quote_terms' => '',
        'default_validity_days' => 30,
        'quote_prefix' => 'RFQ-',
    ])->save();

    $brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 150000,
        'status' => ProductStatus::PUBLISHED->value,
        'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
});

it('populates terms from global settings when a quote is submitted', function () {
    app(QuotationSettings::class)->fill(['quote_terms' => 'Payment due within 30 days. All prices exclude VAT.'])->save();

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Quote::first()->terms)->toBe('Payment due within 30 days. All prices exclude VAT.');
});

it('stores null terms when global setting is empty', function () {
    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Quote::first()->terms)->toBeNull();
});

it('stores customer notes separately from terms', function () {
    app(QuotationSettings::class)->fill(['quote_terms' => 'Standard terms apply.'])->save();

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->set('notes', 'Please deliver before Thursday.')
        ->call('submit')
        ->assertHasNoErrors();

    $quote = Quote::first();
    expect($quote->notes)->toBe('Please deliver before Thursday.')
        ->and($quote->terms)->toBe('Standard terms apply.');
});

it('lets admin update terms on a single quote', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $quote = Quote::factory()->create(['status' => QuoteStatus::DRAFT, 'terms' => 'Old terms.']);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->set('terms', 'Updated payment terms: 50% upfront.')
        ->call('save')
        ->assertHasNoErrors();

    expect($quote->fresh()->terms)->toBe('Updated payment terms: 50% upfront.');
});

it('resets terms to the global default via the reset action', function () {
    app(QuotationSettings::class)->fill(['quote_terms' => 'Global default terms.'])->save();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $quote = Quote::factory()->create(['status' => QuoteStatus::DRAFT, 'terms' => 'Custom per-quote terms.']);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('resetTermsToDefault')
        ->assertSet('terms', 'Global default terms.');
});
