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

        $this->dispatch('notify', title: 'Review Approved', variant: 'success', message: 'Review approved successfully.');
        return $this->redirect(route('admin.reviews.index'), navigate: true);
    }

    public function reject()
    {
        $this->review->update([
            'status' => 'rejected',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        $this->dispatch('notify', title: 'Review Rejected', variant: 'warning', message: 'Review rejected successfully.');
        return $this->redirect(route('admin.reviews.index'), navigate: true);
    }

    public function delete()
    {
        $this->review->delete();
        $this->dispatch('notify', title: 'Review Deleted', variant: 'danger', message: 'Review deleted successfully.');
        return $this->redirect(route('admin.reviews.index'), navigate: true);
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
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

        <flux:badge size="lg" variant="flat" :color="$review->status?->color()">{{ $review->status?->label() }}
        </flux:badge>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Review Content Card --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b dark:border-zinc-600">
                    <flux:heading>Review Content</flux:heading>
                </div>
                <div class="space-y-4 p-5">
                    {{-- Rating --}}
                    <div>
                        <flux:subheading class="mb-2">Rating</flux:subheading>
                        <div class="flex items-center gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <flux:icon.star variant="{{ $i <= $review->rating ? 'solid' : 'outline' }}"
                                    class="w-6 h-6 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-zinc-300' }}" />
                            @endfor
                            <flux:heading size="lg" class="ml-2">{{ $review->rating }} out of 5</flux:heading>
                        </div>
                    </div>

                    {{-- Title --}}
                    @if ($review->title)
                        <div>
                            <flux:subheading class="mb-2">Review Title</flux:subheading>
                            <flux:heading size="lg">{{ $review->title }}</flux:heading>
                        </div>
                    @endif

                    {{-- Review Text --}}
                    <div>
                        <flux:subheading class="mb-2">Review</flux:subheading>
                        <flux:subheading class="whitespace-pre-wrap leading-relaxed">
                            {{ $review->review_text }}</flux:subheading>
                    </div>

                    {{-- Images --}}
                    @if ($review->images->count() > 0)
                        <div>
                            <flux:subheading class="mb-3">Photos ({{ $review->images->count() }})</flux:subheading>
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                                @foreach ($review->images as $image)
                                    <div class="aspect-square rounded-lg border dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 overflow-hidden">
                                        <img src="{{ $image->image_url }}" alt="Review image"
                                            class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Helpfulness Stats --}}
                    @if ($review->helpful_count > 0 || $review->not_helpful_count > 0)
                        <div class="pt-4 border-t dark:border-zinc-600">
                            <flux:subheading class="mb-2">Customer Feedback</flux:subheading>
                            <div class="flex gap-6">
                                <div class="flex items-center gap-2">
                                    <flux:icon.hand-thumb-up variant="solid" class="w-5 h-5 text-green-600" />
                                    <flux:subheading>{{ $review->helpful_count }} found helpful</flux:subheading>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:icon.hand-thumb-down variant="solid" class="w-5 h-5 text-red-600" />
                                    <flux:subheading>{{ $review->not_helpful_count }} not helpful</flux:subheading>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Moderation Actions --}}
            @if ($review->status === 'pending')
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b dark:border-zinc-600">
                        <flux:heading>Moderation Actions</flux:heading>
                    </div>
                    <div class="flex gap-3 p-5">
                        <flux:button color="green" icon="check" wire:click="approve"
                            wire:confirm="Approve this review?" class="cursor-pointer">
                            Approve Review
                        </flux:button>
                        <flux:button color="red" variant="outline" icon="x-mark" wire:click="reject"
                            wire:confirm="Reject this review?" class="cursor-pointer">
                            Reject Review
                        </flux:button>
                    </div>
                </flux:card>
            @endif

            {{-- Moderation History --}}
            @if ($review->moderated_at)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b dark:border-zinc-600">
                        <flux:heading>Moderation History</flux:heading>
                    </div>
                    <div class="space-y-2 p-5">
                        <div class="flex justify-between">
                            <flux:subheading>Moderated by</flux:subheading>
                            <flux:subheading>{{ $review->moderator->name ?? 'System' }}</flux:subheading>
                        </div>
                        <div class="flex justify-between">
                            <flux:subheading>Moderated at</flux:subheading>
                            <flux:subheading>{{ $review->moderated_at->format('M d, Y g:i A') }}</flux:subheading>
                        </div>
                        <div class="flex justify-between">
                            <flux:subheading>Action taken</flux:subheading>
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
            <flux:card class="p-0">
                <div class="px-3 py-2 border-b dark:border-zinc-600">
                    <flux:heading>Customer</flux:heading>
                </div>
                <div class="space-y-3 p-5">
                    <div>
                        <flux:subheading class="mb-1">Name</flux:subheading>
                        <flux:heading size="sm">{{ $review->user?->name }}</flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="mb-1">Email</flux:subheading>
                        <flux:heading size="sm">{{ $review->user?->email }}</flux:heading>
                    </div>
                    @if ($review->is_verified_purchase)
                        <flux:badge color="green" variant="flat" size="sm" icon="check-badge">
                            Verified Purchase
                        </flux:badge>
                    @endif
                </div>
            </flux:card>

            {{-- Product Info --}}
            <flux:card class="p-0">
                <div class="px-4 py-2 border-b dark:border-zinc-600">
                    <flux:heading>Product</flux:heading>
                </div>
                <div class="flex gap-3 p-5">
                    <div class="w-16 h-16 rounded border dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 overflow-hidden shrink-0">
                        @if ($review->product?->image_path)
                            <img src="{{ $review->product?->image_url }}" class="object-cover w-full h-full">
                        @else
                            <flux:icon.photo class="w-full h-full p-3 text-zinc-300" />
                        @endif
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-1">{{ $review->product?->name }}</flux:heading>
                        <flux:subheading>
                            SKU: {{ $review->product?->sku ?? 'N/A' }}
                        </flux:subheading>
                        <flux:button variant="ghost" size="sm" class="mt-2 cursor-pointer" wire:navigate>
                            View Product
                        </flux:button>
                    </div>
                </div>
            </flux:card>

            {{-- Review Meta --}}
            <flux:card class="p-0">
                <div class="px-3 py-2 border-b dark:border-zinc-600">
                    <flux:heading>Review Information</flux:heading>
                </div>
                <div class="space-y-3 p-5">
                    <div class="flex justify-between">
                        <flux:subheading>Submitted</flux:subheading>
                        <flux:subheading>{{ $review->created_at?->format('M d, Y') }}</flux:subheading>
                    </div>
                    <div class="flex justify-between">
                        <flux:subheading>Time ago</flux:subheading>
                        <flux:subheading>{{ $review->created_at?->diffForHumans() }}</flux:subheading>
                    </div>
                    @if ($review->updated_at != $review->created_at)
                        <div class="flex justify-between">
                            <flux:subheading>Last updated</flux:subheading>
                            <flux:subheading>{{ $review->updated_at->diffForHumans() }}</flux:subheading>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Danger Zone --}}
            <flux:card class="p-0">
                <div class="px-3 py-2 border-b dark:border-zinc-600">
                    <flux:heading class="text-red-600">Danger Zone</flux:heading>
                </div>
                <div class="p-5">
                    <flux:button variant="danger" size="sm" icon="trash" wire:click="delete"
                        class="cursor-pointer"
                        wire:confirm="Are you sure you want to permanently delete this review? This action cannot be undone.">
                        Delete Review Permanently
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>
