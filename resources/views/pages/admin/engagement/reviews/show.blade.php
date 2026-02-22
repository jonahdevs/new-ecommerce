<?php
use App\Models\Review;
use Livewire\Component;
use Livewire\Attributes\{Title, Layout};

new class extends Component {
    public Review $review;

    public $moderationNote = '';

    public function mount(Review $review)
    {
        \Log::info(json_encode('Review' . $review, JSON_PRETTY_PRINT));
        $this->review = $review->load(['user', 'product', 'images', 'moderator']);
    }

    public function approve()
    {
        $this->review->update([
            'status' => 'approved',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        session()->flash('status', 'Review approved successfully.');
        return $this->redirect(route('admin.reviews.index'), navigate: true);
    }

    public function reject()
    {
        $this->review->update([
            'status' => 'rejected',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        session()->flash('status', 'Review rejected successfully.');
        return $this->redirect(route('admin.reviews.index'), navigate: true);
    }

    public function delete()
    {
        $this->review->delete();
        session()->flash('status', 'Review deleted successfully.');
        return $this->redirect(route('admin.reviews.index'), navigate: true);
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.reviews.index')" wire:navigate>Reviews</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Header --}}

    <div class="flex items-start justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Review Details</flux:heading>
            <flux:subheading>Review for {{ $review->product?->name }}</flux:subheading>
        </div>

        <flux:badge size="lg" variant="flat"
            :color="match($review->status) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    'pending' => 'amber',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    'approved' => 'green',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    'rejected' => 'red',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    default => 'gray',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                }">
            {{ ucfirst($review->status) }}
        </flux:badge>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Review Content Card --}}
            <flux:card>
                <div class="space-y-4">
                    {{-- Rating --}}
                    <div>
                        <flux:subheading class="mb-2">Rating</flux:subheading>
                        <div class="flex items-center gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <flux:icon name="star" variant="{{ $i <= $review->rating ? 'solid' : 'outline' }}"
                                    class="w-6 h-6 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-zinc-300' }}" />
                            @endfor
                            <span class="text-lg font-semibold ml-2">{{ $review->rating }} out of 5</span>
                        </div>
                    </div>

                    {{-- Title --}}
                    @if ($review->title)
                        <div>
                            <flux:subheading class="mb-2">Review Title</flux:subheading>
                            <p class="text-lg font-semibold text-zinc-800 dark:text-white">{{ $review->title }}</p>
                        </div>
                    @endif

                    {{-- Review Text --}}
                    <div>
                        <flux:subheading class="mb-2">Review</flux:subheading>
                        <p class="text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap leading-relaxed">
                            {{ $review->review_text }}</p>
                    </div>

                    {{-- Images --}}
                    @if ($review->images->count() > 0)
                        <div>
                            <flux:subheading class="mb-3">Photos ({{ $review->images->count() }})</flux:subheading>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach ($review->images as $image)
                                    <div class="aspect-square rounded-lg border bg-zinc-50 overflow-hidden">
                                        <img src="{{ $image->image_url }}" alt="Review image"
                                            class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Helpfulness Stats --}}
                    @if ($review->helpful_count > 0 || $review->not_helpful_count > 0)
                        <div class="pt-4 border-t">
                            <flux:subheading class="mb-2">Customer Feedback</flux:subheading>
                            <div class="flex gap-6">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="hand-thumb-up" variant="solid" class="w-5 h-5 text-green-600" />
                                    <span class="font-medium">{{ $review->helpful_count }} found helpful</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:icon name="hand-thumb-down" variant="solid" class="w-5 h-5 text-red-600" />
                                    <span class="font-medium">{{ $review->not_helpful_count }} not helpful</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Moderation Actions --}}
            @if ($review->status === 'pending')
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Moderation Actions</flux:heading>
                    <div class="flex gap-3">
                        <flux:button color="green" icon="check" wire:click="approve"
                            wire:confirm="Approve this review?">
                            Approve Review
                        </flux:button>
                        <flux:button color="red" variant="outline" icon="x-mark" wire:click="reject"
                            wire:confirm="Reject this review?">
                            Reject Review
                        </flux:button>
                    </div>
                </flux:card>
            @endif

            {{-- Moderation History --}}
            @if ($review->moderated_at)
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Moderation History</flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Moderated by:</span>
                            <span class="font-medium">{{ $review->moderator->name ?? 'System' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Moderated at:</span>
                            <span class="font-medium">{{ $review->moderated_at->format('M d, Y g:i A') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Action taken:</span>
                            <flux:badge size="sm" variant="flat"
                                :color="$review->status === 'approved' ? 'green' : 'red'">
                                {{ ucfirst($review->status) }}
                            </flux:badge>
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Customer Info --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Customer</flux:heading>
                <div class="space-y-3">
                    <div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Name</div>
                        <div class="font-medium">{{ $review->user?->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Email</div>
                        <div class="font-medium">{{ $review->user?->email }}</div>
                    </div>
                    @if ($review->is_verified_purchase)
                        <flux:badge color="green" variant="flat" size="sm">
                            <flux:icon name="check-badge" class="w-4 h-4" />
                            Verified Purchase
                        </flux:badge>
                    @endif
                </div>
            </flux:card>

            {{-- Product Info --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Product</flux:heading>
                <div class="flex gap-3">
                    <div class="w-16 h-16 rounded border bg-zinc-50 overflow-hidden flex-shrink-0">
                        @if ($review->product?->image_path)
                            <img src="{{ $review->product?->image_url }}" class="object-cover w-full h-full">
                        @else
                            <flux:icon name="photo" class="w-full h-full p-3 text-zinc-300" />
                        @endif
                    </div>
                    <div>
                        <div class="font-medium mb-1">{{ $review->product?->name }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                            SKU: {{ $review->product?->sku ?? 'N/A' }}
                        </div>
                        <flux:button variant="ghost" size="sm" class="mt-2" wire:navigate>
                            View Product
                        </flux:button>
                    </div>
                </div>
            </flux:card>

            {{-- Review Meta --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Review Information</flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">Submitted:</span>
                        <span class="font-medium">{{ $review->created_at?->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">Time ago:</span>
                        <span class="font-medium">{{ $review->created_at?->diffForHumans() }}</span>
                    </div>
                    @if ($review->updated_at != $review->created_at)
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Last updated:</span>
                            <span class="font-medium">{{ $review->updated_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Danger Zone --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4" class="text-red-600">Danger Zone</flux:heading>
                <flux:button color="red" variant="outline" size="sm" icon="trash" wire:click="delete"
                    wire:confirm="Are you sure you want to permanently delete this review? This action cannot be undone.">
                    Delete Review Permanently
                </flux:button>
            </flux:card>
        </div>
    </div>
</div>
