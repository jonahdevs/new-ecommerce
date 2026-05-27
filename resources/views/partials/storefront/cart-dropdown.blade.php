@props(['cartCount' => 0])

@php
    $lines = \App\Support\StorefrontSession::cartLines();
    $subtotalCents = $lines->sum('line_total_cents');
    $kes = fn ($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');
@endphp

<flux:dropdown position="bottom" align="end" gap="10">
    <button type="button" aria-label="Cart"
        class="relative inline-flex size-10 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken hover:text-ink">
        <flux:icon.shopping-cart variant="micro" class="size-5" />
        @if ($cartCount > 0)
            <span class="absolute top-1 right-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-bold text-white tabular-nums">{{ $cartCount }}</span>
        @endif
    </button>

    <div popover="manual"
        class="w-[420px] overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg focus:outline-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3.5">
            <div>
                <div class="font-serif text-lg">Your cart</div>
                <div class="text-[12px] text-ink-3">{{ $lines->count() }} {{ \Illuminate\Support\Str::plural('item', $lines->count()) }}</div>
            </div>
            @if ($lines->isNotEmpty())
                <a href="{{ route('cart') }}" wire:navigate class="text-[12px] text-ink-3 underline-offset-2 hover:text-ink hover:underline">View cart →</a>
            @endif
        </div>

        @if ($lines->isEmpty())
            {{-- Empty state --}}
            <div class="px-5 py-7 text-center">
                <flux:icon.shopping-cart variant="outline" class="mx-auto size-7 text-ink-4" />
                <div class="mt-2 font-serif text-lg">Cart is empty</div>
                <p class="mt-1.5 text-[12.5px] text-ink-3">
                    Browse equipment or request a quote for tendered projects.
                </p>
                <flux:button variant="primary" size="sm" :href="route('catalog')" wire:navigate class="mt-3.5">
                    Shop the catalog
                </flux:button>
            </div>
        @else
            {{-- Items --}}
            <div class="max-h-80 overflow-y-auto">
                @foreach ($lines as $line)
                    @php
                        $product = $line['product'];
                        $cover = $product->images->first();
                    @endphp
                    <div wire:key="dd-{{ $line['slug'] }}"
                        class="grid grid-cols-[48px_1fr_auto] items-center gap-3 border-b border-zinc-200 px-5 py-3">
                        <div class="size-12 overflow-hidden rounded bg-surface-sunken p-1.5">
                            @if ($cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($cover->path) }}" alt="" class="size-full object-contain" loading="lazy" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('cart') }}" wire:navigate class="line-clamp-2 text-[13px] leading-snug font-medium">{{ $product->name }}</a>
                            <div class="mt-1 text-[11.5px] text-ink-3">Qty {{ $line['qty'] }} · {{ $product->sku }}</div>
                        </div>
                        <div class="text-right text-[13px] font-semibold tabular-nums whitespace-nowrap">{!! $kes($line['line_total_cents']) !!}</div>
                    </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="bg-surface-sunken px-5 py-3.5">
                <div class="flex items-center justify-between text-[13px] text-ink-2">
                    <span>Subtotal</span>
                    <span class="font-semibold tabular-nums whitespace-nowrap">{!! $kes($subtotalCents) !!}</span>
                </div>
                <div class="mt-1 text-[11.5px] text-ink-3">VAT &amp; delivery calculated at checkout</div>
                <div class="mt-3 flex gap-2">
                    <flux:button size="sm" :href="route('cart')" wire:navigate class="!flex-1">View cart</flux:button>
                    <flux:button variant="primary" size="sm" href="#" wire:navigate class="!flex-1">Checkout</flux:button>
                </div>
                <a href="#" class="mt-2 block text-center text-[12.5px] text-brand-blue-600 hover:underline">
                    Convert to a formal quote →
                </a>
            </div>
        @endif
    </div>
</flux:dropdown>
