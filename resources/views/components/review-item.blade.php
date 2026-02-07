<?php

use Livewire\Component;
use Livewire\Attributes\Defer;
use App\Models\Review;
use App\Services\ReviewService;
use Livewire\Attributes\Reactive;

new #[Defer] class extends Component {
    public Review $review;
    public ?bool $userVote = null;

    /**
     * Vote on Review
     */
    public function vote(bool $isHelpful)
    {
        try {
            app(ReviewService::class)->vote($this->review, $isHelpful);

            // Toggle logic (instant UI update)
            if ($this->userVote === $isHelpful) {
                $this->userVote = null;
            } else {
                $this->userVote = $isHelpful;
            }

            // Clear computed properties to refresh data
            $this->review->refresh();

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
};
?>

@placeholder
    <x-review-item-placeholder />
@endplaceholder

<div class="border-b pb-4 last:border-b-0">

    {{-- Review Header --}}
    <div class="flex items-start justify-between mb-3">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-1">
                <span class="font-semibold text-gray-900">{{ $review->user->name }}</span>
                @if ($review->is_verified_purchase)
                    <flux:badge icon="check-badge" size="sm" color="green">
                        Verified Purchase
                    </flux:badge>
                @endif
            </div>

            {{-- Star Rating --}}
            <div class="flex items-center gap-2">
                <div class="flex gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($i <= $review->rating)
                            <flux:icon.star class="size-4 text-orange-400 fill-current" />
                        @else
                            <flux:icon.star class="w-4 h-4 text-zinc-300 fill-current" />
                        @endif
                    @endfor
                </div>

                <span class="text-sm text-zinc-500">
                    {{ $review->created_at->diffForHumans() }}
                </span>
            </div>
        </div>
    </div>

    {{-- Review Title --}}
    @if ($review->title)
        <h5 class="font-medium mb-2">{{ $review->title }}</h5>
    @endif

    {{-- Review Text --}}
    <p class="text-zinc-600 text-sm mb-3 leading-wide">{{ $review->review_text }}
    </p>

    {{-- Review Images --}}
    @if ($review->images->isNotEmpty())
        <div class="flex gap-2 mb-4">
            @foreach ($review->images as $image)
                <img src="{{ $image->image_url }}" alt="Review image"
                    class="w-20 h-20 object-cover rounded border cursor-pointer hover:opacity-75 transition"
                    onclick="window.open('{{ $image->image_url }}', '_blank')">
            @endforeach
        </div>
    @endif

    {{-- Helpfulness --}}
    <div class="flex items-center gap-4 pt-3 border-t border-zinc-100">
        <p class="text-xs text-zinc-500">Was this helpful?</p>

        <div class="flex items-center gap-2">
            <flux:button wire:click="vote(true)" variant="ghost" size="xs" class="cursor-pointer font-normal"
                icon-size="sm">
                <flux:icon.hand-thumb-up variant="{{ $userVote === true ? 'solid' : 'outline' }}"
                    @class([
                        'inline-block size-5',
                        'text-green-600!' => $userVote === true,
                        'text-zinc-600!' => $userVote !== true,
                    ]) />
                <span class="ms-2">Yes ({{ $review->helpful_count }})</span>
            </flux:button>

            <flux:button wire:click="vote(false)" variant="ghost" size="xs" class="cursor-pointer font-normal">
                <flux:icon.hand-thumb-down variant="{{ $userVote === false ? 'solid' : 'outline' }}"
                    @class([
                        'inline-block size-5',
                        'text-red-600!' => $userVote === false,
                        'text-zinc-600!' => $userVote !== false,
                    ]) />
                <span class="ms-2">No ({{ $review->not_helpful_count }})</span>
            </flux:button>
        </div>
    </div>
</div>
