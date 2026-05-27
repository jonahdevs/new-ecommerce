@props(['product'])

@php
    $coverImage = $product->images->first();
    $brandName = $product->brand?->name;
    $price = $product->sale_price ?? $product->price;
    $compareAt = $product->sale_price ? $product->price : null;
    // Prices stored as integer cents; convert to KES whole units for display
    $priceLabel = $price ? 'KES&nbsp;' . number_format(intdiv($price, 100), 0, '.', ',') : 'Request quote';
    $compareLabel = $compareAt ? 'KES&nbsp;' . number_format(intdiv($compareAt, 100), 0, '.', ',') : null;
    $inStock = $product->stock_status === \App\Enums\StockStatus::IN_STOCK;
    $isWished = \App\Support\StorefrontSession::isWishlisted($product->slug);
@endphp

<article class="group relative flex flex-col overflow-hidden rounded border border-zinc-200 bg-white transition hover:border-zinc-400 hover:shadow-sm">
    <div class="relative h-48 bg-surface-sunken p-3">
        @if ($product->stock_quantity && $product->stock_quantity < 5 && $inStock)
            <span class="absolute top-2.5 left-2.5 z-10 inline-flex h-5 items-center rounded bg-brand-500 px-2 text-[10.5px] font-bold tracking-wider text-white uppercase">
                Low stock
            </span>
        @endif

        <button type="button" wire:click.stop="toggleWishlist('{{ $product->slug }}')" aria-label="{{ $isWished ? 'Remove from wishlist' : 'Save to wishlist' }}"
            class="absolute top-2.5 right-2.5 z-10 inline-flex size-8 items-center justify-center rounded-full border border-zinc-200 bg-white/95 text-ink shadow-sm transition hover:bg-white {{ $isWished ? '!bg-brand-500 !text-white !border-brand-500' : 'opacity-0 group-hover:opacity-100' }}">
            <flux:icon.heart variant="micro" class="size-4" />
        </button>

        @if ($coverImage)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($coverImage->path) }}"
                alt="{{ $coverImage->alt ?? $product->name }}"
                loading="lazy"
                class="block size-full object-contain" />
        @else
            <div class="flex size-full items-center justify-center text-ink-4">
                <flux:icon.photo class="size-12" />
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col border-t border-zinc-200 px-4 py-3.5">
        @if ($brandName)
            <div class="mb-1 text-[11.5px] font-bold tracking-[0.08em] text-brand-blue-600 uppercase">{{ $brandName }}</div>
        @endif
        <div class="line-clamp-2 min-h-[38px] text-[14px] leading-snug font-medium text-ink">{{ $product->name }}</div>
        <div class="mt-1 text-[11.5px] text-ink-4 tabular-nums">{{ $product->sku }}</div>

        <div class="flex-1"></div>

        <div class="mt-3.5 flex items-end justify-between gap-2">
            <div>
                @if ($compareLabel)
                    <div class="text-[12px] text-ink-4 line-through">{!! $compareLabel !!}</div>
                @endif
                <div class="text-base font-bold text-ink tabular-nums whitespace-nowrap">{!! $priceLabel !!}</div>
                <div class="mt-0.5 text-[11.5px] {{ $inStock ? 'text-emerald-700' : 'text-ink-3' }}">
                    {{ $inStock ? '● In stock' : 'Made to order' }}
                </div>
            </div>
            <flux:button variant="primary" size="sm" icon="shopping-cart" aria-label="Add to cart" class="!size-9 !p-0"
                wire:click="addToCart('{{ $product->slug }}')" />
        </div>
    </div>
</article>
