<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the reviews admin index', function () {
    $this->get(route('admin.reviews.index'))->assertOk();
});

it('approves a pending review', function () {
    $review = Review::factory()->create(['status' => ReviewStatus::PENDING]);

    Livewire::test('pages::admin.reviews.index')
        ->call('approve', $review->id);

    $review->refresh();

    expect($review->status)->toBe(ReviewStatus::APPROVED)
        ->and($review->approved_at)->not->toBeNull();
});

it('rejects a review', function () {
    $review = Review::factory()->approved()->create();

    Livewire::test('pages::admin.reviews.index')
        ->call('reject', $review->id);

    expect($review->fresh()->status)->toBe(ReviewStatus::REJECTED);
});

it('deletes a review', function () {
    $review = Review::factory()->create();

    Livewire::test('pages::admin.reviews.index')
        ->call('delete', $review->id);

    expect(Review::find($review->id))->toBeNull();
});

it('filters reviews by status', function () {
    Review::factory()->create(['status' => ReviewStatus::PENDING, 'author_name' => 'Pending Patty']);
    Review::factory()->approved()->create(['author_name' => 'Approved Annie']);

    Livewire::test('pages::admin.reviews.index')
        ->set('filterStatus', ReviewStatus::PENDING->value)
        ->assertSee('Pending Patty')
        ->assertDontSee('Approved Annie');
});
