<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $permissions = [
        'edit.products',
        'edit.orders',
        'edit.quotes',
        'edit.users',
        'edit.categories',
        'edit.brands',
    ];

    foreach ($permissions as $permission) {
        if (! Permission::where('name', $permission)->exists()) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->admin->givePermissionTo($permissions);

    $this->actingAs($this->admin);
});

test('product listing page displays Change Log menu item', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    $response->assertSee('Change Log');
});

test('product Change Log menu item links to correct changelog page', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    $response->assertSee(route('admin.changelog', ['modelType' => 'product', 'id' => $product->id]));
});

test('product Change Log menu item uses clock icon with outline variant', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
});

test('order listing page displays Change Log menu item', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.sales.orders.index'));

    $response->assertSee('Change Log');
});

test('order Change Log menu item links to correct changelog page', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.sales.orders.index'));

    $response->assertSee(route('admin.changelog', ['modelType' => 'order', 'id' => $order->id]));
});

test('order Change Log menu item uses clock icon with outline variant', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.sales.orders.index'));

    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
});

test('quote listing page displays Change Log menu item', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.quotations.index'));

    $response->assertSee('Change Log');
});

test('quote Change Log menu item links to correct changelog page', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.quotations.index'));

    $response->assertSee(route('admin.changelog', ['modelType' => 'quote', 'id' => $quote->id]));
});

test('quote Change Log menu item uses clock icon with outline variant', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.quotations.index'));

    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
});

test('user listing page displays Change Log menu item', function () {
    $user = User::factory()->create();

    $response = $this->get(route('admin.access-control.roles.index'));

    $response->assertSee('Change Log');
});

test('user Change Log menu item links to correct changelog page', function () {
    $user = User::factory()->create(['is_staff' => true]);

    $response = $this->get(route('admin.access-control.roles.index'));

    $response->assertSee(route('admin.changelog', ['modelType' => 'user', 'id' => $user->id]));
});

test('user Change Log menu item uses clock icon with outline variant', function () {
    $user = User::factory()->create(['is_staff' => true]);

    $response = $this->get(route('admin.access-control.roles.index'));

    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
});

test('category listing page displays Change Log menu item', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.catalog.categories.index'));

    $response->assertSee('Change Log');
});

test('category Change Log menu item links to correct changelog page', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.catalog.categories.index'));

    $response->assertSee(route('admin.changelog', ['modelType' => 'category', 'id' => $category->id]));
});

test('category Change Log menu item uses clock icon with outline variant', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.catalog.categories.index'));

    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
});

test('brand listing page displays Change Log menu item', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.catalog.brands.index'));

    $response->assertSee('Change Log');
});

test('brand Change Log menu item links to correct changelog page', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.catalog.brands.index'));

    $response->assertSee(route('admin.changelog', ['modelType' => 'brand', 'id' => $brand->id]));
});

test('brand Change Log menu item uses clock icon with outline variant', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.catalog.brands.index'));

    $html = $response->getContent();

    expect($html)->toContain('Change Log')
        ->and($html)->toContain('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z');
});

test('Change Log menu items are separated from other menu items', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.catalog.products.index'));

    $html = $response->getContent();

    expect($html)->toContain('data-flux-menu-separator')
        ->and($html)->toContain('Change Log');
});

test('clicking Change Log link navigates to changelog page for product', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $response = $this->get(route('admin.changelog', ['modelType' => 'product', 'id' => $product->id]));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for order', function () {
    $order = Order::factory()->create();

    $response = $this->get(route('admin.changelog', ['modelType' => 'order', 'id' => $order->id]));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for quote', function () {
    $quote = Quote::factory()->create();

    $response = $this->get(route('admin.changelog', ['modelType' => 'quote', 'id' => $quote->id]));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for user', function () {
    $user = User::factory()->create();

    $response = $this->get(route('admin.changelog', ['modelType' => 'user', 'id' => $user->id]));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for category', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->get(route('admin.changelog', ['modelType' => 'category', 'id' => $category->id]));

    $response->assertStatus(200);
});

test('clicking Change Log link navigates to changelog page for brand', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $response = $this->get(route('admin.changelog', ['modelType' => 'brand', 'id' => $brand->id]));

    $response->assertStatus(200);
});
