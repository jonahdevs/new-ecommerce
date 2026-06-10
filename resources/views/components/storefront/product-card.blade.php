@props(['product'])

@php
    $brandName  = $product->brand?->name;
    $tax        = app(\App\Support\TaxCalculator::class);
    $price      = $product->sale_price ?? $product->price;
    $compareAt  = $product->sale_price ? $product->price : null;
    $price      = $price !== null ? $tax->displayPriceCents($product, (int) $price) : null;
    $compareAt  = $compareAt !== null ? $tax->displayPriceCents($product, (int) $compareAt) : null;
    $priceLabel = $price ? money($price) : 'Request quote';
    $compareLabel = $compareAt ? money($compareAt) : null;
$isWished   = \App\Support\StorefrontSession::isWishlisted($product->slug);
    $isCompared = \App\Support\StorefrontSession::isCompared($product->slug);
    $cartQty    = \App\Support\StorefrontSession::cartQuantity($product->slug);
    $discount   = ($compareAt && $price && $compareAt > $price)
        ? (int) round((1 - $price / $compareAt) * 100)
        : null;
    // Variable + grouped products need a choice made on the product page, so they
    // link there instead of quick-adding the parent from the card.
    $needsOptions = in_array($product->type, [\App\Enums\ProductType::VARIABLE, \App\Enums\ProductType::GROUPED], true);
    // Quote-only and unpriced products can't be quick-added (there's no price to
    // charge); they route to the product page where the quote flow lives.
    $isQuoteOnly = $product->requires_quotation || ($product->sale_price ?? $product->price) === null;
@endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded border border-zinc-200 bg-white transition hover:shadow-md']) }}>

    {{-- Image area --}}
    <div class="relative aspect-[4/3] overflow-hidden bg-surface-sunken">

        {{-- Clickable image link fills the entire area --}}
        <a href="{{ route('product.show', $product) }}" wire:navigate class="absolute inset-0">
            @if ($product->cover_url)
                <img src="{{ $product->cover_url }}" alt="{{ $product->name }}"
                    loading="lazy" class="size-full object-cover object-center" />
            @else
                <div class="flex size-full items-center justify-center text-ink-4">
                    <flux:icon.photo class="size-12" />
                </div>
            @endif
        </a>

        {{-- Discount badge --}}
        @if ($discount)
            <span class="absolute top-2.5 left-0 z-10 inline-flex h-5 items-center rounded-r bg-brand-500 px-2 text-[10.5px] font-bold tracking-wider text-white">
                −{{ $discount }}%
            </span>
        @endif

        {{-- Wishlist + compare — top right, hover-reveal --}}
        <div class="absolute top-2.5 right-2.5 z-10 flex flex-col gap-1.5">
            <flux:tooltip :content="$isWished ? 'Remove from wishlist' : 'Save to wishlist'" position="left">
                <button type="button" wire:click="toggleWishlist('{{ $product->slug }}')"
                    aria-label="{{ $isWished ? 'Remove from wishlist' : 'Save to wishlist' }}"
                    @class([
                        'inline-flex size-8 cursor-pointer items-center justify-center rounded-full border shadow-sm transition',
                        'border-brand-500 bg-brand-500 text-white' => $isWished,
                        'border-zinc-200 bg-white/95 text-ink opacity-0 hover:bg-white group-hover:opacity-100' => ! $isWished,
                    ])>
                    <flux:icon.heart variant="micro" class="size-4" />
                </button>
            </flux:tooltip>

            <flux:tooltip :content="$isCompared ? 'Remove from compare' : 'Add to compare'" position="left">
                <button type="button" wire:click="toggleCompare('{{ $product->slug }}')"
                    aria-label="{{ $isCompared ? 'Remove from compare' : 'Add to compare' }}"
                    @class([
                        'inline-flex size-8 cursor-pointer items-center justify-center rounded-full border shadow-sm transition',
                        'border-ink bg-ink text-white' => $isCompared,
                        'border-zinc-200 bg-white/95 text-ink opacity-0 hover:bg-white group-hover:opacity-100' => ! $isCompared,
                    ])>
                    <flux:icon.scale variant="micro" class="size-4" />
                </button>
            </flux:tooltip>
        </div>

        {{-- Variable / grouped: route to the product page to choose options --}}
        @if ($needsOptions)
            <a href="{{ route('product.show', $product) }}" wire:navigate aria-label="Select options"
               class="absolute right-2.5 bottom-2.5 z-10 inline-flex h-9 items-center gap-1.5 rounded-full bg-brand-500 px-3.5 text-[12px] font-semibold text-white shadow-md transition hover:bg-brand-600">
                <flux:icon.adjustments-horizontal variant="micro" class="size-3.5" />
                Options
            </a>
        {{-- Quote-only / unpriced: no quick-add — route to the product page --}}
        @elseif ($isQuoteOnly)
            <a href="{{ route('product.show', $product) }}" wire:navigate aria-label="Request a quote"
               class="absolute right-2.5 bottom-2.5 z-10 inline-flex h-9 items-center gap-1.5 rounded-full bg-brand-500 px-3.5 text-[12px] font-semibold text-white shadow-md transition hover:bg-brand-600">
                <flux:icon.document-text variant="micro" class="size-3.5" />
                Quote
            </a>
        @else
        {{-- Add to cart stepper — single expanding pill, no element swapping --}}
        <div wire:key="cart-{{ $product->slug }}"
             x-data="{
                 qty: 0, expanded: false, _timer: null,
                 init() {
                     this.qty = Number(this.$el.dataset.qty);
                     this.$watch('qty', v => { if (v > 0) this.expand(); });
                 },
                 expand() {
                     this.expanded = true;
                     clearTimeout(this._timer);
                     this._timer = setTimeout(() => this.expanded = false, 3000);
                 },
                 addOne() {
                     if (!this.expanded && this.qty > 0) { this.expand(); return; }
                     this.qty++;
                     this.$wire.addToCart('{{ $product->slug }}');
                     this.expand();
                 },
                 removeOne() {
                     this.qty > 1 ? this.qty-- : this.qty = 0;
                     this.$wire.decrementCart('{{ $product->slug }}');
                     if (this.qty > 0) this.expand();
                 }
             }"
             data-qty="{{ $cartQty }}"
             @cart-qty-changed.window="if ($event.detail.slug === '{{ $product->slug }}') qty = $event.detail.qty"
             class="absolute right-2.5 bottom-2.5 z-10">

            {{-- Badge visible when collapsed + in cart --}}
            <span x-show="qty > 0 && !expanded" x-cloak x-text="qty"
                  class="absolute -top-1 -right-1 z-10 flex size-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-brand-500 shadow-sm">
            </span>

            {{-- Single pill — right edge is fixed, expands leftward --}}
            <div class="relative h-9 overflow-hidden rounded-full bg-brand-500 text-white shadow-md transition-[width] duration-200"
                 :class="expanded ? 'w-[100px]' : 'w-9'">

                {{-- Left zone: decrement + count — hidden until pill expands --}}
                <div class="absolute inset-y-0 left-0 right-9 flex items-center transition-opacity duration-150"
                     :class="expanded ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                    <button type="button" aria-label="Remove one"
                            @click="removeOne()"
                            class="flex size-9 shrink-0 cursor-pointer items-center justify-center transition hover:bg-brand-600">
                        <flux:icon.trash-2 variant="micro" class="size-3.5" x-show="qty <= 1" />
                        <flux:icon.minus variant="micro" class="size-3.5" x-show="qty > 1" x-cloak />
                    </button>
                    <span x-text="qty" class="flex-1 text-center text-[13px] font-bold tabular-nums"></span>
                </div>

                {{-- Right zone: cart icon or plus — always pinned to right edge --}}
                <button type="button" aria-label="Add to cart"
                        @click="addOne()"
                        class="absolute right-0 flex size-9 cursor-pointer items-center justify-center rounded-full transition hover:bg-brand-600">
                    <flux:icon.shopping-cart variant="micro" class="size-3.5" x-show="!expanded" />
                    <flux:icon.plus variant="micro" class="size-3.5" x-show="expanded" x-cloak />
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- Info — purely informational, no buttons --}}
    <a href="{{ route('product.show', $product) }}" wire:navigate
        class="flex flex-1 flex-col px-3 py-3 sm:px-4 sm:py-3.5">
        @if ($brandName)
            <div class="text-[11px] font-bold tracking-[0.08em] text-brand-blue-600 uppercase">{{ $brandName }}</div>
        @endif
        <div class="mt-1 line-clamp-2 min-h-9.5 text-[13.5px] font-medium leading-snug text-ink">
            {{ $product->name }}
        </div>
        <div class="mt-0.5 text-[11px] text-ink-4 tabular-nums">{{ $product->sku }}</div>

        <div class="mt-3">
            @if ($compareLabel)
                <div class="text-[11.5px] text-ink-4 line-through">{!! $compareLabel !!}</div>
            @endif
            <div class="text-[15px] font-bold text-ink tabular-nums whitespace-nowrap">{!! $priceLabel !!}</div>
        </div>
    </a>

</article>
