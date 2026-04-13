<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_staff' => true, 'email_verified_at' => now()]);

    Permission::firstOrCreate(['name' => 'create.products', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit.products', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['create.products', 'edit.products']);

    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->actingAs($this->admin);
});

// ─── Create page ──────────────────────────────────────────────────────────────

it('renders the create product page', function () {
    $this->get(route('admin.catalog.products.create'))
        ->assertOk()
        ->assertSee('Create New Product');
});

it('creates a product with valid data', function () {
    Livewire::test('pages::admin.catalog.products.create')
        ->set('form.name', 'New Test Product')
        ->set('form.price', '1500.00')
        ->set('form.sku', 'NTP-001')
        ->set('form.status', ProductStatus::PUBLISHED->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('products', [
        'name' => 'New Test Product',
        'sku' => 'NTP-001',
    ]);
});

it('auto-generates slug from name on create', function () {
    Livewire::test('pages::admin.catalog.products.create')
        ->set('form.name', 'My Awesome Product')
        ->set('form.price', '500')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'My Awesome Product',
        'slug' => 'my-awesome-product',
    ]);
});

it('uses a manually entered slug instead of auto-generating', function () {
    Livewire::test('pages::admin.catalog.products.create')
        ->set('form.name', 'My Product')
        ->set('form.slug', 'custom-slug')
        ->set('form.price', '100')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', ['slug' => 'custom-slug']);
});

it('fails validation when name is missing on create', function () {
    Livewire::test('pages::admin.catalog.products.create')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

it('fails validation when sku is duplicate on create', function () {
    Product::factory()->create(['sku' => 'DUP-SKU']);

    Livewire::test('pages::admin.catalog.products.create')
        ->set('form.name', 'Another Product')
        ->set('form.sku', 'DUP-SKU')
        ->call('save')
        ->assertHasErrors(['form.sku']);
});

it('fails validation when slug is duplicate on create', function () {
    Product::factory()->create(['slug' => 'existing-slug']);

    Livewire::test('pages::admin.catalog.products.create')
        ->set('form.name', 'Some Product')
        ->set('form.slug', 'existing-slug')
        ->call('save')
        ->assertHasErrors(['form.slug']);
});

// ─── Edit page ────────────────────────────────────────────────────────────────

it('renders the edit product page', function () {
    $product = Product::factory()->create(['name' => 'Editable Product']);

    $this->get(route('admin.catalog.products.edit', $product))
        ->assertOk()
        ->assertSee('Editable Product');
});

it('loads existing product data into the form', function () {
    $product = Product::factory()->create([
        'name' => 'Loaded Product',
        'slug' => 'loaded-product',
        'price' => '299.99',
        'sku' => 'LP-001',
        'status' => ProductStatus::DRAFT->value,
        'visibility' => ProductVisibility::PUBLIC->value,
        'type' => ProductType::SIMPLE->value,
    ]);

    Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
        ->assertSet('form.name', 'Loaded Product')
        ->assertSet('form.slug', 'loaded-product')
        ->assertSet('form.sku', 'LP-001')
        ->assertSet('form.status', ProductStatus::DRAFT->value)
        ->assertSet('form.type', ProductType::SIMPLE->value);
});

it('updates a product with valid data', function () {
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'price' => '100.00',
    ]);

    Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
        ->set('form.name', 'Updated Name')
        ->set('form.price', '199.99')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect($product->fresh()->name)->toBe('Updated Name');
    expect((float) $product->fresh()->price)->toBe(199.99);
});

it('fails validation when name is missing on edit', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

it('allows the same sku when editing the same product', function () {
    $product = Product::factory()->create(['sku' => 'SAME-SKU']);

    Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
        ->set('form.name', 'Updated Name')
        ->set('form.sku', 'SAME-SKU')
        ->call('save')
        ->assertHasNoErrors();
});

it('rejects a duplicate sku from a different product on edit', function () {
    Product::factory()->create(['sku' => 'TAKEN-SKU']);
    $product = Product::factory()->create(['sku' => 'MY-SKU']);

    Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
        ->set('form.sku', 'TAKEN-SKU')
        ->call('save')
        ->assertHasErrors(['form.sku']);
});

it('saves manage_stock and stock_quantity correctly', function () {
    $product = Product::factory()->create(['manage_stock' => false]);

    Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
        ->set('form.manage_stock', true)
        ->set('form.stock_quantity', 50)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->manage_stock)->toBeTrue();
    expect($product->fresh()->stock_quantity)->toBe(50);
});
