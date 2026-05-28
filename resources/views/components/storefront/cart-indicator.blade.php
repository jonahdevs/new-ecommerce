<?php

use App\Support\StorefrontSession;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[On('cart-updated')]
    public function refresh(): void {}
}; ?>

@php
    $cartCount    = StorefrontSession::cartCount();
    $lines        = StorefrontSession::cartLines();
    $totalCents   = $lines->sum('line_total_cents');
    $kes          = fn ($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');
@endphp

<flux:dropdown position="bottom" align="end" gap="10">
    <button type="button" aria-label="Cart"
        class="relative inline-flex size-10 cursor-pointer items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken hover:text-ink">
        <flux:icon.shopping-cart variant="micro" class="size-5" />
        @if ($cartCount > 0)
            <span class="absolute top-1 right-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-bold text-white tabular-nums">{{ $cartCount }}</span>
        @endif
    </button>

    <div popover="manual"
        class="w-80 overflow-hidden rounded-md border border-zinc-200 bg-white shadow-xl focus:outline-hidden">

        @if ($lines->isEmpty())
            <div class="px-5 py-8 text-center">
                <flux:icon.shopping-cart variant="outline" class="mx-auto size-8 text-ink-4" />
                <div class="mt-3 text-[14px] font-medium text-ink">Your cart is empty</div>
                <p class="mt-1 text-[12.5px] text-ink-3">Browse equipment and add items to get started.</p>
                <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate class="mt-4">
                    Shop the catalog
                </flux:button>
            </div>
        @else
            {{-- Line items --}}
            <div class="max-h-72 overflow-y-auto divide-y divide-zinc-100 pt-2">
                @foreach ($lines as $line)
                    @php $product = $line['product']; @endphp
                    <div wire:key="dd-{{ $line['slug'] }}" class="flex items-center gap-3 px-4 py-3.5">
                        <div class="size-12 shrink-0 overflow-hidden rounded-md border border-zinc-100 bg-surface-sunken p-1">
                            @if ($product->cover_url)
                                <img src="{{ $product->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('product.show', $product) }}" wire:navigate
                               class="line-clamp-2 text-[13px] font-semibold leading-snug text-ink hover:text-brand-500">
                                {{ $product->name }}
                            </a>
                            <div class="mt-0.5 text-[12px] text-brand-500 tabular-nums">
                                {{ $line['qty'] }} × {!! $kes($product->sale_price ?? $product->price ?? 0) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Total + actions --}}
            <div class="border-t border-zinc-100 px-4 py-4">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-semibold text-ink">Total</span>
                    <span class="text-[13px] font-bold text-brand-500 tabular-nums">{!! $kes($totalCents) !!}</span>
                </div>
                <div class="mt-3 flex gap-2">
                    <flux:button variant="customer-outline" size="customer" :href="route('cart')" wire:navigate class="flex-1!">View cart</flux:button>
                    <flux:button variant="customer-primary" size="customer" href="#" class="flex-1!">Checkout</flux:button>
                </div>
            </div>
        @endif
    </div>
</flux:dropdown>
