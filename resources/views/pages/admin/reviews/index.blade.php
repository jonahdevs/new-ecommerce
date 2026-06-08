<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Reviews — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 10;

    public ?int $viewingId = null;
    public bool $showModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function reviews()
    {
        return Review::query()
            ->with(['product', 'user'])
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('author_name', 'like', $term)
                        ->orWhere('title', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        return [
            'pending' => Review::where('status', ReviewStatus::PENDING)->count(),
            'approved' => Review::where('status', ReviewStatus::APPROVED)->count(),
            'rejected' => Review::where('status', ReviewStatus::REJECTED)->count(),
        ];
    }

    #[Computed]
    public function viewing(): ?Review
    {
        return $this->viewingId ? Review::with('product')->find($this->viewingId) : null;
    }

    public function openReview(int $id): void
    {
        $this->viewingId = $id;
        $this->showModal = true;
    }

    public function approve(int $id): void
    {
        Review::whereKey($id)->update(['status' => ReviewStatus::APPROVED, 'approved_at' => now()]);
        unset($this->reviews, $this->stats);
        Flux::toast(heading: 'Review approved', text: 'It is now visible on the storefront.', variant: 'success');
    }

    public function reject(int $id): void
    {
        Review::whereKey($id)->update(['status' => ReviewStatus::REJECTED, 'approved_at' => null]);
        unset($this->reviews, $this->stats);
        Flux::toast(heading: 'Review rejected', text: 'It will not be shown to customers.', variant: 'success');
    }

    public function delete(int $id): void
    {
        Review::whereKey($id)->delete();
        $this->showModal = false;
        unset($this->reviews, $this->stats);
        Flux::toast(heading: 'Review deleted', text: 'The review has been permanently removed.', variant: 'success');
    }

    /** @return array<int, ReviewStatus> */
    public function statuses(): array
    {
        return ReviewStatus::cases();
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Reviews</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Reviews</flux:heading>
            <flux:subheading>Moderate customer product reviews.</flux:subheading>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card class="flex items-center gap-4">
            <flux:icon.clock class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['pending'] }}</div>
                <flux:text size="sm">Pending</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.check-circle class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['approved'] }}</div>
                <flux:text size="sm">Approved</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.x-circle class="size-9 text-red-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['rejected'] }}</div>
                <flux:text size="sm">Rejected</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search author, product or text…"
                icon="magnifying-glass" clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach ($this->statuses() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Product</flux:table.column>
                <flux:table.column>Review</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->reviews as $review)
                    <flux:table.row :key="$review->id">
                        <flux:table.cell variant="strong">
                            {{ $review->product?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <button type="button" wire:click="openReview({{ $review->id }})" class="block max-w-xs text-left">
                                @if ($review->title)
                                    <span class="font-medium dark:text-white">{{ $review->title }}</span>
                                @endif
                                <span class="block truncate text-xs text-zinc-500">{{ Str::limit($review->body, 60) }}</span>
                                <span class="flex items-center gap-1.5 text-xs text-zinc-400">
                                    {{ $review->author_name }}
                                    @if ($review->verified_purchase)
                                        <flux:badge size="sm" color="green" inset="top bottom">Verified</flux:badge>
                                    @endif
                                </span>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-0.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <flux:icon.star :variant="$i <= $review->rating ? 'solid' : 'outline'" class="size-3.5 text-amber-500" />
                                @endfor
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$review->status->badgeColor()">
                                {{ $review->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['review', $review->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                @if ($review->status !== \App\Enums\ReviewStatus::APPROVED)
                                    <flux:button size="xs" variant="ghost" icon="check" wire:click="approve({{ $review->id }})"
                                        class="text-emerald-600! hover:text-emerald-700!" />
                                @endif
                                @if ($review->status !== \App\Enums\ReviewStatus::REJECTED)
                                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="reject({{ $review->id }})"
                                        class="text-amber-600! hover:text-amber-700!" />
                                @endif
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $review->id }})"
                                    wire:confirm="Delete this review permanently?" class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">No reviews found.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->reviews->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->reviews" />
            </div>
        @endif
    </flux:card>

    {{-- View modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[520px]">
        @if ($this->viewing)
            <flux:heading>{{ $this->viewing->title ?: 'Review' }}</flux:heading>
            <flux:subheading>{{ $this->viewing->product?->name }}</flux:subheading>

            <div class="mt-4 flex items-center gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <flux:icon.star :variant="$i <= $this->viewing->rating ? 'solid' : 'outline'" class="size-4 text-amber-500" />
                @endfor
            </div>

            <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $this->viewing->body }}</p>

            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                <span>{{ $this->viewing->author_name }} · {{ $this->viewing->created_at->format('d M Y') }}</span>
                @if ($this->viewing->verified_purchase)
                    <flux:badge size="sm" color="green" inset="top bottom">Verified Purchase</flux:badge>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-2">
                @if ($this->viewing->status !== \App\Enums\ReviewStatus::REJECTED)
                    <flux:button variant="ghost" wire:click="reject({{ $this->viewing->id }})">Reject</flux:button>
                @endif
                @if ($this->viewing->status !== \App\Enums\ReviewStatus::APPROVED)
                    <flux:button variant="primary" wire:click="approve({{ $this->viewing->id }})">Approve</flux:button>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
