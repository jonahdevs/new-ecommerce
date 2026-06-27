<?php

use App\Livewire\Concerns\InteractsWithStorefront;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Compare')] class extends Component
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

        $unit = $product->dimension_unit ?? 'cm';

        return implode(' × ', array_map(fn ($v) => rtrim(rtrim((string) $v, '0'), '.').' '.$unit, $parts));
    };

    $emptySlots = max(0, 4 - $this->products->count());
@endphp

<div class="page-fade">
    {{-- Breadcrumb --}}
    <div class="bg-surface-sunken">
        <div class="shell py-3">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Compare</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="shell pt-3 pb-20">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Side by side.</h1>
            <p class="mt-2 text-ink-3">
                Comparing <span class="font-medium text-ink">{{ $this->products->count() }}</span> of 4 max
            </p>
        </div>
        @if ($this->products->isNotEmpty())
            <flux:button variant="ghost" size="sm" wire:click="clear">Clear all</flux:button>
        @endif
    </div>

    @if ($this->products->isEmpty())
        <div class="mt-10 flex flex-col items-center justify-center px-6 py-16 text-center">
            <img src="{{ asset('images/empty-states/product-comparison.svg') }}" alt="Nothing to compare"
                class="mx-auto h-72 w-72" />
            <h2 class="mt-6 text-xl font-semibold sm:text-2xl">Nothing to compare yet.</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-ink-3">Add up to 4 products to compare specs side-by-side.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <flux:button variant="customer-primary" size="customer" :href="route('catalog')" wire:navigate>
                    <flux:icon.magnifying-glass variant="micro" class="size-3.5" />
                    Browse products
                </flux:button>
                <flux:button variant="customer-outline" size="customer" :href="route('home')" wire:navigate>Back to home</flux:button>
            </div>
        </div>
    @else
        {{-- Comparison table --}}
        <div class="scrollbar-thin mt-7 overflow-x-auto rounded-md border border-zinc-200 bg-white">
            <table class="w-full min-w-[600px] table-auto border-collapse text-left lg:min-w-0 lg:table-fixed">
                <thead>
                    <tr>
                        {{-- Sticky header corner --}}
                        <th class="sticky left-0 z-10 w-36 border-b border-zinc-200 bg-white px-4 py-4 text-center text-[11.5px] font-bold tracking-[0.08em] text-ink-2 uppercase lg:w-50">
                            Product
                        </th>

                        @foreach ($this->products as $product)
                            <th wire:key="head-{{ $product->slug }}"
                                class="border-b border-l border-zinc-200 bg-white p-4 align-top">
                                {{-- Product image --}}
                                <a href="{{ route('product.show', $product) }}" wire:navigate
                                    class="mx-auto block h-32 w-32 lg:h-44 lg:w-44">
                                    @if ($product->cover_url)
                                        <img src="{{ $product->cover_url }}"
                                            alt="{{ $product->name }}"
                                            class="size-full object-contain" loading="lazy" />
                                    @else
                                        <flux:icon.photo class="size-12 text-ink-4" />
                                    @endif
                                </a>

                                <a href="{{ route('product.show', $product) }}" wire:navigate
                                    class="mt-3 block text-center font-serif text-lg leading-snug text-ink hover:underline">
                                    {{ $product->name }}
                                </a>
                            </th>
                        @endforeach

                        @for ($i = 0; $i < $emptySlots; $i++)
                            <th class="hidden border-b border-l border-zinc-200 bg-white p-4 align-top lg:table-cell">
                                <a href="{{ route('catalog') }}" wire:navigate
                                    class="mx-auto flex h-44 w-44 flex-col items-center justify-center gap-2 rounded border-2 border-dashed border-zinc-300 text-ink-3 transition hover:border-ink-3 hover:text-ink">
                                    <flux:icon.plus variant="micro" class="size-5" />
                                    <span class="text-[12.5px]">Add product</span>
                                </a>
                            </th>
                        @endfor
                    </tr>
                </thead>

                <tbody class="[&_tr:last-child>td]:border-b-0">
                    @include('partials.storefront.compare-row', [
                        'label' => 'Brand',
                        'cells' => $this->products->map(fn ($p) => $p->brand?->name ?? '—'),
                        'emptyCount' => $emptySlots,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Price',
                        'cells' => $this->products->map(fn ($p) => ($price = $p->sale_price ?? $p->price) ? money($price) : 'Quote on request'),
                        'emptyCount' => $emptySlots,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'SKU',
                        'cells' => $this->products->map(fn ($p) => $p->sku ?? '—'),
                        'emptyCount' => $emptySlots,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Model',
                        'cells' => $this->products->map(fn ($p) => $p->model_number ?? '—'),
                        'emptyCount' => $emptySlots,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Category',
                        'cells' => $this->products->map(fn ($p) => $p->primaryCategory?->name ?? '—'),
                        'emptyCount' => $emptySlots,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Stock',
                        'cells' => $this->products->map(fn ($p) => $p->stock_quantity ? $p->stock_quantity.' units' : 'Made to order'),
                        'emptyCount' => $emptySlots,
                    ])

                    @include('partials.storefront.compare-row', [
                        'label' => 'Weight',
                        'cells' => $this->products->map(fn ($p) => $p->weight ? rtrim(rtrim((string) $p->weight, '0'), '.').' '.($p->weight_unit ?? 'kg') : '—'),
                        'emptyCount' => $emptySlots,
                    ])
                    @include('partials.storefront.compare-row', [
                        'label' => 'Dimensions (W × D × H)',
                        'cells' => $this->products->map(fn ($p) => $dimensions($p)),
                        'emptyCount' => $emptySlots,
                    ])

                    @if (count($this->specLabels) > 0)
                        @foreach ($this->specLabels as $label)
                            @include('partials.storefront.compare-row', [
                                'label' => $label,
                                'cells' => $this->products->map(fn ($p) => $specFor($p, $label)),
                                'emptyCount' => $emptySlots,
                            ])
                        @endforeach
                    @endif

                    {{-- Actions: add to cart, then remove --}}
                    <tr>
                        <td class="sticky left-0 z-10 w-36 border-b border-zinc-200 bg-white px-4 py-3 text-center align-top text-[13px] font-semibold text-ink-2 lg:w-50">
                            Buy Now
                        </td>
                        @foreach ($this->products as $product)
                            <td wire:key="cart-{{ $product->slug }}" class="border-b border-l border-zinc-200 px-4 py-3 text-center align-top">
                                <flux:button variant="primary" size="sm"
                                    wire:click="addToCart('{{ $product->slug }}')">
                                    Add to cart
                                </flux:button>
                            </td>
                        @endforeach
                        @for ($i = 0; $i < $emptySlots; $i++)
                            <td class="hidden border-b border-l border-zinc-200 px-4 py-3 lg:table-cell"></td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="sticky left-0 z-10 w-36 bg-white px-4 py-3 text-center align-top text-[13px] font-semibold text-ink-2 lg:w-50">
                            Remove
                        </td>
                        @foreach ($this->products as $product)
                            <td wire:key="remove-{{ $product->slug }}" class="border-l border-zinc-200 px-4 py-3 text-center align-top">
                                <flux:button variant="ghost" size="sm" icon="trash-2"
                                    wire:click="remove('{{ $product->slug }}')" aria-label="Remove from compare" />
                            </td>
                        @endforeach
                        @for ($i = 0; $i < $emptySlots; $i++)
                            <td class="hidden border-l border-zinc-200 px-4 py-3 lg:table-cell"></td>
                        @endfor
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    @include('partials.storefront.accessory-modal')
    </div>
</div>
