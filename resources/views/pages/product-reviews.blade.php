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
use Illuminate\Support\Facades\Auth; // FIX: was missing — Auth::check() / Auth::id() in userVotes() would fail

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    use WithPagination;

    public Product $product;

    #[Url(as: 'rating')]
    public ?int $filterRating = null;

    #[Url(as: 'sort')]
    public string $sortBy = 'recent';

    public int $perPage = 10;

    /**
     * Single query returning total, average, and distribution.
     * persist: true is appropriate here — stats don't change while
     * the user is paginating or filtering within a session.
     *
     * Replaces three separate computed properties:
     *   totalReviews(), averageRating(), ratingDistribution()
     */
    #[Computed(persist: true)]
    public function reviewStats(): array
    {
        return app(ReviewService::class)->getStatistics($this->product);
    }

    /**
     * Paginated reviews — filter and sort aware.
     * FIX: removed persist: true — this is filter/sort driven and must
     * re-query when filterRating or sortBy changes, or pagination moves.
     */
    #[Computed]
    public function reviews()
    {
        return app(ReviewService::class)->forReviewsPage($this->product, [
            'rating' => $this->filterRating,
            'sort_by' => $this->sortBy,
            'per_page' => $this->perPage,
        ]);
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
        unset($this->reviews);
    }

    public function updatedFilterRating(): void
    {
        $this->resetPage();
        unset($this->reviews);
    }

    public function filterByRating(?int $rating): void
    {
        $this->filterRating = $this->filterRating === $rating ? null : $rating;
        $this->resetPage();
        unset($this->reviews);
    }

    public function clearFilters(): void
    {
        $this->filterRating = null;
        $this->sortBy = 'recent';
        $this->resetPage();
        unset($this->reviews);
    }

    public function vote($reviewId, $isHelpful): void
    {
        try {
            $review = Review::findOrFail($reviewId);
            app(ReviewService::class)->vote($review, $isHelpful);

            unset($this->reviews);
            unset($this->userVotes);

            $this->dispatch('notify', variant: 'success', message: 'Thank you for your feedback!');
        } catch (\DomainException $e) {
            $message = $e->getMessage() === 'self_vote' ? 'You cannot vote on your own review.' : 'An error occurred. Please try again.';

            $this->dispatch('notify', variant: 'warning', message: $message);
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'warning', message: $th->getMessage() ?: 'Something went wrong!');
        }
    }

    /**
     * Batch-load helpfulness votes for all reviews on the current page.
     * Prevents N+1 — one query per page render instead of one per review.
     */
    #[Computed]
    public function userVotes()
    {
        if (!Auth::check()) {
            // FIX: now works — Auth facade is imported
            return collect();
        }

        $reviewIds = $this->reviews->getCollection()->pluck('id')->toArray();

        if (empty($reviewIds)) {
            return collect();
        }

        return ReviewHelpfulness::whereIn('review_id', $reviewIds)->where('user_id', Auth::id())->get()->keyBy('review_id')->map(fn($vote) => $vote->is_helpful);
    }

    public function render()
    {
        return $this->view()->title('Reviews — ' . $this->product->name . ' — ' . config('app.name'));
    }
};
?>

@placeholder
    <div>
        <section class="bg-zinc-100">
            <div class="container mx-auto py-2.5 px-4">
                <div class="flex items-center gap-3">
                    <flux:skeleton animate="shimmer" class="w-4 h-4" />
                    <flux:skeleton animate="shimmer" class="w-14 h-4" />
                    <flux:skeleton animate="shimmer" class="w-3 h-4" />
                    <flux:skeleton animate="shimmer" class="w-14 h-4" />
                    <flux:skeleton animate="shimmer" class="w-3 h-4" />
                    <flux:skeleton animate="shimmer" class="w-14 h-4" />
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

                        @for ($i = 0; $i < 5; $i++)
                            <div class="flex items-center gap-3 mb-2">
                                <flux:skeleton class="w-16 h-4" />
                                <flux:skeleton class="flex-1 h-4" />
                            </div>
                        @endfor
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
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item
                href="{{ route('shop.category', ['category' => $product->primaryCategory()->slug]) }}">
                {{ $product->primaryCategory()->name }}
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item href="{{ route('products.show', $product) }}" wire:navigate>
                {{ $product->name }}
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Reviews</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-4 min-h-[80svh]">
        <div class="flex items-center justify-between mb-6">
            <flux:heading level="1" class="text-2xl! font-bold! text-zinc-900">
                Customer Reviews
            </flux:heading>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

            {{-- Sidebar: Rating Statistics --}}
            <div class="lg:col-span-1">
                <flux:card class="lg:sticky lg:top-44 space-y-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-brand-secondary">
                            {{ $this->reviewStats['average'] }}
                        </div>

                        <div class="flex justify-center gap-1 mt-1">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= floor($this->reviewStats['average']))
                                    <flux:icon.star class="size-5 text-orange-400 fill-current" />
                                @elseif ($i - 0.5 <= $this->reviewStats['average'])
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

                        <div class="text-sm text-zinc-600 mt-1">
                            {{ $this->reviewStats['total'] }} {{ Str::plural('review', $this->reviewStats['total']) }}
                        </div>
                    </div>

                    <flux:separator class="my-4" />

                    {{-- Distribution bars — clickable to filter --}}
                    <div class="space-y-2">
                        @foreach ($this->reviewStats['distribution'] as $rating => $data)
                            <button wire:click="filterByRating({{ $rating }})"
                                class="grid grid-cols-[auto_1fr_auto] items-center gap-3 w-full cursor-pointer group">

                                <div class="flex gap-0.5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        @if ($star <= $rating)
                                            <flux:icon.star class="size-4 text-orange-400 fill-current" />
                                        @else
                                            <flux:icon.star class="size-4 text-zinc-300 fill-current" />
                                        @endif
                                    @endfor
                                </div>

                                <div class="w-full bg-zinc-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full transition-all duration-300 {{ $filterRating === $rating ? 'bg-orange-400' : 'bg-brand-secondary group-hover:bg-brand-secondary/70' }}"
                                        style="width: {{ $data['percentage'] }}%"></div>
                                </div>

                                <span class="text-sm font-semibold text-brand-secondary min-w-11.25 text-right">
                                    {{ $data['percentage'] }}%
                                </span>
                            </button>
                        @endforeach
                    </div>
                </flux:card>
            </div>

            {{-- Reviews List --}}
            <flux:card class="lg:col-span-3">

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
                        @if ($filterRating || $sortBy !== 'recent')
                            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark" size="sm"
                                class="cursor-pointer">
                                Clear Filters
                            </flux:button>
                        @endif

                        {{-- FIX: Added "All Ratings" option so users can deselect via the select itself --}}
                        <flux:select wire:model.live="filterRating" class="w-fit">
                            <flux:select.option value="">All Ratings</flux:select.option>
                            <flux:select.option value="5">5 Star</flux:select.option>
                            <flux:select.option value="4">4 Star</flux:select.option>
                            <flux:select.option value="3">3 Star</flux:select.option>
                            <flux:select.option value="2">2 Star</flux:select.option>
                            <flux:select.option value="1">1 Star</flux:select.option>
                        </flux:select>

                        <flux:select wire:model.live="sortBy" class="w-fit">
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
                        <p class="text-zinc-500 text-lg mb-2">No reviews found</p>
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

                @if ($this->reviews->hasPages())
                    <div class="mt-8">
                        {{ $this->reviews->links() }}
                    </div>
                @endif
            </flux:card>
        </div>

        <livewire:product-recommendations type="recently_viewed" />
    </section>
</div>
