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
        session()->flash('status', 'Review deleted successfully.');
    }

    public function quickApprove($id)
    {
        $review = Review::findOrFail($id);
        $review->update([
            'status' => 'approved',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        session()->flash('status', 'Review approved.');
    }

    public function quickReject($id)
    {
        $review = Review::findOrFail($id);
        $review->update([
            'status' => 'rejected',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        session()->flash('status', 'Review rejected.');
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


    <flux:card class="p-0 mt-6 **:data-flux-columns:bg-zinc-50">
        {{-- Filters --}}
        <div class="flex items-center gap-4 px-5 py-3 border-b ">
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
                <flux:table.column class="ps-4!">Review</flux:table.column>
                <flux:table.column>Product</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->reviews as $review)
                    <flux:table.row :key="$review->id">
                        {{-- Review Content --}}
                        <flux:table.cell class="max-w-md ps-4!">
                            <flux:heading class="mb-1">
                                {{ $review->title ?? 'No title' }}
                            </flux:heading>
                            <flux:text class="line-clamp-2 whitespace-normal wrap-break-words">
                                {{ $review->review_text }}
                            </flux:text>
                            @if ($review->images->count() > 0)
                                <div class="flex gap-1 mt-2">
                                    <flux:badge size="sm" color="blue" variant="outline">
                                        <flux:icon name="photo" class="w-3 h-3" />
                                        {{ $review->images->count() }}
                                        {{ $review->images->count() === 1 ? 'image' : 'images' }}
                                    </flux:badge>
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Product --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden shrink-0">
                                    @if ($review->product->image_path)
                                        <img src="{{ $review->product->image_url }}" class="object-cover w-full h-full">
                                    @else
                                        <flux:icon name="photo" class="w-full h-full p-1.5 text-zinc-300" />
                                    @endif
                                </div>
                                <div class="text-sm font-medium">{{ $review->product->name }}</div>
                            </div>
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
                            <flux:link class="text-xs">{{ $review->user->email }}</flux:link>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$review->status?->color()">
                                {{ $review->status?->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell>
                            <div class="text-sm">{{ $review->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $review->created_at->diffForHumans() }}</div>
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
                @endforeach
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
