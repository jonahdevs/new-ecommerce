<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\QuoteStatus;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Support\StorefrontSession;
use Livewire\Livewire;

beforeEach(function () {
    $this->brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $this->cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
});

it('renders for guests', function () {
    $this->get(route('quote.request'))->assertOk()->assertSee('Request a quote');
});

it('prefills line items from the cart', function () {
    StorefrontSession::addToCart('wok-range', 3);

    Livewire::test('pages::storefront.request-quote')
        ->assertSet('items', ['wok-range' => 3])
        ->assertSee('Wok Range');
});

it('seeds a product passed via the query string', function () {
    Livewire::withQueryParams(['product' => 'wok-range'])
        ->test('pages::storefront.request-quote')
        ->assertSet('items', ['wok-range' => 1])
        ->assertSee('Wok Range');
});

it('lets a guest submit a quote request', function () {
    StorefrontSession::addToCart('wok-range', 2);

    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $quote = Quote::first();

    expect($quote)->not->toBeNull()
        ->and($quote->user_id)->toBeNull()
        ->and($quote->contact_email)->toBe('jane@example.com')
        ->and($quote->status)->toBe(QuoteStatus::DRAFT)
        ->and($quote->total_cents)->toBe(0)
        ->and($quote->items)->toHaveCount(1);
});

it('stores no pricing on a request — staff price it later', function () {
    StorefrontSession::addToCart('wok-range', 2);

    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    $item = Quote::first()->items->first();

    expect($item->unit_price_cents)->toBe(0)
        ->and($item->line_total_cents)->toBe(0);
});

it('does not display any prices on the request page', function () {
    StorefrontSession::addToCart('wok-range', 2);

    Livewire::test('pages::storefront.request-quote')
        ->assertSee('Wok Range')
        ->assertDontSee('Indicative total')
        ->assertSee('No pricing yet')
        ->assertDontSee('3,000'); // catalog total (2 × 1,500) must never surface
});

it('auto-populates contact details for authenticated users', function () {
    $user = User::factory()->create(['name' => 'Anita Wanjiru', 'email' => 'anita@example.com']);
    $this->actingAs($user);

    Livewire::test('pages::storefront.request-quote')
        ->assertSet('contact_name', 'Anita Wanjiru')
        ->assertSet('contact_email', 'anita@example.com');
});

it('ties a submitted quote to the authenticated user with their contact details', function () {
    $user = User::factory()->create(['email' => 'anita@example.com']);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.request-quote')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.quotes.index'));

    $quote = Quote::first();

    expect($quote->user_id)->toBe($user->id)
        ->and($quote->contact_email)->toBe('anita@example.com');
});

it('auto-generates a title from the contact details', function () {
    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->set('contact_company', 'Acme Catering')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Quote::first()->title)->toBe('Quote request — Acme Catering');
});

it('requires contact details', function () {
    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', '')
        ->set('contact_email', '')
        ->call('submit')
        ->assertHasErrors(['contact_name', 'contact_email']);
});

it('shows catalog products in the picker without searching', function () {
    Livewire::test('pages::storefront.request-quote')
        ->assertSet('itemSearch', '')
        ->assertSee('Wok Range');
});

it('adds and removes items via the picker', function () {
    Livewire::test('pages::storefront.request-quote')
        ->call('addItem', 'wok-range')
        ->assertSet('items', ['wok-range' => 1])
        ->call('incrementItem', 'wok-range')
        ->assertSet('items', ['wok-range' => 2])
        ->call('removeItem', 'wok-range')
        ->assertSet('items', []);
});

it('loads more picker results with infinite scroll', function () {
    foreach (range(1, 20) as $n) {
        Product::create([
            'name' => "Filler Product {$n}", 'slug' => "filler-{$n}", 'sku' => "FL-{$n}",
            'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
            'type' => 'simple', 'price' => 1000, 'sort_order' => 0,
            'stock_status' => StockStatus::IN_STOCK->value,
            'visibility' => ProductVisibility::VISIBLE->value,
        ]);
    }

    // Highest sort_order — always last, so it falls onto the second page.
    Product::create([
        'name' => 'Last Catalog Item', 'slug' => 'last-item', 'sku' => 'LAST-1',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'simple', 'price' => 1000, 'sort_order' => 100000,
        'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);

    Livewire::test('pages::storefront.request-quote')
        ->assertSet('itemsPerPage', 18)
        ->assertDontSee('Last Catalog Item')
        ->call('loadMoreItems')
        ->assertSet('itemsPerPage', 30)
        ->assertSee('Last Catalog Item');
});

it('resets picker pagination when the search changes', function () {
    Livewire::test('pages::storefront.request-quote')
        ->call('loadMoreItems')
        ->assertSet('itemsPerPage', 30)
        ->set('itemSearch', 'wok')
        ->assertSet('itemsPerPage', 18);
});
