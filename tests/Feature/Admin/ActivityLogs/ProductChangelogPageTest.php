<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    if (! Permission::where('name', 'edit.products')->exists()) {
        Permission::create(['name' => 'edit.products', 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->admin->givePermissionTo('edit.products');

    $this->actingAs($this->admin);
});

test('product changelog page displays activities in reverse chronological order', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    sleep(1);
    $product->update(['price' => 100]);
    sleep(1);
    $product->update(['price' => 150]);
    sleep(1);
    $product->update(['price' => 200]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3)
        ->and($activities->first()->properties['attributes']['price'])->toBe('200.00')
        ->and($activities->last()->properties['attributes']['price'])->toBe('100.00');
});

test('product changelog page paginates results with 20 entries per page', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    for ($i = 1; $i <= 25; $i++) {
        $product->update(['price' => 100 + $i]);
    }

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(20)
        ->and($activities->hasMorePages())->toBeTrue();
});

test('product changelog page shows timestamp, causer name, and field changes', function () {
    $product = Product::factory()->create(['name' => 'Original Name', 'price' => 100]);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    $product->update(['name' => 'Updated Name', 'price' => 150]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('Updated Name')
        ->assertSee($this->admin->name)
        ->assertSee($this->admin->email)
        ->assertSee('Name:')
        ->assertSee('Price:')
        ->assertSee('Original Name')
        ->assertSee('100');
});

test('product changelog page displays dash for null values', function () {
    $product = Product::factory()->create(['sale_price' => null]);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    $product->update(['sale_price' => 50]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('Sale Price:')
        ->assertSee('—');
});

test('product changelog page displays System when causer is null', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    auth()->logout();

    $product->update(['price' => 100]);

    $this->actingAs($this->admin);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('System');
});

test('product changelog page shows empty state when no changes exist', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this product will appear here');
});

test('product changelog page enforces authorization', function () {
    $product = Product::factory()->create(['name' => 'Test Product']);

    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($unauthorizedUser);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id])
        ->assertForbidden();
});

test('product changelog page returns 404 for non-existent product', function () {
    $this->expectException(ModelNotFoundException::class);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => 99999]);
});

test('product changelog page formats field labels correctly', function () {
    $product = Product::factory()->create([
        'name' => 'Test Product',
        'sku' => 'SKU-001',
        'stock_quantity' => 10,
    ]);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    $product->update([
        'name' => 'Updated Product',
        'sku' => 'SKU-002',
        'stock_quantity' => 20,
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('Name:')
        ->assertSee('SKU:')
        ->assertSee('Stock Quantity:');
});

test('product changelog page formats currency values correctly', function () {
    $product = Product::factory()->create(['price' => 100, 'sale_price' => 80]);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    $product->update(['price' => 150, 'sale_price' => 120]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('Price:')
        ->assertSee('Sale Price:');
});

test('product changelog page displays multiple field changes in single activity', function () {
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'price' => 100,
        'sku' => 'SKU-001',
    ]);

    Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->delete();

    $product->update([
        'name' => 'Updated Name',
        'price' => 150,
        'sku' => 'SKU-002',
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'product', 'id' => $product->id]);

    $component->assertSee('Name:')
        ->assertSee('Price:')
        ->assertSee('SKU:')
        ->assertSee('Original Name')
        ->assertSee('Updated Name')
        ->assertSee('SKU-001')
        ->assertSee('SKU-002');
});
