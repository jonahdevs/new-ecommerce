<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// ─── Model scope & helper ────────────────────────────────────────────────────

it('scopes to products that are live now', function () {
    $published = Product::factory()->create(['status' => ProductStatus::PUBLISHED]);
    $dueScheduled = Product::factory()->create(['status' => ProductStatus::SCHEDULED, 'published_at' => now()->subHour()]);
    $futureScheduled = Product::factory()->create(['status' => ProductStatus::SCHEDULED, 'published_at' => now()->addHour()]);
    $draft = Product::factory()->create(['status' => ProductStatus::DRAFT]);

    $ids = Product::published()->pluck('id');

    expect($ids)->toContain($published->id)
        ->and($ids)->toContain($dueScheduled->id)
        ->and($ids)->not->toContain($futureScheduled->id)
        ->and($ids)->not->toContain($draft->id);
});

it('reports isPublished correctly', function () {
    expect(Product::factory()->create(['status' => ProductStatus::PUBLISHED])->isPublished())->toBeTrue()
        ->and(Product::factory()->create(['status' => ProductStatus::SCHEDULED, 'published_at' => now()->subHour()])->isPublished())->toBeTrue()
        ->and(Product::factory()->create(['status' => ProductStatus::SCHEDULED, 'published_at' => now()->addHour()])->isPublished())->toBeFalse()
        ->and(Product::factory()->create(['status' => ProductStatus::DRAFT])->isPublished())->toBeFalse();
});

// ─── Scheduled publishing command ────────────────────────────────────────────

it('publishes scheduled products whose time has passed', function () {
    $due = Product::factory()->create(['status' => ProductStatus::SCHEDULED, 'published_at' => now()->subMinute()]);
    $future = Product::factory()->create(['status' => ProductStatus::SCHEDULED, 'published_at' => now()->addDay()]);

    $this->artisan('products:publish-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe(ProductStatus::PUBLISHED)
        ->and($future->fresh()->status)->toBe(ProductStatus::SCHEDULED);
});

// ─── Form: status handling ───────────────────────────────────────────────────

it('rejects a scheduled product with a past publish date', function () {
    Livewire::test('pages::admin.products.form')
        ->set('name', 'Past Schedule')
        ->set('slug', 'past-schedule')
        ->set('status', ProductStatus::SCHEDULED->value)
        ->set('published_at', now()->subDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['published_at']);
});

it('saves a scheduled product with a future publish date', function () {
    $future = now()->addWeek()->startOfMinute();

    Livewire::test('pages::admin.products.form')
        ->set('name', 'Future Schedule')
        ->set('slug', 'future-schedule')
        ->set('status', ProductStatus::SCHEDULED->value)
        ->set('published_at', $future->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::firstWhere('slug', 'future-schedule');

    expect($product->status)->toBe(ProductStatus::SCHEDULED)
        ->and($product->published_at->format('Y-m-d H:i'))->toBe($future->format('Y-m-d H:i'));
});

it('defaults published_at to now when published with no date', function () {
    Livewire::test('pages::admin.products.form')
        ->set('name', 'Publish Now')
        ->set('slug', 'publish-now')
        ->set('status', ProductStatus::PUBLISHED->value)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::firstWhere('slug', 'publish-now');

    expect($product->status)->toBe(ProductStatus::PUBLISHED)
        ->and($product->published_at)->not->toBeNull();
});

it('clears the publish date when moved to draft', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::SCHEDULED,
        'published_at' => now()->addDay(),
    ]);

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->set('status', ProductStatus::DRAFT->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->published_at)->toBeNull();
});

it('prefills a future publish date when switching to scheduled', function () {
    Livewire::test('pages::admin.products.form')
        ->set('status', ProductStatus::SCHEDULED->value)
        ->assertSet('published_at', fn ($value) => $value !== '' && strtotime($value) > time());
});
