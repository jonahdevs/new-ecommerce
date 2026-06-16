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
    actingAsAdmin();
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
        ->call('save')
        ->assertHasNoErrors();

    $quote->refresh();

    expect($quote->total_cents)->toBe(600000)
        ->and($quote->items)->toHaveCount(1)
        ->and($quote->items->first()->line_total_cents)->toBe(600000);
});

it('normalises a comma-masked unit price when pricing a quote', function () {
    $quote = Quote::factory()->create(['status' => QuoteStatus::DRAFT, 'total_cents' => 0]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('addBlankLine')
        ->set('lineItems.0.product_name', 'Walk-in freezer')
        ->set('lineItems.0.unit_price', '1,250,000.50')
        ->set('lineItems.0.quantity', 2)
        ->call('save')
        ->assertHasNoErrors();

    $quote->refresh();

    expect($quote->items->first()->unit_price_cents)->toBe(125000050)
        ->and($quote->total_cents)->toBe(250000100);
});

it('normalises a comma-masked unit price when creating a quote', function () {
    Livewire::test('pages::admin.quotes.create')
        ->call('addBlankLine')
        ->set('lineItems.0.product_name', 'Blast freezer')
        ->set('lineItems.0.unit_price', '2,500.00')
        ->set('lineItems.0.quantity', 1)
        ->call('create')
        ->assertHasNoErrors();

    expect(Quote::latest('id')->first()->items->first()->unit_price_cents)->toBe(250000);
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

it('keeps the product image and slug in the snapshot when an admin prices a quote', function () {
    $brand = Brand::create(['name' => 'ImgBrand', 'slug' => 'img-brand', 'is_active' => true, 'sort_order' => 1]);
    $category = Category::create(['name' => 'ImgCat', 'slug' => 'img-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $product = Product::create([
        'name' => 'Blast Chiller', 'slug' => 'blast-chiller', 'sku' => 'BC-1',
        'brand_id' => $brand->id, 'primary_category_id' => $category->id,
        'type' => 'simple', 'price' => 250000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    $product->images()->create(['path' => 'products/blast-chiller.jpg', 'is_cover' => true, 'sort_order' => 0]);

    $quote = Quote::factory()->create(['total_cents' => 0]);

    // Pricing a quote rebuilds every line item; the snapshot must retain the
    // cover image + slug so the "your quote is ready" email can show them.
    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('addProduct', $product->id)
        // The line item shows the product thumbnail before the name.
        ->assertSeeHtml('products/blast-chiller.jpg')
        ->set('lineItems.0.unit_price', 2500)
        ->call('save')
        ->assertHasNoErrors();

    $snapshot = $quote->fresh()->items->first()->product_snapshot;

    expect($snapshot['slug'])->toBe('blast-chiller')
        ->and($snapshot['cover_url'])->toContain('products/blast-chiller.jpg');
});

it('shows the product thumbnail in the admin create-quote line items', function () {
    $brand = Brand::create(['name' => 'NewBrand', 'slug' => 'new-brand', 'is_active' => true, 'sort_order' => 1]);
    $category = Category::create(['name' => 'NewCat', 'slug' => 'new-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $product = Product::create([
        'name' => 'Combi Oven', 'slug' => 'combi-oven', 'sku' => 'CO-1',
        'brand_id' => $brand->id, 'primary_category_id' => $category->id,
        'type' => 'simple', 'price' => 300000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    $product->images()->create(['path' => 'products/combi-oven.jpg', 'is_cover' => true, 'sort_order' => 0]);

    Livewire::test('pages::admin.quotes.create')
        ->call('addProduct', $product->id)
        ->assertSet('lineItems.0.product_name', 'Combi Oven')
        ->assertSeeHtml('products/combi-oven.jpg');
});

it('forbids a view-only user from approving a quote', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('quotes.view');
    $this->actingAs($viewer);

    $quote = Quote::factory()->create(['status' => QuoteStatus::AWAITING_APPROVAL]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('approve')
        ->assertForbidden();

    expect($quote->fresh()->status)->toBe(QuoteStatus::AWAITING_APPROVAL);
});
