<?php

use App\Models\Quote;
use App\Enums\QuoteStatus;
use Livewire\Component;
use Livewire\Attributes\{Layout, Computed, On};
use Livewire\WithPagination;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    public string $selectedTab = 'active';
    public int $userId;

    public function mount(): void
    {
        $this->userId = auth()->id();
    }

    // =========================================================================
    //  REAL-TIME UPDATES
    //  Listen for quote updates on the user's private channel
    // =========================================================================

    #[On('echo-private:App.Models.User.{userId},.quote.updated')]
    public function handleQuoteUpdate(array $data): void
    {
        // Clear computed caches to refresh the quotations list
        unset($this->hasQuotations, $this->activeQuotations, $this->closedQuotations, $this->awaitingResponseCount);

        // Show toast notification
        $message = match ($data['update_type']) {
            'pricing' => "Quotation #{$data['reference']} has been priced and is ready for review",
            'status' => "Quotation #{$data['reference']} status updated to {$data['status_label']}",
            default => "Quotation #{$data['reference']} has been updated",
        };

        $this->dispatch('notify', title: 'Quotation Updated', variant: 'info', message: $message);
    }

    // =========================================================================
    //  COMPUTED — EXISTENCE CHECK
    // =========================================================================

    #[Computed]
    public function hasQuotations(): bool
    {
        return Quote::where('user_id', auth()->id())->exists();
    }

    // =========================================================================
    //  COMPUTED — ACTIVE QUOTATIONS
    //
    //  Shows quotations that are still in play:
    //    pending  → submitted, awaiting admin pricing
    //    sent     → priced by admin, awaiting customer response ← needs action
    // =========================================================================

    #[Computed]
    public function activeQuotations()
    {
        return Quote::where('user_id', auth()->id())
            ->whereIn('status', [QuoteStatus::PENDING, QuoteStatus::SENT])
            ->with(['items' => fn($q) => $q->with('product')->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(5);
    }

    // =========================================================================
    //  COMPUTED — CLOSED QUOTATIONS
    //
    //  Terminal quotations — accepted, rejected, expired, or cancelled.
    //  Shown separately so the active tab stays focused on actionable items.
    // =========================================================================

    #[Computed]
    public function closedQuotations()
    {
        return Quote::where('user_id', auth()->id())
            ->whereIn('status', [QuoteStatus::ACCEPTED, QuoteStatus::REJECTED, QuoteStatus::EXPIRED, QuoteStatus::CANCELLED])
            ->with(['items' => fn($q) => $q->with('product')->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(5);
    }

    // =========================================================================
    //  COMPUTED — COUNT OF QUOTES NEEDING RESPONSE
    //  Used to show the "action needed" notice at the top of the page.
    // =========================================================================

    #[Computed]
    public function awaitingResponseCount(): int
    {
        return Quote::where('user_id', auth()->id())
            ->where('status', QuoteStatus::SENT)
            ->count();
    }
};
?>

@php
    $tabClass =
        'px-[18px] py-2.5 text-[11px] font-bold tracking-[0.08em] uppercase cursor-pointer border-r border-zinc-200 last:border-r-0 whitespace-nowrap font-barlow transition-all';
    $tabActive = 'bg-primary text-white';
    $tabInactive = 'bg-transparent text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950';
@endphp

<div>
    @if (!$this->hasQuotations)
        {{-- Empty state with same design as actual list --}}
        <div class="flex flex-col bg-white border border-zinc-200">
            <div class="p-12 text-center flex flex-col items-center justify-center min-h-[50svh]">
                <flux:icon.tag class="w-16 h-16 text-zinc-300 mb-4" />
                <h4 class="text-lg font-bold text-zinc-900 mb-2">No quotations yet</h4>
                <p class="text-sm text-zinc-500 max-w-sm">
                    When you request a quote for a product, it will appear here.
                </p>
            </div>
        </div>
    @else
        @if ($this->awaitingResponseCount > 0)
            <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-sm mb-4">
                <flux:icon.clock class="size-5 shrink-0 mt-0.5 text-amber-500" />
                <div class="text-[13px] flex-1">
                    <p class="font-bold text-amber-900">
                        {{ $this->awaitingResponseCount }} {{ Str::plural('quotation', $this->awaitingResponseCount) }}
                        awaiting your response
                    </p>
                    <p class="text-amber-800 mt-0.5">
                        Review the priced quotation(s) below and accept or reject before they expire.
                    </p>
                </div>
            </div>
        @endif

        {{-- Filter tabs --}}
        <div class="flex border-[1.5px] border-zinc-200 bg-white overflow-x-auto mb-5">
            <button wire:click="$set('selectedTab', 'active')"
                class="{{ $tabClass }} {{ $selectedTab === 'active' ? $tabActive : $tabInactive }}">
                Active ({{ $this->activeQuotations->total() }})
            </button>
            <button wire:click="$set('selectedTab', 'closed')"
                class="{{ $tabClass }} {{ $selectedTab === 'closed' ? $tabActive : $tabInactive }}">
                Closed ({{ $this->closedQuotations->total() }})
            </button>
        </div>

        {{-- Quotations list --}}
        <div class="flex flex-col bg-white border border-zinc-200">
            @php $quotations = $selectedTab === 'active' ? $this->activeQuotations : $this->closedQuotations; @endphp

            @forelse ($quotations as $quotation)
                @php
                    $firstItem = $quotation->items->first();
                    $firstProductName =
                        $firstItem?->product_snapshot['name'] ?? ($firstItem?->product?->name ?? 'Product');
                    $needsResponse = $quotation->status === QuoteStatus::SENT;
                    $img = $firstItem?->product_snapshot['image_url'] ?? $firstItem?->product?->image_url;
                @endphp
                <a href="{{ route('customer.quotations.show', $quotation) }}" wire:navigate
                    @class([
                        'p-4.5 border-b border-zinc-200 last:border-b-0 flex items-center gap-4 transition-colors hover:bg-zinc-50 cursor-pointer',
                        'bg-amber-50/30' => $needsResponse,
                    ])>
                    <div class="hidden md:flex">
                        <div
                            class="w-12 h-12 bg-zinc-50 flex items-center justify-center shrink-0 overflow-hidden border-2 border-white">
                            @if ($img)
                                <img src="{{ asset($img) }}" alt="{{ $firstProductName }}"
                                    class="w-[85%] h-[85%] object-contain" />
                            @else
                                <flux:icon.photo class="w-full h-full p-2 text-zinc-200" />
                            @endif
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-bold text-zinc-950 mb-0.5">#{{ $quotation->reference }}</div>
                        <div class="text-[11px] text-zinc-500">{{ $quotation->created_at->format('d M Y') }}</div>
                        <div class="text-[11px] text-zinc-500 mt-0.5">
                            {{ $quotation->items_count }} {{ Str::plural('item', $quotation->items_count) }}
                            @if ($quotation->expires_at && $quotation->status === QuoteStatus::SENT)
                                · <span @class([
                                    'font-bold',
                                    'text-rose-600' => $quotation->expires_at->isPast(),
                                    'text-amber-600' =>
                                        !$quotation->expires_at->isPast() &&
                                        $quotation->expires_at->diffInHours() <= 48,
                                ])>
                                    {{ $quotation->expires_at->isPast() ? 'Expired' : 'Expires' }}
                                    {{ $quotation->expires_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <flux:badge size="sm" :color="$quotation->status->color()">
                        {{ $quotation->status->label() }}
                    </flux:badge>
                    <div class="text-zinc-400 shrink-0">
                        <flux:icon.chevron-right class="w-4 h-4" />
                    </div>
                </a>
            @empty
                <div class="p-12 text-center flex flex-col items-center justify-center">
                    <flux:icon.tag class="w-12 h-12 text-zinc-300 mb-3" />
                    <h4 class="text-lg font-medium text-zinc-900">No quotations found</h4>
                    <p class="text-sm text-zinc-500 mt-1">
                        {{ $selectedTab === 'active' ? 'You have no active quotations at the moment.' : 'You have no closed quotations.' }}
                    </p>
                </div>
            @endforelse
        </div>

        @if ($quotations->hasPages())
            <div class="mt-6">
                {{ $quotations->links() }}
            </div>
        @endif
    @endif
</div>
