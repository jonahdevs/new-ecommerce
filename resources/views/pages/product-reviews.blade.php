<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\ReviewHelpfulness;
use App\Models\Review;
use App\Services\ReviewService;
use Livewire\WithPagination;
use Livewire\Attributes\Defer;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    use WithPagination;

    public Product $product;

    #[Url(as: 'rating')]
    public ?int $filterRating = null;

    #[Url(as: 'sort')]
    public string $sortBy = 'recent';

    public int $perPage = 10;

    /**
     * Get paginated reviews
     */
    #[Computed]
    public function reviews()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->forReviewsPage($this->product, [
            'rating' => $this->filterRating,
            'sort_by' => $this->sortBy,
            'per_page' => $this->perPage,
        ]);
    }

    /**
     * Get rating distribution with percentages
     */
    #[Computed]
    public function ratingDistribution()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->getDistributionWithPercentages($this->product);
    }

    /**
     * Get total reviews count
     */
    #[Computed]
    public function totalReviews()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->totalReview($this->product);
    }

    /**
     * Get average rating
     */
    #[Computed]
    public function averageRating()
    {
        $reviewService = app(ReviewService::class);
        return $reviewService->averageRating($this->product);
    }

    /**
     * Update sorting
     */
    public function updatedSortBy()
    {
        $this->resetPage();
    }

    /**
     * Update rating filter
     */
    public function updatedFilterRating()
    {
        $this->resetPage();
    }

    /**
     * Filter by rating
     */
    public function filterByRating(?int $rating)
    {
        $this->filterRating = $this->filterRating === $rating ? null : $rating;
        $this->resetPage();
    }

    /**
     * Clear filters
     */
    public function clearFilters()
    {
        $this->filterRating = null;
        $this->sortBy = 'recent';
        $this->resetPage();
    }

    /**
     * Vote on Review
     */
    public function vote($reviewId, $isHelpful)
    {
        try {
            $review = Review::findOrFail($reviewId);
            $reviewService = app(ReviewService::class);
            $reviewService->vote($review, $isHelpful);

            // Clear computed properties to refresh data
            unset($this->reviews);
            unset($this->userVotes);

            $this->dispatch('notify', variant: 'success', message: 'Thank you for your feedback!');
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'self_vote') {
                $this->dispatch('notify', variant: 'warning', message: 'You cannot vote on your own review.');
            } else {
                $this->dispatch('notify', variant: 'warning', message: 'An error occurred. Please try again.');
            }
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'warning', message: $th->getMessage() ?? 'Something went wrong!');
        }
    }

    /**
     * Get user votes for all reviews on current page (prevents N+1 queries)
     */
    #[Computed]
    public function userVotes()
    {
        if (!Auth::check()) {
            return collect();
        }

        $reviewIds = $this->reviews->getCollection()->pluck('id')->toArray();

        if (empty($reviewIds)) {
            return collect();
        }

        return ReviewHelpfulness::whereIn('review_id', $reviewIds)->where('user_id', Auth::id())->get()->keyBy('review_id')->map(fn($vote) => $vote->is_helpful);
    }
};
?>


@placeholder
    <div>
        <section class="bg-zinc-100">
            <div class="container mx-auto py-4 px-4">
                <div class="flex items-center gap-3">
                    <flux:skeleton animate="shimmer" class="w-32 h-4" />
                    <flux:skeleton animate="shimmer" class="w-8 h-4" />
                    <flux:skeleton animate="shimmer" class="w-32 h-4" />
                    <flux:skeleton animate="shimmer" class="w-8 h-4" />
                    <flux:skeleton animate="shimmer" class="w-44 h-4" />
                </div>
            </div>
        </section>

        <div class="container mx-auto px-4 py-4 min-h-[80svh]">
            <flux:skeleton class="w-48 h-4 mb-6" animate="shimmer" />

            <div class="p-5 grid grid-cols-1 lg:grid-cols-4 gap-5">
                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-44 space-y-6">
                        <flux:skeleton animate="shimmer" class="w-14 h-5 mx-auto mb-2" />
                        <flux:skeleton animate="shimmer" class="w-28 h-4 mx-auto mb-2" />
                        <flux:skeleton animate="shimmer" class="w-12 h-3 mx-auto" />

                        <flux:separator class="my-4" />

                        <div class="flex items-center gap-3 mb-2">
                            <flux:skeleton class="w-16 h-4" />
                            <flux:skeleton class="flex-1 h-4" />
                        </div>

                        <div class="flex items-center gap-3 mb-2">
                            <flux:skeleton class="w-16 h-4" />
                            <flux:skeleton class="flex-1 h-4" />
                        </div>

                        <div class="flex items-center gap-3 mb-2">
                            <flux:skeleton class="w-16 h-4" />
                            <flux:skeleton class="flex-1 h-4" />
                        </div>

                        <div class="flex items-center gap-3 mb-2">
                            <flux:skeleton class="w-16 h-4" />
                            <flux:skeleton class="flex-1 h-4" />
                        </div>

                        <div class="flex items-center gap-3">
                            <flux:skeleton class="w-16 h-4" />
                            <flux:skeleton class="flex-1 h-4" />
                        </div>

                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="flex items-center justify-between mb-5 pb-4 border-b">
                        <flux:skeleton animate="shimmer" class="h-4 w-28" />


                        <div class="flex items-center gap-3">
                            <flux:skeleton class="w-28 h-4" />
                            <flux:skeleton class="w-28 h-4" />
                        </div>

                    </div>

                    <div class="space-y-6">
                        @for ($i = 0; $i < 4; $i++)
                            <x-review-item-placeholder />
                        @endfor
                    </div>
                </div>
            </div>

        </div>
    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-4 px-4 ">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item href="{{ route('products', ['category' => $product->primaryCategory()->slug]) }}">
                {{ $product->primaryCategory()->name }}
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item href="{{ route('products.show', $product) }}" wire:navigate>
                {{ $product->name }}
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Reviews</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-4 min-h-[80svh]">
        <!-- Cart Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900">Customer Reviews</h1>
            </div>
        </div>

        <div class=" p-5">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

                {{-- Sidebar with Rating Statistics --}}
                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-44 space-y-6">
                        <div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-sheffield-blue">{{ $this->averageRating }}
                                </div>
                                <div class="flex justify-center gap-1 mt-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= floor($this->averageRating))
                                            <flux:icon.star class="size-5 text-orange-400 fill-current" />
                                        @elseif ($i - 0.5 <= $this->averageRating)
                                            <svg class="w-5 h-5 text-orange-400" viewBox="0 0 20 20">
                                                <defs>
                                                    <linearGradient id="half-star">
                                                        <stop offset="50%" stop-color="currentColor" />
                                                        <stop offset="50%" stop-color="#D1D5DB" />
                                                    </linearGradient>
                                                </defs>
                                                <path fill="url(#half-star)"
                                                    d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                            </svg>
                                        @else
                                            <flux:icon.star class="size-5 text-zinc-300 fill-current" />
                                        @endif
                                    @endfor
                                </div>
                                <div class="text-sm text-zinc-600 mt-1">{{ $this->totalReviews }}
                                    {{ Str::plural('review', $this->totalReviews) }}</div>
                            </div>
                        </div>

                        <flux:separator class="my-4" />

                        {{-- Rating Distribution --}}
                        <div>
                            <div class="space-y-2">
                                @foreach ($this->ratingDistribution as $rating => $data)
                                    <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                                        {{-- Star Rating --}}
                                        <div class="flex gap-0.5">
                                            @for ($star = 1; $star <= 5; $star++)
                                                @if ($star <= $rating)
                                                    <flux:icon.star class="size-5 text-orange-400 fill-current" />
                                                @else
                                                    <flux:icon.star class="size-5 text-zinc-300 fill-current" />
                                                @endif
                                            @endfor
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-sheffield-blue h-2.5 rounded-full"
                                                style="width: {{ $data['percentage'] }}%"></div>
                                        </div>

                                        {{-- Percentage --}}
                                        <span class="text-sm font-semibold text-sheffield-blue min-w-[45px]">
                                            {{ $data['percentage'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reviews List --}}
                <div class="lg:col-span-3">
                    {{-- Sort and Filter Controls --}}
                    <div class="flex items-center justify-between mb-5 pb-4 border-b">
                        <div class="text-sm text-zinc-600">
                            @if ($filterRating)
                                Showing {{ $filterRating }}-star reviews
                            @else
                                Showing all reviews
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Clear Filters Button --}}
                            @if ($filterRating || $sortBy !== 'recent')
                                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" size="sm">
                                    Clear Filters
                                </flux:button>
                            @endif

                            <flux:select wire:model.change="filterRating" class="w-fit">
                                <flux:select.option value="5">5 Star</flux:select.option>
                                <flux:select.option value="4">4 Star</flux:select.option>
                                <flux:select.option value="3">3 Star</flux:select.option>
                                <flux:select.option value="2">2 Star</flux:select.option>
                                <flux:select.option value="1">1 Star</flux:select.option>
                            </flux:select>

                            <flux:select wire:model.change="sortBy" class="w-fit">
                                <flux:select.option value="recent">Most Recent</flux:select.option>
                                <flux:select.option value="helpful">Most Helpful</flux:select.option>
                                <flux:select.option value="highest">Highest Rating</flux:select.option>
                                <flux:select.option value="lowest">Lowest Rating</flux:select.option>
                            </flux:select>
                        </div>
                    </div>

                    {{-- Reviews Content --}}
                    @if ($this->reviews->isEmpty())
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-zinc-300 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <p class="text-zinc-500 text-lg mb-2">No reviews yet</p>
                            <p class="text-zinc-400 text-sm">
                                @if ($filterRating)
                                    No {{ $filterRating }}-star reviews found. Try adjusting your filters.
                                @else
                                    Be the first to review this product!
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="space-y-6">
                            @foreach ($this->reviews as $review)
                                <livewire:review-item :review="$review" :key="'review-item-' . $review->id" :user-vote="$this->userVotes->get($review->id)" />
                            @endforeach
                        </div>
                    @endif

                    {{-- Pagination --}}
                    @if ($this->reviews->hasPages())
                        <div class="mt-8">
                            {{ $this->reviews->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <livewire:product-recommendations type="recently_viewed" />
    </section>
</div>
