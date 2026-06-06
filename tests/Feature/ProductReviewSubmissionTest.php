<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\ReviewStatus;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Settings\ReviewSettings;
use Livewire\Livewire;

beforeEach(function () {
    $brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $category = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $this->product = Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $brand->id, 'primary_category_id' => $category->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
});

it('lets an authenticated customer submit a review for moderation', function () {
    app(ReviewSettings::class)->fill(['require_verified_purchase' => false])->save();

    $user = User::factory()->create(['name' => 'Anita Wanjiru']);
    $this->actingAs($user);

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->set('reviewRating', 4)
        ->set('reviewTitle', 'Solid performer')
        ->set('reviewBody', 'We have used this in our hotel kitchen for months without issue.')
        ->call('submitReview')
        ->assertHasNoErrors();

    $review = Review::first();

    expect($review)->not->toBeNull()
        ->and($review->status)->toBe(ReviewStatus::PENDING)
        ->and($review->user_id)->toBe($user->id)
        ->and($review->author_name)->toBe('Anita Wanjiru')
        ->and($review->rating)->toBe(4);
});

it('validates the review body', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->set('reviewBody', 'too short')
        ->call('submitReview')
        ->assertHasErrors('reviewBody');
});

it('redirects guests to login when submitting a review', function () {
    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->call('submitReview')
        ->assertRedirect(route('login'));

    expect(Review::count())->toBe(0);
});

it('shows approved reviews but hides pending ones', function () {
    Review::factory()->approved()->create([
        'product_id' => $this->product->id,
        'body' => 'Approved and visible review body.',
    ]);
    Review::factory()->create([
        'product_id' => $this->product->id,
        'body' => 'Pending hidden review body.',
    ]);

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->set('activeTab', 'reviews')
        ->assertSee('Approved and visible review body.')
        ->assertDontSee('Pending hidden review body.');
});

it('blocks a review from a customer who has not purchased the product', function () {
    // require_verified_purchase defaults to true.
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->set('reviewRating', 5)
        ->set('reviewBody', 'I have not actually purchased this product yet.')
        ->call('submitReview')
        ->assertHasErrors('reviewBody');

    expect(Review::count())->toBe(0);
});

it('lets a verified purchaser submit, and auto-approves when configured', function () {
    app(ReviewSettings::class)->fill(['auto_approve' => true])->save();

    $user = User::factory()->create();
    $this->actingAs($user);

    $order = Order::factory()->create(['user_id' => $user->id]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'product_snapshot' => ['name' => $this->product->name, 'sku' => $this->product->sku, 'model_number' => null],
        'unit_price_cents' => 150000,
        'quantity' => 1,
        'line_total_cents' => 150000,
        'tax_rate' => 0,
        'tax_cents' => 0,
    ]);

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->set('reviewRating', 5)
        ->set('reviewBody', 'Bought it and it performs well in our kitchen.')
        ->call('submitReview')
        ->assertHasNoErrors();

    expect(Review::first()?->status)->toBe(ReviewStatus::APPROVED);
});

it('hides reviews and rejects submissions when reviews are disabled', function () {
    app(ReviewSettings::class)->fill(['reviews_enabled' => false])->save();

    Review::factory()->approved()->create([
        'product_id' => $this->product->id,
        'body' => 'Approved review that should now be hidden.',
    ]);

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->assertDontSee('Approved review that should now be hidden.')
        ->set('reviewRating', 5)
        ->set('reviewBody', 'Trying to review while reviews are switched off.')
        ->call('submitReview');

    expect(Review::where('body', 'Trying to review while reviews are switched off.')->exists())->toBeFalse();
});
