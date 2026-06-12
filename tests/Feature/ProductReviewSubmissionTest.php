<?php

use App\Enums\CategoryStatus;
use App\Enums\OrderStatus;
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

/** A customer with a completed order containing the given product — the verified-purchase gate. */
function verifiedPurchaserOf(Product $product, array $userAttrs = []): User
{
    $user = User::factory()->create($userAttrs);
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::COMPLETED]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_snapshot' => ['name' => $product->name, 'sku' => $product->sku, 'model_number' => null],
        'unit_price_cents' => 150000,
        'quantity' => 1,
        'line_total_cents' => 150000,
        'tax_rate' => 0,
        'tax_cents' => 0,
    ]);

    return $user;
}

it('lets a verified purchaser submit a review for moderation', function () {
    app(ReviewSettings::class)->fill(['auto_approve' => false])->save();

    $user = verifiedPurchaserOf($this->product, ['name' => 'Anita Wanjiru']);
    $this->actingAs($user);

    Livewire::test('pages::account.review-form', ['product' => $this->product])
        ->set('rating', 4)
        ->set('title', 'Solid performer')
        ->set('body', 'We have used this in our hotel kitchen for months without issue.')
        ->call('submit')
        ->assertHasNoErrors();

    $review = Review::first();

    expect($review)->not->toBeNull()
        ->and($review->status)->toBe(ReviewStatus::PENDING)
        ->and($review->user_id)->toBe($user->id)
        ->and($review->author_name)->toBe('Anita Wanjiru')
        ->and($review->rating)->toBe(4)
        ->and($review->verified_purchase)->toBeTrue();
});

it('validates the review body', function () {
    $this->actingAs(verifiedPurchaserOf($this->product));

    Livewire::test('pages::account.review-form', ['product' => $this->product])
        ->set('body', 'too short')
        ->call('submit')
        ->assertHasErrors('body');
});

it('redirects guests to login from the review form', function () {
    $this->get(route('account.reviews.form', $this->product))
        ->assertRedirect(route('login'));

    expect(Review::count())->toBe(0);
});

it('blocks the review form for a customer who has not purchased the product', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('account.reviews.form', $this->product))->assertForbidden();

    expect(Review::count())->toBe(0);
});

it('auto-approves a verified purchaser review when configured', function () {
    app(ReviewSettings::class)->fill(['auto_approve' => true])->save();

    $this->actingAs(verifiedPurchaserOf($this->product));

    Livewire::test('pages::account.review-form', ['product' => $this->product])
        ->set('rating', 5)
        ->set('body', 'Bought it and it performs well in our kitchen.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Review::first()?->status)->toBe(ReviewStatus::APPROVED);
});

it('updates an existing review instead of creating a duplicate', function () {
    $user = verifiedPurchaserOf($this->product);
    $this->actingAs($user);

    Review::create([
        'product_id' => $this->product->id,
        'user_id' => $user->id,
        'author_name' => $user->name,
        'rating' => 2,
        'body' => 'My first take on this product before the firmware update.',
        'status' => ReviewStatus::PENDING,
        'verified_purchase' => true,
    ]);

    Livewire::test('pages::account.review-form', ['product' => $this->product])
        ->assertSet('rating', 2)
        ->set('rating', 5)
        ->set('body', 'After the update it performs much better — revising my rating up.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Review::where('user_id', $user->id)->where('product_id', $this->product->id)->count())->toBe(1)
        ->and(Review::first()->rating)->toBe(5);
});

it('shows approved reviews but hides pending ones on the product page', function () {
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

it('hides the reviews tab content when reviews are disabled', function () {
    app(ReviewSettings::class)->fill(['reviews_enabled' => false])->save();

    Review::factory()->approved()->create([
        'product_id' => $this->product->id,
        'body' => 'Approved review that should now be hidden.',
    ]);

    Livewire::test('pages::storefront.product', ['product' => $this->product])
        ->assertDontSee('Approved review that should now be hidden.');
});
