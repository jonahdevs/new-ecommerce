<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\QuoteStatus;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());
});

it('loads the quotes admin index', function () {
    $this->get(route('admin.quotes.index'))->assertOk();
});

it('searches quotes by number and filters by status', function () {
    Quote::factory()->create(['quote_number' => 'RFQ-FINDME', 'status' => QuoteStatus::SENT]);
    Quote::factory()->create(['quote_number' => 'RFQ-OTHER', 'status' => QuoteStatus::APPROVED]);

    Livewire::test('pages::admin.quotes.index')
        ->set('search', 'FINDME')
        ->assertSee('RFQ-FINDME')
        ->assertDontSee('RFQ-OTHER')
        ->set('search', '')
        ->set('filterStatus', QuoteStatus::APPROVED->value)
        ->assertSee('RFQ-OTHER')
        ->assertDontSee('RFQ-FINDME');
});

it('creates a draft quote and redirects to it', function () {
    Livewire::test('pages::admin.quotes.index')
        ->call('createDraft')
        ->assertRedirect();

    $quote = Quote::first();

    expect($quote)->not->toBeNull()
        ->and($quote->status)->toBe(QuoteStatus::DRAFT)
        ->and($quote->quote_number)->toStartWith('RFQ-');
});

it('loads existing line items into the editable form', function () {
    $quote = Quote::factory()->create();
    QuoteItem::factory()->count(2)->create(['quote_id' => $quote->id]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->assertCount('lineItems', 2);
});

it('saves details and recomputes the total from line items', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::DRAFT, 'total_cents' => 0]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('addBlankLine')
        ->set('lineItems.0.product_name', 'Combi oven')
        ->set('lineItems.0.unit_price', 2000)
        ->set('lineItems.0.quantity', 3)
        ->set('title', 'Hotel fit-out')
        ->set('status', QuoteStatus::SENT->value)
        ->set('contact_email', 'buyer@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $quote->refresh();

    expect($quote->title)->toBe('Hotel fit-out')
        ->and($quote->status)->toBe(QuoteStatus::SENT)
        ->and($quote->contact_email)->toBe('buyer@example.com')
        ->and($quote->total_cents)->toBe(600000)
        ->and($quote->items)->toHaveCount(1)
        ->and($quote->items->first()->line_total_cents)->toBe(600000);
});

it('removes a line item from the editable set', function () {
    $quote = Quote::factory()->create();
    QuoteItem::factory()->count(2)->create(['quote_id' => $quote->id]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('removeLine', 0)
        ->assertCount('lineItems', 1);
});

it('adds a catalog product as a line item', function () {
    $brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $category = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $product = Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $brand->id, 'primary_category_id' => $category->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);

    $quote = Quote::factory()->create(['total_cents' => 0]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('addProduct', $product->id)
        ->assertCount('lineItems', 1)
        ->assertSet('lineItems.0.product_name', 'Wok Range')
        ->assertSet('lineItems.0.unit_price', 1500.0);
});

it('requires a title to save', function () {
    $quote = Quote::factory()->create();

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});
