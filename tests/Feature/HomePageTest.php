<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('renders the storefront home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Shop by category');
    $response->assertSee('Featured equipment');
    $response->assertSee('Just In');
    $response->assertSee('professionals trust');
    $response->assertSee('The Sheffield Quarterly');
});

it('renders the responsive header chrome', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    // Mobile menu trigger + slide-over drawer
    $response->assertSee('aria-label="Open menu"', false);
    $response->assertSee('aria-modal="true"', false);
    $response->assertSee('drawerOpen', false);
    // Primary nav links rendered (desktop bar + drawer)
    $response->assertSee('Request quote');
    $response->assertSee('Contact');
});

it('wires each hero slide to a working destination', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('href="'.route('catalog').'"', false);
    $response->assertSee('href="'.route('category.show', 'coffee-machines').'"', false);
    $response->assertSee('href="'.route('category.show', 'refrigeration').'"', false);
    $response->assertSee('href="'.route('category.show', 'bakery-preparation').'"', false);
    $response->assertSee('href="'.e(route('catalog', ['tag' => 'On Sale'])).'"', false);
});

it('shows a department card for each of the four divisions with a shop link', function () {
    Category::factory()->create(['name' => 'Commercial Kitchen', 'slug' => 'commercial-kitchen']);
    Category::factory()->create(['name' => 'Cold Room', 'slug' => 'cold-room']);
    Category::factory()->create(['name' => 'Laundry', 'slug' => 'laundry']);
    Category::factory()->create(['name' => 'Healthcare', 'slug' => 'healthcare']);

    // An unrelated top-level category must never appear in the band.
    Category::factory()->create(['name' => 'Random Top Level', 'slug' => 'random-top-level']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Shop by department');
    $response->assertSee('Shop Commercial Kitchen');
    $response->assertSee('Shop Cold Room');
    $response->assertSee('Shop Laundry');
    $response->assertSee('Shop Healthcare');
    $response->assertDontSee('Shop Random Top Level');
    // With no product imagery, each division renders a placeholder hero linking to its page.
    $response->assertSee('href="'.route('category.show', 'cold-room').'"', false);
});

it('fills a division card with product images from that division and its subcategories', function () {
    Storage::fake('public');

    $kitchen = Category::factory()->create(['name' => 'Commercial Kitchen', 'slug' => 'commercial-kitchen']);
    $ovens = Category::factory()->create(['name' => 'Ovens', 'slug' => 'ovens', 'parent_id' => $kitchen->id]);

    // Product lives in a subcategory of the division; its image should surface on the card.
    $product = Product::factory()->published()->create([
        'name' => 'Combi Oven 10 Grid',
        'primary_category_id' => $ovens->id,
    ]);
    $product->addMedia(UploadedFile::fake()->image('oven.jpg'))
        ->withCustomProperties(['is_cover' => true])
        ->toMediaCollection('images');

    $response = $this->get(route('home'));

    $response->assertOk();
    // The collage tile links to the product and shows its name.
    $response->assertSee('href="'.route('product.show', $product).'"', false);
    $response->assertSee('Combi Oven 10 Grid');
});

it('prioritises the LCP hero image and lazy-loads off-screen ones', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    // First hero slide + thin banner are above the fold → high priority.
    $response->assertSee('fetchpriority="high"', false);
    // Off-screen hero slides defer.
    $response->assertSee('loading="lazy"', false);
});

it('loads webfonts without a render-blocking chained @import', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('rel="preconnect" href="https://fonts.gstatic.com"', false);
    $response->assertSee('fonts.googleapis.com/css2', false);
});

it('serves the hero banner images from public/images/banners', function () {
    foreach (['topline', 'coffee-machines', 'refrigeration', 'bakery-prep', 'clearance-sale', 'thin-banner'] as $name) {
        expect(file_exists(public_path("images/banners/{$name}.webp")))
            ->toBeTrue("Missing /images/banners/{$name}.webp");
    }
});
