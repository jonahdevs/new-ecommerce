<?php

use App\Enums\ProductLinkType;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Support\StorefrontSession;
use Illuminate\Support\Facades\DB;
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

    #[Computed]
    public function crossSells(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->lines->isEmpty()) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        $cartProductIds = $this->lines->pluck('product.id')->filter()->unique()->values()->all();

        $crossSellIds = DB::table('product_links')
            ->whereIn('product_id', $cartProductIds)
            ->where('type', ProductLinkType::CROSS_SELL->value)
            ->orderBy('sort_order')
            ->pluck('linked_product_id')
            ->unique()
            ->diff($cartProductIds)
            ->take(4)
            ->all();

        if (empty($crossSellIds)) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Product::query()
            ->whereIn('id', $crossSellIds)
            ->where('visibility', 'visible')
            ->with(['brand:id,name', 'taxClass:id,rate,is_inclusive', 'media'])
            ->get();
    }
}; ?>

@php
    $tax           = app(\App\Support\TaxCalculator::class);
    $subtotalCents = $this->lines->sum('line_total_cents');
    $vatCents      = $tax->taxForCart($this->lines);
    $taxInclusive  = $tax->pricesIncludeTax();
    $vatRates      = $this->lines->map(fn ($l) => (float) $tax->rateForProduct($l['product']))->filter()->unique();
    $vatRateLabel  = $vatRates->count() === 1
        ? 'VAT '.rtrim(rtrim(number_format($vatRates->first(), 2), '0'), '.').'%'
        : 'VAT (mixed rates)';
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
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Cart</h1>
            @if ($this->lines->isNotEmpty())
                <flux:modal.trigger name="confirm-clear-cart">
                    <flux:button variant="customer-danger" size="customer">Clear cart</flux:button>
                </flux:modal.trigger>
            @endif
        </div>

        @if ($this->lines->isEmpty())
            <div class="mt-10 flex flex-col items-center justify-center px-6 py-16 text-center">
                <img src="{{ asset('images/empty-states/empty-cart.svg') }}" alt="Your cart is empty"
                    class="mx-auto h-72 w-72" />
                <h2 class="mt-6 text-xl font-semibold sm:text-2xl">Your cart is empty.</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-ink-3">Browse the catalog and add equipment, or request a formal quote for tendered projects.</p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate>
                        <flux:icon.shopping-bag variant="micro" class="size-3.5" />
                        Start shopping
                    </flux:button>
                    <flux:button variant="customer-outline" size="customer" :href="route('home')" wire:navigate>Back to home</flux:button>
                </div>
            </div>

        @else
            <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

                {{-- ================================================== --}}
                {{-- ITEMS TABLE --}}
                {{-- ================================================== --}}
                <div class="flex-1 min-w-0">
                    <div class="overflow-x-auto rounded-md border border-zinc-200">
                    <table class="w-full min-w-[600px] bg-white lg:min-w-0">
                        <thead>
                            <tr class="bg-zinc-50 text-[11px] font-bold tracking-widest text-ink-3 uppercase">
                                <th class="px-4 py-3 xl:px-6 text-left border-b border-zinc-200">Product</th>
                                <th class="px-4 py-3 xl:px-6 text-center border-b border-zinc-200">Price</th>
                                <th class="px-4 py-3 xl:px-6 text-center border-b border-zinc-200">Quantity</th>
                                <th class="px-4 py-3 xl:px-6 text-right border-b border-zinc-200">Subtotal</th>
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
                                <td class="px-4 py-5 xl:px-6">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <a href="{{ route('product.show', $product) }}" wire:navigate
                                           class="size-20 shrink-0 overflow-hidden">
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
                                                    <flux:icon.trash-2 variant="micro" class="size-3.5" />
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Unit price --}}
                                <td class="px-4 py-5 xl:px-6 text-center text-[14px] font-medium text-ink tabular-nums whitespace-nowrap">
                                    {!! money($unitPrice) !!}
                                </td>

                                {{-- Qty stepper --}}
                                <td class="px-4 py-5 xl:px-6 text-center">
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
                                <td class="px-4 py-5 xl:px-6 text-right text-[14px] font-semibold text-ink tabular-nums whitespace-nowrap">
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

                {{-- ================================================== --}}
                {{-- CART SUMMARY SIDEBAR --}}
                {{-- ================================================== --}}
                <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-80 xl:w-96">
                    <div class="rounded-md border border-zinc-200 bg-white">
                        <div class="border-b border-zinc-200 px-6 py-4">
                            <flux:heading size="sm" class="uppercase tracking-wide">Cart summary</flux:heading>
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
                                    <span>{{ $vatRateLabel }}@if ($taxInclusive) <span class="text-xs opacity-60">(incl.)</span>@endif</span>
                                    <span class="font-medium tabular-nums">{!! money($vatCents) !!}</span>
                                </div>
                            @endif
                        </div>

                        <div class="my-5 h-px bg-zinc-100"></div>

                        <div class="flex items-center justify-between">
                            <span class="text-[13px] font-bold tracking-wide uppercase">Total</span>
                            <span class="text-2xl font-bold text-brand-500 tabular-nums">{!! money($totalCents) !!}</span>
                        </div>

                        <flux:button variant="customer-primary" size="customer-lg" :href="route('checkout')" wire:navigate icon:trailing="chevron-right" class="mt-5! w-full!">
                            Proceed to checkout
                        </flux:button>

                        <div class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                            <flux:icon.shield-check variant="micro" class="size-3.5" />
                            SSL encrypted &amp; secure
                        </div>

                        {{-- Payment methods --}}
                        <div class="mt-5">
                            <div class="mb-2 text-[10.5px] font-bold tracking-widest text-ink-4 uppercase">We accept</div>
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
        @if ($this->crossSells->isNotEmpty())
            <div class="mt-16">
                <div class="mb-4">
                    <h2 class="text-[22px] font-semibold tracking-tight">Customers Also Buy</h2>
                    <p class="mt-1 text-[13px] text-ink-3">Frequently purchased alongside items in your cart.</p>
                </div>
                <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
                    @foreach ($this->crossSells as $cs)
                        <x-storefront.product-card :product="$cs" wire:key="cs-{{ $cs->id }}" />
                    @endforeach
                </div>
            </div>
        @endif

        @endif
    </div>

    @include('partials.storefront.accessory-modal')

    {{-- Clear cart confirmation --}}
    <flux:modal name="confirm-clear-cart" class="max-w-sm">
        <flux:heading size="lg" class="uppercase tracking-wide">Clear your cart?</flux:heading>
        <flux:subheading class="mt-2">All {{ $this->lines->count() }} {{ \Illuminate\Support\Str::plural('item', $this->lines->count()) }} will be removed. This cannot be undone.</flux:subheading>
        <div class="mt-6 flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" class="flex-1" wire:click="clear" x-on:click="$flux.modal('confirm-clear-cart').close()">
                Yes, clear cart
            </flux:button>
        </div>
    </flux:modal>
</div>
