<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\ReviewStatus;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
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
