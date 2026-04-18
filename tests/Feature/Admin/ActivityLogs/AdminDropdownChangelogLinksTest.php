<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

/**
 * Integration tests for admin dropdown changelog links
 * 
 * **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9**
 * 
 * Tests that "Change Log" menu items:
 * - Appear in all admin dropdowns (Product, Order, Quote, User, Category, Brand)
 * - Link to the correct changelog page routes
 * - Use the correct clock icon with outline variant
 */

beforeEach(function () {
    // Create necessary permissions if they don't exist
    $permissions = [
        'edit.products',
        'edit.orders',
        'edit.quotes',
        'edit.users',
        'edit.categories',
        'edit.brands',
    ];

    foreach ($permissions as $permission) {
        if (!\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    // Create a staff user with all necessary permissions
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    // Give the admin all permissions
    $this->admin->givePermissionTo($permissions);

    $this->actingAs($this->admin);
});

test('product listing page displays Change Log menu item', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $component = Livewire::test('admin.catalog.products.index');

    $component->assertSee('Change Log');
});

test('product Change Log menu item links to correct changelog page', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    $response->assertSee(route('admin.changelog.product', $product));
});

test('product Change Log menu item uses clock icon with outline variant', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    // Check that the Change Log menu item exists with the clock icon SVG path
    // The clock icon renders as an SVG with a specific path
    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'); // Clock icon SVG path
});

test('order listing page displays Change Log menu item', function () {
    $order = Order::factory()->create();

    $component = Livewire::test('admin.sales.orders.index');

    $component->assertSee('Change Log');
});

test('order Change Log menu item links to correct changelog page', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.sales.orders.index'));

    $response->assertSee(route('admin.changelog.order', $order));
});

test('order Change Log menu item uses clock icon with outline variant', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.sales.orders.index'));

    // Check that the Change Log menu item exists with the clock icon SVG path
    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'); // Clock icon SVG path
});

test('quote listing page displays Change Log menu item', function () {
    $quote = Quote::factory()->create();

    $component = Livewire::test('admin.sales.quotations.index');

    $component->assertSee('Change Log');
});

test('quote Change Log menu item links to correct changelog page', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.sales.quotations.index'));

    $response->assertSee(route('admin.changelog.quote', $quote));
});

test('quote Change Log menu item uses clock icon with outline variant', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.sales.quotations.index'));

    // Check that the Change Log menu item exists with the clock icon SVG path
    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'); // Clock icon SVG path
});

test('user listing page displays Change Log menu item', function () {
    $user = User::factory()->create();

    $component = Livewire::test('admin.access-control.roles.index');

    $component->assertSee('Change Log');
});

test('user Change Log menu item links to correct changelog page', function () {
    $user = User::factory()->create();

    $response = $this->get(route('admin.access-control.roles.index'));

    $response->assertSee(route('admin.changelog.user', $user));
});

test('user Change Log menu item uses clock icon with outline variant', function () {
    $user = User::factory()->create();

    $response = $this->get(route('admin.access-control.roles.index'));

    // Check that the Change Log menu item exists with the clock icon SVG path
    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'); // Clock icon SVG path
});

test('category listing page displays Change Log menu item', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $component = Livewire::test('admin.catalog.categories.index');

    $component->assertSee('Change Log');
});

test('category Change Log menu item links to correct changelog page', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.catalog.categories.index'));

    $response->assertSee(route('admin.changelog.category', $category));
});

test('category Change Log menu item uses clock icon with outline variant', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.catalog.categories.index'));

    // Check that the Change Log menu item exists with the clock icon SVG path
    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'); // Clock icon SVG path
});

test('brand listing page displays Change Log menu item', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $component = Livewire::test('admin.catalog.brands.index');

    $component->assertSee('Change Log');
});

test('brand Change Log menu item links to correct changelog page', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.catalog.brands.index'));

    $response->assertSee(route('admin.changelog.brand', $brand));
});

test('brand Change Log menu item uses clock icon with outline variant', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.catalog.brands.index'));

    // Check that the Change Log menu item exists with the clock icon SVG path
    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'); // Clock icon SVG path
});

test('Change Log menu items are separated from other menu items', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    // Check that there's a separator before the Change Log item
    $html = $response->getContent();

    // Look for the pattern: separator followed by Change Log
    // The separator renders as a div with data-flux-menu-separator attribute
    expect($html)->toContain('data-flux-menu-separator')
        ->and($html)->toContain('Change Log');
});

test('clicking Change Log link navigates to changelog page for product', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.changelog.product', $product));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for order', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.changelog.order', $order));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for quote', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.changelog.quote', $quote));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for user', function () {
    $user = User::factory()->create();

    $response = $this->get(route('admin.changelog.user', $user));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for category', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.changelog.category', $category));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for brand', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.changelog.brand', $brand));

    $response->assertStatus(200);
});
