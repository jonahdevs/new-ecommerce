<?php

use App\Enums\StockStatus;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Cart')] class extends Component
{
    use \App\Livewire\Concerns\InteractsWithStorefront;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    public function increment(string $key): void
    {
        $cart = StorefrontSession::cart();
        StorefrontSession::setCartQty($key, ($cart[$key] ?? 0) + 1);
        unset($this->lines);
        $this->dispatch('cart-updated');
    }

    public function decrement(string $key): void
    {
        $cart = StorefrontSession::cart();
        StorefrontSession::setCartQty($key, max(1, ($cart[$key] ?? 1) - 1));
        unset($this->lines);
        $this->dispatch('cart-updated');
    }

    public function remove(string $key): void
    {
        StorefrontSession::removeFromCart($key);
        unset($this->lines);
        $this->dispatch('cart-updated');
        Flux::toast(heading: 'Item removed', text: 'The item has been removed from your cart.', variant: 'warning');
    }

    public function clear(): void
    {
        StorefrontSession::clearCart();
        unset($this->lines);
        $this->dispatch('cart-updated');
        Flux::toast(heading: 'Cart cleared', text: 'All items have been removed from your cart.', variant: 'danger');
    }

    #[Computed]
    public function lines(): Collection
    {
        return StorefrontSession::cartLines();
    }
}; ?>

@php
    $tax           = app(\App\Support\TaxCalculator::class);
    $subtotalCents = $this->lines->sum('line_total_cents');
    $vatCents      = $tax->taxForCart($this->lines);
    $taxInclusive  = $tax->pricesIncludeTax();
    $deliveryCents = $subtotalCents > 50000000 ? 0 : 1200000;
    $totalCents    = $taxInclusive
        ? $subtotalCents + $deliveryCents
        : $subtotalCents + $vatCents + $deliveryCents;
@endphp

<div class="page-fade">
    <div class="shell pt-4 pb-20">

        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Cart</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-semibold tracking-tight">Cart</h1>
            @if ($this->lines->isNotEmpty())
                <flux:button variant="customer-danger" size="customer" wire:click="clear" wire:confirm="Remove all items from your cart?">
                    Clear cart
                </flux:button>
            @endif
        </div>

        @if ($this->lines->isEmpty())
            <div class="mt-10 rounded-md border border-zinc-200 bg-white p-16 text-center">
                <flux:icon.shopping-cart variant="outline" class="mx-auto size-12 text-ink-4" />
                <h2 class="mt-5 text-xl font-semibold">Your cart is empty.</h2>
                <p class="mx-auto mt-2 max-w-md text-ink-3">Browse the catalog and add equipment, or request a formal quote for tendered projects.</p>
                <div class="mt-6 flex justify-center gap-2.5">
                    <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate>Shop the catalog</flux:button>
                    <flux:button variant="customer-outline" size="customer" :href="route('quote.request')" wire:navigate>Request a quote</flux:button>
                </div>
            </div>

        @else
            <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

                {{-- ── Items table ── --}}
                <div class="flex-1 min-w-0">
                    <div class="overflow-hidden rounded-md border border-zinc-200">
                    <table class="w-full bg-white">
                        <thead>
                            <tr class="bg-surface-sunken text-[11px] font-bold tracking-[0.1em] text-ink-3 uppercase">
                                <th class="px-6 py-3 text-left border-b border-zinc-200">Product</th>
                                <th class="px-6 py-3 text-center border-b border-zinc-200">Price</th>
                                <th class="px-6 py-3 text-center border-b border-zinc-200">Quantity</th>
                                <th class="px-6 py-3 text-right border-b border-zinc-200">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($this->lines as $line)
                            @php
                                $product   = $line['product'];
                                $unitPrice = $line['unit_price_cents'];
                                $lineTotal = $line['line_total_cents'];
                                $inStock   = $product->stock_status === StockStatus::IN_STOCK;
                                $isWished  = StorefrontSession::isWishlisted($product->slug);
                            @endphp
                            <tr wire:key="line-{{ $line['key'] }}" class="{{ ! $loop->last ? 'border-b border-zinc-100' : '' }}">

                                {{-- Product --}}
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <a href="{{ route('product.show', $product) }}" wire:navigate
                                           class="size-20 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1.5">
                                            @if ($product->cover_url)
                                                <img src="{{ $product->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                            @endif
                                        </a>
                                        <div class="min-w-0">
                                            @if ($product->brand)
                                                <div class="text-[10.5px] font-bold tracking-[0.08em] text-brand-blue-600 uppercase">{{ $product->brand->name }}</div>
                                            @endif
                                            <a href="{{ route('product.show', $product) }}" wire:navigate
                                               class="mt-0.5 block text-[14px] font-semibold leading-snug text-ink hover:text-brand-500">
                                                {{ $product->name }}
                                            </a>
                                            @if ($line['label'])
                                                <div class="mt-0.5 text-[11.5px] text-ink-3">{{ $line['label'] }}</div>
                                            @endif
                                            <div class="mt-2 flex items-center gap-3 text-[11.5px] text-ink-4">
                                                <button type="button" wire:click="toggleWishlist('{{ $product->slug }}')"
                                                        class="inline-flex cursor-pointer items-center gap-1 transition hover:text-brand-500">
                                                    <flux:icon.heart variant="micro" class="size-3.5 {{ $isWished ? 'text-brand-500' : '' }}" />
                                                    {{ $isWished ? 'Saved' : 'Save for later' }}
                                                </button>
                                                <span class="text-zinc-300">|</span>
                                                <button type="button" wire:click="remove('{{ $line['key'] }}')"
                                                        class="inline-flex cursor-pointer items-center gap-1 transition hover:text-brand-500">
                                                    <flux:icon.trash variant="micro" class="size-3.5" />
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Unit price --}}
                                <td class="px-6 py-5 text-center text-[14px] font-medium text-ink tabular-nums whitespace-nowrap">
                                    {!! money($unitPrice) !!}
                                </td>

                                {{-- Qty stepper --}}
                                <td class="px-6 py-5 text-center">
                                    <div class="inline-flex items-center rounded border border-zinc-200">
                                        <button type="button" wire:click="decrement('{{ $line['key'] }}')"
                                                class="flex size-9 cursor-pointer items-center justify-center text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                            <span class="text-base leading-none">−</span>
                                        </button>
                                        <span class="min-w-8 text-center text-sm font-semibold tabular-nums">{{ $line['qty'] }}</span>
                                        <button type="button" wire:click="increment('{{ $line['key'] }}')"
                                                class="flex size-9 cursor-pointer items-center justify-center text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                            <span class="text-base leading-none">+</span>
                                        </button>
                                    </div>
                                </td>

                                {{-- Line total --}}
                                <td class="px-6 py-5 text-right text-[14px] font-semibold text-ink tabular-nums whitespace-nowrap">
                                    {!! money($lineTotal) !!}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>

                    {{-- Continue shopping --}}
                    <div class="mt-5">
                        <flux:button variant="customer-outline" size="customer" icon="arrow-left" :href="route('catalog')" wire:navigate>
                            Continue shopping
                        </flux:button>
                    </div>
                </div>

                {{-- ── Cart summary sidebar ── --}}
                <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-96">
                    <div class="rounded-md border border-zinc-200 bg-white">
                        <div class="border-b border-zinc-200 px-6 py-4">
                            <h2 class="text-[11px] font-bold tracking-[0.14em] text-ink uppercase">Cart summary</h2>
                        </div>

                        <div class="p-6">
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>Subtotal</span>
                                <span class="font-medium tabular-nums">{!! money($subtotalCents) !!}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>Shipping</span>
                                <span class="{{ $deliveryCents === 0 ? 'font-medium text-emerald-600' : 'font-medium tabular-nums' }}">
                                    {!! $deliveryCents === 0 ? 'Free' : money($deliveryCents) !!}
                                </span>
                            </div>
                            @if ($tax->enabled() && $vatCents > 0)
                                <div class="flex items-center justify-between text-sm text-ink-2">
                                    <span>VAT{{ $taxInclusive ? ' (incl.)' : '' }}</span>
                                    <span class="font-medium tabular-nums">{!! money($vatCents) !!}</span>
                                </div>
                            @endif
                        </div>

                        <div class="my-5 h-px bg-zinc-100"></div>

                        <div class="flex items-center justify-between">
                            <span class="text-[13px] font-bold tracking-wide uppercase">Total</span>
                            <span class="text-2xl font-bold text-brand-500 tabular-nums">{!! money($totalCents) !!}</span>
                        </div>

                        <flux:button variant="customer-primary" size="customer-lg" :href="route('checkout')" wire:navigate icon:trailing="arrow-right" class="mt-5! w-full!">
                            Proceed to checkout
                        </flux:button>

                        <div class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                            <flux:icon.shield-check variant="micro" class="size-3.5" />
                            SSL encrypted &amp; secure
                        </div>

                        {{-- Payment methods --}}
                        <div class="mt-5">
                            <div class="mb-2 text-[10.5px] font-bold tracking-[0.1em] text-ink-4 uppercase">We accept</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (['Visa', 'M-Pesa', 'Mastercard', 'Bank transfer'] as $method)
                                    <span class="rounded border border-zinc-200 px-2.5 py-1 text-[10.5px] font-semibold text-ink-3 uppercase tracking-wide">
                                        {{ $method }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Trust signals --}}
                        <div class="mt-5 flex flex-col gap-2 text-[12px] text-ink-3">
                            <span class="flex items-center gap-2">
                                <flux:icon.arrow-path variant="micro" class="size-3.5 text-brand-500" />
                                30-day returns policy
                            </span>
                            <span class="flex items-center gap-2">
                                <flux:icon.truck variant="micro" class="size-3.5 text-brand-500" />
                                Free delivery within Nairobi
                            </span>
                        </div>
                        </div>
                    </div>
                </aside>

            </div>
        @endif
    </div>
</div>
