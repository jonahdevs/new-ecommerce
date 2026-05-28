<?php

use App\Livewire\Concerns\InteractsWithStorefront;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Compare — Sheffield')] class extends Component
{
    use InteractsWithStorefront;

    public function mount(): void
    {
        // Session-driven page: keep it out of search indexes.
        SEOMeta::setRobots('noindex,follow')
            ->setDescription('Compare commercial kitchen equipment side-by-side — specs, dimensions, lead times.');
    }

    public function remove(string $slug): void
    {
        StorefrontSession::removeFromCompare($slug);
        $this->dispatch('compare-updated');
        unset($this->products);
    }

    public function clear(): void
    {
        StorefrontSession::clearCompare();
        $this->dispatch('compare-updated');
        unset($this->products);
    }

    #[Computed]
    public function products(): Collection
    {
        return StorefrontSession::compareProducts();
    }

    /**
     * Union of attribute names across all compared products, in first-seen order.
     * Used to build the "Detailed specs" rows so columns line up even when a
     * product is missing one of the attributes.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function specLabels(): array
    {
        $labels = [];
        foreach ($this->products as $product) {
            foreach ($product->productAttributes as $pa) {
                $name = $pa->attribute?->name;
                if ($name && ! in_array($name, $labels, true)) {
                    $labels[] = $name;
                }
            }
        }

        return $labels;
    }
}; ?>

@php
    $kes = fn ($cents) => $cents ? 'KES&nbsp;'.number_format(intdiv($cents, 100), 0, '.', ',') : null;

    // Find a productAttribute by attribute name; returns a presentable string or '—'.
    $specFor = function ($product, string $label): string {
        foreach ($product->productAttributes as $pa) {
            if ($pa->attribute?->name === $label) {
                $values = $pa->values;
                if (is_array($values)) {
                    return implode(', ', $values);
                }

                return (string) ($values ?? '—');
            }
        }

        return '—';
    };

    $dimensions = function ($product): string {
        $parts = array_filter([$product->width, $product->depth ?? $product->length, $product->height]);
        if (count($parts) < 2) {
            return '—';
        }

        return implode(' × ', array_map(fn ($v) => rtrim(rtrim((string) $v, '0'), '.').' cm', $parts));
    };

    $emptySlots = max(0, 4 - $this->products->count());
@endphp

<div class="shell page-fade pt-4 pb-20">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Compare</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Side by side.</h1>
            <p class="mt-2 text-ink-3">
                Comparing <span class="font-medium text-ink">{{ $this->products->count() }}</span> of 4 max
            </p>
        </div>
        @if ($this->products->isNotEmpty())
            <flux:button variant="ghost" size="sm" wire:click="clear">Clear all</flux:button>
        @endif
    </div>

    @if ($this->products->isEmpty())
        {{-- Empty state --}}
        <div class="mt-10 rounded-md bg-surface-sunken p-14 text-center">
            <flux:icon.scale variant="outline" class="mx-auto size-10 text-ink-4" />
            <h2 class="mt-4 font-serif text-2xl font-normal">Nothing to compare yet.</h2>
            <p class="mt-2 text-ink-3">Add up to 4 products to compare specs side-by-side.</p>
            <flux:button variant="primary" :href="route('catalog')" wire:navigate class="mt-5">
                Browse the catalog
            </flux:button>
        </div>
    @else
        {{-- Comparison table --}}
        <div class="mt-7 overflow-x-auto rounded-md border border-zinc-200 bg-white">
            <table class="w-full border-collapse text-left" style="min-width: {{ 200 + 220 * ($this->products->count() + min(1, $emptySlots)) }}px">
                <thead>
                    <tr>
                        {{-- Sticky header corner --}}
                        <th class="sticky left-0 z-10 w-50 border-b border-zinc-200 bg-surface-sunken px-4 py-4 text-[11.5px] font-bold tracking-[0.08em] text-ink-2 uppercase">
                            Product
                        </th>

                        @foreach ($this->products as $product)
                            @php
                                $price = $product->sale_price ?? $product->price;
                            @endphp
                            <th wire:key="head-{{ $product->slug }}"
                                class="relative min-w-55 border-b border-zinc-200 bg-white p-4 align-top">
                                <button type="button" wire:click="remove('{{ $product->slug }}')" aria-label="Remove from compare"
                                    class="absolute top-3 right-3 inline-flex size-7 cursor-pointer items-center justify-center rounded-full text-ink-3 transition hover:bg-surface-sunken hover:text-ink">
                                    <flux:icon.x-mark variant="micro" class="size-4" />
                                </button>

                                {{-- Product card --}}
                                <a href="{{ route('product.show', $product) }}" wire:navigate
                                    class="block aspect-square overflow-hidden rounded bg-surface-sunken p-3">
                                    @if ($product->cover_url)
                                        <img src="{{ $product->cover_url }}"
                                            alt="{{ $product->name }}"
                                            class="size-full object-contain" loading="lazy" />
                                    @endif
                                </a>

                                @if ($product->brand)
                                    <div class="mt-3 text-[11.5px] font-semibold tracking-[0.06em] text-brand-blue-500 uppercase">
                                        {{ $product->brand->name }}
                                    </div>
                                @endif
                                <a href="{{ route('product.show', $product) }}" wire:navigate
                                    class="mt-1 block font-serif text-lg leading-snug text-ink hover:underline">
                                    {{ $product->name }}
                                </a>
                                <div class="mt-2 font-serif text-xl tabular-nums">
                                    {!! $kes($price) ?? '<span class="text-ink-3 text-sm">Quote on request</span>' !!}
                                </div>

                                <flux:button variant="primary" size="sm" class="mt-3! w-full!"
                                    wire:click="addToCart('{{ $product->slug }}')">
                                    Add to cart
                                </flux:button>
                            </th>
                        @endforeach

                        @if ($emptySlots > 0)
                            <th class="min-w-55 border-b border-zinc-200 bg-white p-4 align-top">
                                <a href="{{ route('catalog') }}" wire:navigate
                                    class="flex aspect-square flex-col items-center justify-center gap-2 rounded border-2 border-dashed border-zinc-300 text-ink-3 transition hover:border-ink-3 hover:text-ink">
                                    <flux:icon.plus variant="micro" class="size-5" />
                                    <span class="text-[12.5px]">Add product</span>
                                </a>
                            </th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    {{-- Section: Key facts --}}
                    @include('partials.storefront.compare-section', [
                        'title' => 'Key facts',
                        'cols' => $this->products->count() + min(1, $emptySlots) + 1,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'SKU',
                        'cells' => $this->products->map(fn ($p) => $p->sku ?? '—'),
                        'empty' => $emptySlots > 0,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Model',
                        'cells' => $this->products->map(fn ($p) => $p->model_number ?? '—'),
                        'empty' => $emptySlots > 0,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Category',
                        'cells' => $this->products->map(fn ($p) => $p->primaryCategory?->name ?? '—'),
                        'empty' => $emptySlots > 0,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Stock',
                        'cells' => $this->products->map(fn ($p) => $p->stock_quantity ? $p->stock_quantity.' units' : 'Made to order'),
                        'empty' => $emptySlots > 0,
                    ])

                    {{-- Section: Dimensions & weight --}}
                    @include('partials.storefront.compare-section', [
                        'title' => 'Dimensions & weight',
                        'cols' => $this->products->count() + min(1, $emptySlots) + 1,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Weight',
                        'cells' => $this->products->map(fn ($p) => $p->weight ? rtrim(rtrim((string) $p->weight, '0'), '.').' kg' : '—'),
                        'empty' => $emptySlots > 0,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Dimensions (W × D × H)',
                        'cells' => $this->products->map(fn ($p) => $dimensions($p)),
                        'empty' => $emptySlots > 0,
                    ])

                    {{-- Section: Detailed specs --}}
                    @if (count($this->specLabels) > 0)
                        @include('partials.storefront.compare-section', [
                            'title' => 'Detailed specs',
                            'cols' => $this->products->count() + min(1, $emptySlots) + 1,
                        ])
                        @foreach ($this->specLabels as $label)
                            @include('partials.storefront.compare-row', [
                                'label' => $label,
                                'cells' => $this->products->map(fn ($p) => $specFor($p, $label)),
                                'empty' => $emptySlots > 0,
                            ])
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</div>
