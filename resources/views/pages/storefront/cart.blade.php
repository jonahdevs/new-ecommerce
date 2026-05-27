<?php

use App\Enums\StockStatus;
use App\Support\StorefrontSession;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Cart')] class extends Component
{
    public function increment(string $slug): void
    {
        $cart = StorefrontSession::cart();
        StorefrontSession::setCartQty($slug, ($cart[$slug] ?? 0) + 1);
        unset($this->lines);
    }

    public function decrement(string $slug): void
    {
        $cart = StorefrontSession::cart();
        $next = max(1, ($cart[$slug] ?? 1) - 1);
        StorefrontSession::setCartQty($slug, $next);
        unset($this->lines);
    }

    public function remove(string $slug): void
    {
        StorefrontSession::removeFromCart($slug);
        unset($this->lines);
    }

    public function clear(): void
    {
        StorefrontSession::clearCart();
        unset($this->lines);
    }

    #[Computed]
    public function lines(): Collection
    {
        return StorefrontSession::cartLines();
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');

    // Workshop-direction totals (matches the design spec)
    $subtotalCents = $this->lines->sum('line_total_cents');
    $vatCents = (int) round($subtotalCents * 0.16);
    $deliveryCents = $subtotalCents > 50000000 ? 0 : 1200000;     // free over KES 500,000; otherwise KES 12,000
    $totalCents = $subtotalCents + $vatCents + $deliveryCents;
@endphp

<div class="page-fade">
    <div class="shell pt-8 pb-20">
        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-[12.5px] text-ink-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink" wire:navigate>Home</a>
            <flux:icon.chevron-right variant="micro" class="size-3" />
            <span class="text-ink">Cart</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight">Cart</h1>
        <p class="mt-2 text-[14.5px] text-ink-3">
            {{ $this->lines->count() }} {{ \Illuminate\Support\Str::plural('item', $this->lines->count()) }} ·
            ready to check out, or convert to a formal quote.
        </p>

        @if ($this->lines->isEmpty())
            {{-- Empty state --}}
            <div class="mt-10 rounded-lg bg-surface-sunken p-16 text-center">
                <flux:icon.shopping-cart variant="outline" class="mx-auto size-12 text-ink-4" />
                <h2 class="mt-5 font-serif text-2xl">Your cart is empty.</h2>
                <p class="mx-auto mt-2 max-w-md text-ink-3">
                    Browse the catalog and add equipment, or request a formal quote for tendered projects.
                </p>
                <div class="mt-6 flex justify-center gap-2.5">
                    <flux:button variant="primary" :href="route('catalog')" wire:navigate>Shop the catalog</flux:button>
                    <flux:button :href="route('catalog')" wire:navigate>Request a quote</flux:button>
                </div>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1.5fr_1fr]">
                {{-- Lines + quote CTA --}}
                <div>
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        @foreach ($this->lines as $line)
                            @php
                                $product = $line['product'];
                                $cover = $product->images->first();
                                $unitPrice = $product->sale_price ?? $product->price ?? 0;
                                $lineTotal = $line['line_total_cents'];
                                $inStock = $product->stock_status === StockStatus::IN_STOCK;
                            @endphp
                            <div wire:key="line-{{ $line['slug'] }}"
                                class="grid grid-cols-[120px_1fr_auto_auto] items-center gap-5 p-5 {{ ! $loop->last ? 'border-b border-zinc-200' : '' }}">
                                <a href="#" wire:navigate
                                    class="block size-30 cursor-pointer overflow-hidden rounded bg-surface-sunken p-2"
                                    style="width: 120px; height: 120px">
                                    @if ($cover)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($cover->path) }}" alt="" class="size-full object-contain" loading="lazy" />
                                    @endif
                                </a>
                                <div>
                                    @if ($product->brand)
                                        <div class="text-[11.5px] font-bold tracking-[0.06em] text-brand-blue-600 uppercase">{{ $product->brand->name }}</div>
                                    @endif
                                    <a href="#" wire:navigate class="mt-1 block text-[15px] leading-snug font-medium hover:text-brand-500">{{ $product->name }}</a>
                                    <div class="mt-1 text-[12px] text-ink-3">SKU: {{ $product->sku }}</div>
                                    <div class="mt-2 inline-flex items-center gap-1.5 text-[12px] {{ $inStock ? 'text-emerald-700' : 'text-ink-3' }}">
                                        <flux:icon.check variant="micro" class="size-3" />
                                        {{ $inStock ? 'In stock — ships within 3–5 days' : 'Made to order' }}
                                    </div>
                                </div>

                                {{-- Qty stepper --}}
                                <div class="inline-flex h-10 items-center rounded border border-zinc-200">
                                    <button type="button" wire:click="decrement('{{ $line['slug'] }}')" aria-label="Decrease quantity"
                                        class="inline-flex size-9 items-center justify-center text-ink-2 hover:bg-surface-sunken">
                                        <span class="text-sm font-medium">−</span>
                                    </button>
                                    <span class="min-w-7 text-center text-sm font-medium tabular-nums">{{ $line['qty'] }}</span>
                                    <button type="button" wire:click="increment('{{ $line['slug'] }}')" aria-label="Increase quantity"
                                        class="inline-flex size-9 items-center justify-center text-ink-2 hover:bg-surface-sunken">
                                        <span class="text-sm font-medium">+</span>
                                    </button>
                                </div>

                                <div class="min-w-32 text-right">
                                    <div class="font-serif text-xl tabular-nums whitespace-nowrap">{!! $kes($lineTotal) !!}</div>
                                    @if ($line['qty'] > 1)
                                        <div class="mt-0.5 text-[11.5px] text-ink-3">{!! $kes($unitPrice) !!} each</div>
                                    @endif
                                    <button type="button" wire:click="remove('{{ $line['slug'] }}')"
                                        class="mt-2 text-[12px] text-ink-3 underline underline-offset-2 hover:text-brand-500">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Convert-to-quote --}}
                    <div class="mt-6 flex items-center justify-between gap-4 rounded bg-surface-sunken p-5">
                        <div class="flex items-center gap-3">
                            <flux:icon.document-text variant="micro" class="size-5 text-brand-blue-600" />
                            <div>
                                <div class="text-sm font-semibold">Need a formal quotation instead?</div>
                                <div class="text-[12.5px] text-ink-3">Convert this cart to a signed quote with delivery and installation. PDF in 24 hours.</div>
                            </div>
                        </div>
                        <flux:button href="#">Convert to quote →</flux:button>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <a href="{{ route('catalog') }}" wire:navigate
                            class="inline-flex items-center gap-1.5 text-sm text-ink-2 hover:text-ink">
                            <flux:icon.arrow-left variant="micro" class="size-3.5" /> Continue shopping
                        </a>
                        <span class="text-ink-4">·</span>
                        <button type="button" wire:click="clear" wire:confirm="Clear all items from your cart?"
                            class="text-sm text-ink-3 underline-offset-2 hover:text-brand-500 hover:underline">
                            Clear cart
                        </button>
                    </div>
                </div>

                {{-- Summary sidebar --}}
                <aside class="lg:sticky lg:top-44 lg:self-start">
                    <div class="rounded-lg border border-zinc-200 bg-white p-6">
                        <div class="mb-4 font-serif text-xl">Order summary</div>

                        <div class="flex justify-between py-1.5 text-sm text-ink-2">
                            <span>Subtotal</span>
                            <span class="tabular-nums">{!! $kes($subtotalCents) !!}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-sm text-ink-2">
                            <span>VAT (16%)</span>
                            <span class="tabular-nums">{!! $kes($vatCents) !!}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-sm text-ink-2">
                            <span>Delivery — Nairobi metro</span>
                            <span class="tabular-nums">{!! $deliveryCents === 0 ? 'Free' : $kes($deliveryCents) !!}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-sm text-ink-3">
                            <span>Installation</span>
                            <span>Calculated by team</span>
                        </div>

                        <div class="my-4 h-px bg-zinc-200"></div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm font-medium">Total due today</span>
                            <span class="font-serif text-3xl tabular-nums">{!! $kes($totalCents) !!}</span>
                        </div>

                        <div class="mt-4 rounded bg-surface-sunken px-3 py-2.5 text-[12.5px] text-ink-2">
                            <strong>Trade pricing.</strong> Verified business accounts get Net 30 terms.
                            <a href="#" class="text-brand-500 hover:underline">Apply →</a>
                        </div>

                        <flux:button variant="primary" href="#" wire:navigate class="!mt-4 !h-12 !w-full">
                            Checkout →
                        </flux:button>

                        <div class="mt-3 flex items-center justify-center gap-1.5 text-[11.5px] text-ink-3">
                            <flux:icon.shield-check variant="micro" class="size-3" />
                            Secure payment · Card, M-Pesa, bank transfer
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg bg-ink p-5 text-[#f3eadd]" style="background:#0c1421">
                        <div class="flex items-start gap-3">
                            <flux:icon.chat-bubble-left-right variant="micro" class="size-5 shrink-0 text-brand-500" />
                            <div>
                                <div class="text-sm font-semibold">Talk to a specialist</div>
                                <div class="mt-1 text-[12.5px] text-[#c9bea4]">
                                    Sizing, electrical or installation questions before you check out?
                                </div>
                                <a href="tel:+254202345600" class="mt-2 inline-block text-[13px] text-brand-500 hover:underline">
                                    +254&nbsp;20&nbsp;234&nbsp;5600 →
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</div>
