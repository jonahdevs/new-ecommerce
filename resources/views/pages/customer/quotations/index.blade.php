<?php

use App\Models\Quote;
use App\Enums\QuoteStatus;
use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use Livewire\WithPagination;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    public string $selectedTab = 'active';

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
            ->whereIn('status', [
                QuoteStatus::ACCEPTED,
                QuoteStatus::REJECTED,
                QuoteStatus::EXPIRED,
                QuoteStatus::CANCELLED,
            ])
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

<div>
    <flux:card class="p-0 rounded-md">

        {{-- Page Header --}}
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <flux:heading size="lg" level="1">My Quotations</flux:heading>
            <flux:link :href="route('customer.orders.index')" wire:navigate class="text-xs flex items-center gap-1">
                <flux:icon.shopping-bag class="size-3.5 inline-block me-2" />
                <span>My Orders</span>
            </flux:link>
        </div>

        <div class="px-4 py-4">

            @if (!$this->hasQuotations)
                {{-- Empty state --}}
                <div class="min-h-[50svh] flex flex-col items-center gap-2 justify-center text-center">
                    <flux:icon.tag class="size-12 text-zinc-300" />
                    <flux:heading>No quotations yet</flux:heading>
                    <flux:text class="text-zinc-500 max-w-sm">
                        When you request a quote for a product,
                        it will appear here.
                    </flux:text>
                    <flux:button :href="route('shop.index')" variant="primary" icon="shopping-bag" wire:navigate
                        class="mt-2">
                        Browse Products
                    </flux:button>
                </div>
            @else
                {{-- Action needed banner — only shown when a quote is waiting for response --}}
                @if ($this->awaitingResponseCount > 0)
                    <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg mb-4">
                        <flux:icon.clock class="size-5 shrink-0 mt-0.5 text-amber-500" />
                        <div class="text-sm flex-1">
                            <p class="font-medium text-amber-800">
                                {{ $this->awaitingResponseCount }}
                                {{ Str::plural('quotation', $this->awaitingResponseCount) }}
                                awaiting your response
                            </p>
                            <p class="text-amber-700 mt-0.5 text-xs">
                                Review the priced quotation(s) below and accept or reject before they expire.
                            </p>
                        </div>
                    </div>
                @endif

                <x-my-tabs wire:model="selectedTab">

                    {{-- Active quotations --}}
                    <x-my-tab name="active" label="Active">
                        <div class="space-y-3">
                            @forelse ($this->activeQuotations as $quotation)
                                @php
                                    $firstItem = $quotation->items->first();
                                    $firstProductName =
                                        $firstItem?->product_snapshot['name'] ??
                                        ($firstItem?->product?->name ?? 'Product');
                                    $extraCount = $quotation->items_count - 1;
                                    $needsResponse = $quotation->status === QuoteStatus::SENT;
                                    $img = $firstItem?->product_snapshot['image_url'] ?? $firstItem?->product?->image_url;
                                @endphp

                                <div wire:key="active-{{ $quotation->id }}"
                                    class="border rounded-md p-4 hover:bg-zinc-50 transition-colors
                                        {{ $needsResponse ? 'border-amber-300 bg-amber-50/50' : '' }}">
                                    <div class="flex items-center justify-between gap-4">

                                        {{-- Product image --}}
                                        <div class="shrink-0">
                                            <div class="w-12 h-12 rounded-md border bg-zinc-100 overflow-hidden">
                                                @if ($img)
                                                    <img src="{{ asset($img) }}" alt="{{ $firstProductName }}"
                                                        class="w-full h-full object-cover" />
                                                @else
                                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Quotation info --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-800 truncate">
                                                {{ $firstProductName }}
                                                @if ($extraCount > 0)
                                                    <span class="text-zinc-400 font-normal">
                                                        + {{ $extraCount }} more
                                                    </span>
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $quotation->reference }}
                                                </flux:text>
                                                <span class="text-zinc-200">·</span>
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $quotation->created_at->format('M j, Y') }}
                                                </flux:text>
                                                <flux:badge size="sm" :color="$quotation->status->color()">
                                                    {{ $quotation->status->label() }}
                                                </flux:badge>
                                            </div>

                                            {{-- Expiry warning --}}
                                            @if ($quotation->expires_at && $quotation->status === QuoteStatus::SENT)
                                                <flux:text
                                                    class="text-xs mt-1
                                                    {{ $quotation->expires_at->isPast()
                                                        ? 'text-rose-500'
                                                        : ($quotation->expires_at->diffInHours() <= 48
                                                            ? 'text-amber-600'
                                                            : 'text-zinc-400') }}">
                                                    {{ $quotation->expires_at->isPast() ? 'Expired' : 'Expires' }}
                                                    {{ $quotation->expires_at->diffForHumans() }}
                                                </flux:text>
                                            @endif
                                        </div>

                                        {{-- Action --}}
                                        <flux:button :href="route('customer.quotations.show', $quotation)" wire:navigate
                                            :variant="$needsResponse ? 'primary' : 'ghost'" size="sm"
                                            class="shrink-0">
                                            {{ $needsResponse ? 'Respond' : 'See details' }}
                                        </flux:button>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.tag class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No active quotations</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">
                                        You have no active quotations at the moment.
                                    </flux:text>
                                </div>
                            @endforelse
                        </div>

                        @if ($this->activeQuotations->hasPages())
                            <div class="mt-4">
                                <flux:pagination :paginator="$this->activeQuotations" />
                            </div>
                        @endif
                    </x-my-tab>

                    {{-- Closed quotations --}}
                    <x-my-tab name="closed" label="Closed">
                        <div class="space-y-3">
                            @forelse ($this->closedQuotations as $quotation)
                                @php
                                    $firstItem = $quotation->items->first();
                                    $firstProductName =
                                        $firstItem?->product_snapshot['name'] ??
                                        ($firstItem?->product?->name ?? 'Product');
                                    $extraCount = $quotation->items_count - 1;
                                    $img = $firstItem?->product_snapshot['image_url'] ?? $firstItem?->product?->image_url;
                                @endphp

                                <div wire:key="closed-{{ $quotation->id }}"
                                    class="border rounded-md p-4 hover:bg-zinc-50 transition-colors opacity-75">
                                    <div class="flex items-center justify-between gap-4">

                                        <div class="shrink-0">
                                            <div class="w-12 h-12 rounded-md border bg-zinc-100 overflow-hidden">
                                                @if ($img)
                                                    <img src="{{ asset($img) }}" alt="{{ $firstProductName }}"
                                                        class="w-full h-full object-cover opacity-60" />
                                                @else
                                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-500 truncate">
                                                {{ $firstProductName }}
                                                @if ($extraCount > 0)
                                                    <span class="text-zinc-400 font-normal">
                                                        + {{ $extraCount }} more
                                                    </span>
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $quotation->reference }}
                                                </flux:text>
                                                <span class="text-zinc-200">·</span>
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ $quotation->created_at->format('M j, Y') }}
                                                </flux:text>
                                                <flux:badge size="sm" :color="$quotation->status->color()">
                                                    {{ $quotation->status->label() }}
                                                </flux:badge>
                                            </div>
                                        </div>

                                        <flux:button :href="route('customer.quotations.show', $quotation)" wire:navigate
                                            variant="ghost" size="sm" class="shrink-0">
                                            See details
                                        </flux:button>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <flux:icon.check-circle class="w-12 h-12 text-zinc-300 mb-3" />
                                    <flux:heading size="sm">No closed quotations</flux:heading>
                                    <flux:text class="text-zinc-500 mt-1 text-sm">
                                        You have no rejected, expired, or cancelled quotations.
                                    </flux:text>
                                </div>
                            @endforelse
                        </div>

                        @if ($this->closedQuotations->hasPages())
                            <div class="mt-4">
                                <flux:pagination :paginator="$this->closedQuotations" />
                            </div>
                        @endif
                    </x-my-tab>

                </x-my-tabs>
            @endif
        </div>
    </flux:card>
</div>
