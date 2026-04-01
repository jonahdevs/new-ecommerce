<?php
use App\Models\Review;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Reviews')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $ratingFilter = '';

    public function delete($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        $this->dispatch('notify', title: 'Review Deleted', variant: 'danger', message: 'Review deleted successfully.');
    }

    public function quickApprove($id)
    {
        $review = Review::findOrFail($id);
        $review->update([
            'status' => 'approved',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        $this->dispatch('notify', title: 'Review Approved', variant: 'success', message: 'Review approved.');
    }

    public function quickReject($id)
    {
        $review = Review::findOrFail($id);
        $review->update([
            'status' => 'rejected',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        $this->dispatch('notify', title: 'Review Rejected', variant: 'warning', message: 'Review rejected.');
    }

    #[Computed]
    public function reviews()
    {
        return Review::query()
            ->with(['user', 'product', 'images'])
            ->when($this->search, function ($q) {
                $q->whereHas('product', function ($query) {
                    $query->where('name', 'like', "%{$this->search}%");
                })
                    ->orWhereHas('user', function ($query) {
                        $query->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhere('title', 'like', "%{$this->search}%")
                    ->orWhere('review_text', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->ratingFilter, function ($q) {
                $q->where('rating', $this->ratingFilter);
            })
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function pendingCount()
    {
        return Review::where('status', 'pending')->count();
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Reviews</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-1">Reviews</flux:heading>
            <flux:subheading>Manage customer reviews, moderate content, and monitor product feedback.</flux:subheading>
        </div>

        @if ($this->pendingCount > 0)
            <flux:badge color="amber" size="lg">
                {{ $this->pendingCount }} pending review{{ $this->pendingCount !== 1 ? 's' : '' }}
            </flux:badge>
        @endif
    </div>


    <flux:card class="p-0 mt-6 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex items-center gap-4 px-5 py-3 border-b dark:border-zinc-600 ">
            <flux:input wire:model.live="search" icon="magnifying-glass"
                placeholder="Search by product, user, or review content..." class="flex-1 max-w-md" />

            <div class="ms-auto flex items-center gap-3">
                <flux:select wire:model.live="statusFilter" class="w-40">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="approved">Approved</flux:select.option>
                    <flux:select.option value="rejected">Rejected</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="ratingFilter" class="w-40">
                    <flux:select.option value="">All Ratings</flux:select.option>
                    <flux:select.option value="5">5 Stars</flux:select.option>
                    <flux:select.option value="4">4 Stars</flux:select.option>
                    <flux:select.option value="3">3 Stars</flux:select.option>
                    <flux:select.option value="2">2 Stars</flux:select.option>
                    <flux:select.option value="1">1 Star</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->reviews">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Product</flux:table.column>
                <flux:table.column>Review</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->reviews as $review)
                    <flux:table.row :key="$review->id">
                        {{-- Product --}}
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm" class="line-clamp-1">{{ $review->product->name }}</flux:heading>
                        </flux:table.cell>

                        {{-- Review Content --}}
                        <flux:table.cell class="max-w-sm">
                            <flux:subheading class="line-clamp-2">
                                {{ $review->review_text }}
                            </flux:subheading>
                            @if ($review->images->count() > 0)
                                <flux:badge size="sm" color="blue" variant="outline" class="mt-1">
                                    <flux:icon.photo class="w-3 h-3" />
                                    {{ $review->images->count() }}
                                </flux:badge>
                            @endif
                        </flux:table.cell>

                        {{-- Rating --}}
                        <flux:table.cell>
                            <flux:badge icon="star" icon-variant="solid" size="sm"
                                class="[&_[data-flux-badge-icon]]:text-yellow-500!">
                                {{ number_format($review->rating, 1) }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Customer --}}
                        <flux:table.cell>
                            <flux:subheading>{{ $review->user->name }}</flux:subheading>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$review->status?->color()">
                                {{ $review->status?->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell>
                            <flux:subheading>{{ $review->created_at->format('M d, Y') }}</flux:subheading>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="eye" icon-variant="outline"
                                href="{{ route('admin.reviews.show', $review) }}" wire:navigate
                                tooltip="View Details" />

                            @if ($review->status === 'pending')
                                <flux:button variant="ghost" size="sm" icon="check" class="text-green-500!"
                                    icon-variant="outline" wire:confirm="Approve this review?"
                                    wire:click="quickApprove({{ $review->id }})" tooltip="Quick Approve" />

                                <flux:button variant="ghost" size="sm" icon="x-mark" class="text-red-500!"
                                    icon-variant="outline" wire:confirm="Reject this review?"
                                    wire:click="quickReject({{ $review->id }})" tooltip="Quick Reject" />
                            @endif

                            <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500!"
                                icon-variant="outline" wire:confirm="Delete this review permanently?"
                                wire:click="delete({{ $review->id }})" tooltip="Delete" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.chat-bubble-left-right class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No reviews found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->search || $this->statusFilter || $this->ratingFilter)
                                            No results match your current filters.
                                        @else
                                            Customer reviews will appear here once they start rating products.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->statusFilter || $this->ratingFilter)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('statusFilter', ''); $set('ratingFilter', '')">
                                        Clear filters
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>


<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
