@if ($this->product->reviews_enabled && app(\App\Settings\ReviewSettings::class)->reviews_enabled)
    <div wire:cloak wire:show="selectedTab == 'reviews'">
        <h4 class="font-bold mb-6">Customer Ratings</h4>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-7">

            {{-- ── Rating Distribution ── --}}
            <div class="col-span-1">
                <div class="sticky top-44">
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
                            {{ $this->reviewStats['total'] }}
                            {{ Str::plural('review', $this->reviewStats['total']) }}
                        </div>
                    </div>

                    <flux:separator class="my-4" />

                    <div class="space-y-2">
                        @foreach ($this->reviewStats['distribution'] as $rating => $data)
                            <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                                <div class="flex gap-0.5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        @if ($star <= $rating)
                                            <flux:icon.star class="size-5 text-orange-400 fill-current" />
                                        @else
                                            <flux:icon.star class="size-5 text-zinc-300 fill-current" />
                                        @endif
                                    @endfor
                                </div>
                                <div class="w-full bg-zinc-200 rounded-full h-2.5">
                                    <div class="bg-brand-secondary h-2.5 rounded-full"
                                        style="width: {{ $data['percentage'] }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-brand-secondary min-w-11.25">
                                    {{ $data['percentage'] }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── Reviews List ── --}}
            <div class="col-span-1 lg:col-span-3">
                @if ($this->reviews->isEmpty())
                    <div class="text-center py-8 text-zinc-500">
                        <p>No reviews yet. Be the first to review this product!</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($this->reviews as $review)
                            <livewire:review-item :review="$review" :key="'review-item-' . $review->id" :user-vote="$this->userVotes->get($review->id)" />
                        @endforeach
                    </div>

                    @if ($this->hasMoreReviews)
                        <div class="mt-6 text-center">
                            <flux:button href="{{ route('products.reviews', $product) }}" wire:navigate>
                                View All {{ $this->reviewStats['total'] }} Reviews
                            </flux:button>
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
@endif
