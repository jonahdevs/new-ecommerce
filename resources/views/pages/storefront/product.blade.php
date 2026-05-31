<?php

use App\Enums\ReviewStatus;
use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\Product;
use Flux\Flux;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::storefront')] class extends Component
{
    use InteractsWithStorefront;

    public Product $product;

    public int $qty = 1;

    public bool $installation = false;

    public bool $extWarranty = false;

    public string $activeTab = 'specs';

    public int $galleryIdx = 0;

    public int $reviewRating = 5;

    public string $reviewTitle = '';

    public string $reviewBody = '';

    public function mount(Product $product): void
    {
        $this->product = $product->load([
            'brand',
            'primaryCategory',
            'images',
            'productAttributes' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order'),
            'productAttributes.attribute',
            'downloadableFiles',
            'accessories.brand',
            'accessories.images' => fn ($q) => $q->where('is_cover', true)->limit(1),
        ]);

        $this->applySeo();
    }

    /**
     * Build the product page's SEO tags — title, description, OG image and a
     * schema.org Product JSON-LD block (price, availability, brand, image).
     */
    private function applySeo(): void
    {
        $product = $this->product;
        $brand = $product->brand?->name;

        $title = $product->meta_title
            ?: trim(($brand ? $brand.' ' : '').$product->name).' — Sheffield';

        $description = $product->meta_description
            ?: ($product->short_description ?: Str::limit(strip_tags((string) $product->description), 160))
            ?: 'Authorised distributor for '.$product->name.' across East Africa. Install, service and spares from Sheffield.';

        $imageUrl = $product->cover_url;

        // ── Meta + OG + Twitter ─────────────────────────────────────────
        SEOMeta::setTitle($title)->setDescription($description);
        OpenGraph::setTitle($title)
            ->setDescription($description)
            ->setType('product');
        TwitterCard::setTitle($title)->setDescription($description);

        if ($imageUrl) {
            OpenGraph::addImage(url($imageUrl));
            TwitterCard::setImage(url($imageUrl));
        }

        if ($product->canonical_url) {
            SEOMeta::setCanonical($product->canonical_url);
        }

        // ── JSON-LD Product schema ──────────────────────────────────────
        $price = $product->sale_price ?? $product->price;
        if ($price !== null) {
            $price = app(\App\Support\TaxCalculator::class)->displayPriceCents($product, (int) $price);
        }
        $availability = $product->stock_status === StockStatus::IN_STOCK
            ? 'https://schema.org/InStock'
            : 'https://schema.org/MadeToOrder';

        JsonLdMulti::setType('Product')
            ->setTitle($product->name)
            ->setDescription($description);

        if ($imageUrl) {
            JsonLdMulti::addImage(url($imageUrl));
        }
        if ($product->sku) {
            JsonLdMulti::addValue('sku', $product->sku);
        }
        if ($brand) {
            JsonLdMulti::addValue('brand', ['@type' => 'Brand', 'name' => $brand]);
        }
        if ($price) {
            JsonLdMulti::addValue('offers', [
                '@type' => 'Offer',
                'price' => number_format($price / 100, 2, '.', ''),
                'priceCurrency' => 'KES',
                'availability' => $availability,
                'url' => url()->current(),
                'seller' => ['@type' => 'Organization', 'name' => 'Sheffield'],
            ]);
        }
    }

    public function rendering($view): void
    {
        // Mirror for layouts that read $title (and keeps the SEO bridge in head.blade.php in sync).
        $view->title($this->product->meta_title ?: $this->product->name.' — Sheffield');
    }

    public function incQty(): void
    {
        $this->qty = min(99, $this->qty + 1);
    }

    public function decQty(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function addThisToCart(): void
    {
        $this->addToCart($this->product->slug, $this->qty);
    }

    #[Computed]
    public function related(): Collection
    {
        $categoryId = $this->product->primary_category_id;

        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->where('visibility', 'visible')
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where('id', '!=', $this->product->id)
            ->when($categoryId, fn ($q) => $q->where('primary_category_id', $categoryId))
            ->inRandomOrder()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function approvedReviews(): Collection
    {
        return $this->product->approvedReviews()->get();
    }

    #[Computed]
    public function averageRating(): float
    {
        return round((float) $this->approvedReviews->avg('rating'), 1);
    }

    public function submitReview(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->validate([
            'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviewTitle' => ['nullable', 'string', 'max:120'],
            'reviewBody' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->product->reviews()->create([
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'rating' => $this->reviewRating,
            'title' => $this->reviewTitle ?: null,
            'body' => $this->reviewBody,
            'status' => ReviewStatus::PENDING,
        ]);

        $this->reset(['reviewTitle', 'reviewBody']);
        $this->reviewRating = 5;

        Flux::toast(heading: 'Thank you!', text: 'Your review has been submitted for moderation.', variant: 'success');
    }
}; ?>

@php
    $kes = fn ($cents) => $cents ? 'KES&nbsp;'.number_format(intdiv($cents, 100), 0, '.', ',') : null;

    $price = $product->sale_price ?? $product->price;
    $compareAt = $product->sale_price ? $product->price : null;
    // Headline display prices honour the store's tax display setting; $price stays
    // the stored (charged) amount that feeds the add-to-cart total below.
    $tax = app(\App\Support\TaxCalculator::class);
    $displayPrice = $price !== null ? $tax->displayPriceCents($product, (int) $price) : null;
    $displayCompareAt = $compareAt !== null ? $tax->displayPriceCents($product, (int) $compareAt) : null;
    $isOnSale = $compareAt !== null;
    $inStock = $product->stock_status === \App\Enums\StockStatus::IN_STOCK;
    $stockQty = $product->stock_quantity;

    $isWished = StorefrontSession::isWishlisted($product->slug);
    $isCompared = StorefrontSession::isCompared($product->slug);

    $gallery = $product->images->take(6); // cap thumbnails

    // Add-on prices: 6% install, 4% extended warranty — same heuristic as the design.
    $installPriceCents = $price ? (int) round($price * 0.06) : 0;
    $warrantyPriceCents = $price ? (int) round($price * 0.04) : 0;
    $unitPriceCents = (int) ($price ?? 0)
        + ($installation ? $installPriceCents : 0)
        + ($extWarranty ? $warrantyPriceCents : 0);

    $dimensionStr = collect([
        $product->width ? rtrim(rtrim((string) $product->width, '0'), '.') : null,
        $product->length ? rtrim(rtrim((string) $product->length, '0'), '.') : null,
        $product->height ? rtrim(rtrim((string) $product->height, '0'), '.') : null,
    ])->filter()->implode(' × ');
    $dimensionStr = $dimensionStr !== '' ? $dimensionStr.' '.($product->dimension_unit ?? 'cm') : null;
@endphp

<div class="shell page-fade pt-4 pb-20">
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('catalog')" wire:navigate>Catalog</flux:breadcrumbs.item>
        @if ($product->primaryCategory)
            <flux:breadcrumbs.item :href="route('category.show', $product->primaryCategory)" wire:navigate>
                {{ $product->primaryCategory->name }}
            </flux:breadcrumbs.item>
        @endif
        <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Main: gallery + info panel --}}
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1.05fr_1fr] lg:gap-14">
        {{-- Gallery --}}
        <div>
            <div class="relative aspect-square overflow-hidden rounded-md border border-zinc-200 bg-white p-10">
                @if ($isOnSale)
                    <span class="absolute top-5 left-5 text-[11px] font-bold tracking-[0.08em] text-brand-500 uppercase">
                        ● Sale
                    </span>
                @endif

                <div class="absolute top-5 right-5 flex gap-1.5">
                    <flux:tooltip :content="$isWished ? 'Remove from wishlist' : 'Save to wishlist'">
                        <button type="button" wire:click="toggleWishlist('{{ $product->slug }}')"
                            aria-label="{{ $isWished ? 'Remove from wishlist' : 'Save to wishlist' }}"
                            @class([
                                'inline-flex size-9 cursor-pointer items-center justify-center rounded-full border bg-white text-ink transition',
                                'bg-brand-500! border-brand-500! text-white!' => $isWished,
                                'border-zinc-200 hover:bg-surface-sunken' => ! $isWished,
                            ])>
                            <flux:icon.heart variant="micro" class="size-4" />
                        </button>
                    </flux:tooltip>
                    <flux:tooltip :content="$isCompared ? 'Remove from compare' : 'Add to compare'">
                        <button type="button" wire:click="toggleCompare('{{ $product->slug }}')"
                            aria-label="{{ $isCompared ? 'Remove from compare' : 'Add to compare'}}"
                            @class([
                                'inline-flex size-9 cursor-pointer items-center justify-center rounded-full border bg-white text-ink transition',
                                'bg-ink! border-ink! text-white!' => $isCompared,
                                'border-zinc-200 hover:bg-surface-sunken' => ! $isCompared,
                            ])>
                            <flux:icon.scale variant="micro" class="size-4" />
                        </button>
                    </flux:tooltip>
                </div>

                @php $shown = $gallery->values()->get($galleryIdx); @endphp
                @if ($shown)
                    <img src="{{ $shown->url }}"
                        alt="{{ $shown->alt ?? $product->name }}"
                        class="size-full object-contain" />
                @else
                    <div class="grid size-full place-items-center text-ink-4">
                        <flux:icon.photo variant="outline" class="size-12" />
                    </div>
                @endif

                @if ($gallery->count() > 1)
                    <div class="absolute right-5 bottom-5 left-5 flex justify-between text-[12px] text-ink-3">
                        <span class="tabular-nums">{{ $galleryIdx + 1 }} / {{ $gallery->count() }}</span>
                    </div>
                @endif
            </div>

            @if ($gallery->count() > 1)
                <div class="mt-3 grid gap-2.5" style="grid-template-columns: repeat({{ $gallery->count() }}, 1fr)">
                    @foreach ($gallery as $i => $img)
                        <button type="button" wire:click="$set('galleryIdx', {{ $i }})"
                            @class([
                                'aspect-square cursor-pointer overflow-hidden rounded border bg-white p-2 transition',
                                'border-brand-500 ring-1 ring-brand-500' => $i === $galleryIdx,
                                'border-zinc-200 hover:border-zinc-400' => $i !== $galleryIdx,
                            ])>
                            <img src="{{ $img->url }}" alt=""
                                class="size-full object-contain" loading="lazy" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info panel --}}
        <div>
            @if ($product->brand)
                <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-blue-500 uppercase">
                    {{ $product->brand->name }}
                </div>
            @endif
            <h1 class="mt-2 font-serif text-3xl leading-tight font-normal lg:text-4xl">{{ $product->name }}</h1>
            @if ($product->short_description)
                <p class="mt-3 text-[15px] leading-relaxed text-ink-2">{{ $product->short_description }}</p>
            @endif

            <div class="mt-5 flex items-center gap-4 text-[13px] text-ink-3">
                @if ($product->sku)
                    <span>SKU: <span class="text-ink-2 tabular-nums">{{ $product->sku }}</span></span>
                @endif
                @if ($product->model_number)
                    <span>·</span>
                    <span>Model: <span class="text-ink-2">{{ $product->model_number }}</span></span>
                @endif
            </div>

            {{-- Price block --}}
            <div class="mt-6 border-y border-zinc-200 py-5">
                <div class="flex flex-wrap items-baseline gap-3.5">
                    @if ($displayCompareAt)
                        <span class="text-lg text-ink-4 line-through tabular-nums whitespace-nowrap">{!! $kes($displayCompareAt) !!}</span>
                    @endif
                    <span class="font-serif text-4xl tabular-nums">
                        {!! $kes($displayPrice) ?? '<span class="text-ink-3 text-base">Quote on request</span>' !!}
                    </span>
                    @if ($displayPrice && $tax->priceDisplaySuffix())
                        <span class="text-[12.5px] text-ink-3">{{ $tax->priceDisplaySuffix() }}</span>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3 text-[13px] text-ink-2">
                    <span class="inline-flex items-center gap-1.5">
                        <span @class([
                            'size-2 rounded-full',
                            'bg-emerald-600' => $inStock && $stockQty,
                            'bg-amber-500' => ! $inStock || ! $stockQty,
                        ])></span>
                        {{ $inStock && $stockQty ? $stockQty.' in stock — Nairobi warehouse' : 'Made to order' }}
                    </span>
                </div>
            </div>

            {{-- Add-ons --}}
            @if ($price)
                <div class="mt-6">
                    <div class="mb-2.5 text-[11.5px] font-bold tracking-[0.08em] text-ink-3 uppercase">
                        Bundle add-ons
                    </div>

                    <label @class([
                        'flex cursor-pointer items-start gap-3.5 rounded border p-3.5 transition mb-2',
                        'border-ink-2 bg-surface-sunken' => $installation,
                        'border-zinc-200 hover:border-zinc-400' => ! $installation,
                    ])>
                        <input type="checkbox" wire:model.live="installation" class="mt-1 accent-brand-500" />
                        <div class="flex-1">
                            <div class="text-[14px] font-medium">Professional installation & commissioning</div>
                            <div class="mt-0.5 text-[12.5px] text-ink-3">Factory-trained engineer, on-site, parts & connections included.</div>
                        </div>
                        <div class="font-semibold tabular-nums whitespace-nowrap">+ {!! $kes($installPriceCents) !!}</div>
                    </label>

                    <label @class([
                        'flex cursor-pointer items-start gap-3.5 rounded border p-3.5 transition',
                        'border-ink-2 bg-surface-sunken' => $extWarranty,
                        'border-zinc-200 hover:border-zinc-400' => ! $extWarranty,
                    ])>
                        <input type="checkbox" wire:model.live="extWarranty" class="mt-1 accent-brand-500" />
                        <div class="flex-1">
                            <div class="text-[14px] font-medium">Extended warranty +24 months</div>
                            <div class="mt-0.5 text-[12.5px] text-ink-3">Adds 2 years to factory cover. Annual service visits included.</div>
                        </div>
                        <div class="font-semibold tabular-nums whitespace-nowrap">+ {!! $kes($warrantyPriceCents) !!}</div>
                    </label>
                </div>
            @endif

            {{-- Qty + CTAs --}}
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <div class="inline-flex h-12 items-stretch overflow-hidden rounded border border-zinc-200">
                    <button type="button" wire:click="decQty" aria-label="Decrease quantity"
                        class="grid w-11 cursor-pointer place-items-center text-ink-2 transition hover:bg-surface-sunken">
                        <flux:icon.minus variant="micro" class="size-4" />
                    </button>
                    <div class="grid w-12 place-items-center border-x border-zinc-200 text-[14px] font-semibold tabular-nums">
                        {{ $qty }}
                    </div>
                    <button type="button" wire:click="incQty" aria-label="Increase quantity"
                        class="grid w-11 cursor-pointer place-items-center text-ink-2 transition hover:bg-surface-sunken">
                        <flux:icon.plus variant="micro" class="size-4" />
                    </button>
                </div>

                <flux:button variant="primary" wire:click="addThisToCart" class="h-12! flex-1! px-6!"
                    icon="shopping-cart">
                    Add to cart · {!! $kes($unitPriceCents * $qty) ?? 'Request quote' !!}
                </flux:button>

                <flux:button class="h-12! px-5!">Request quote</flux:button>
            </div>

            {{-- Trust grid --}}
            <div class="mt-7 grid grid-cols-2 gap-3 border-t border-zinc-200 pt-5 text-[12.5px]">
                @foreach ([
                    ['truck', 'Regional delivery', 'KE · UG · TZ · RW'],
                    ['wrench-screwdriver', 'Install & commission', 'Factory-trained'],
                    ['shield-check', 'Spares in stock', '98% next-day'],
                    ['check-badge', 'Trade-tested', 'Net 30 available'],
                ] as [$icon, $title, $sub])
                    <div class="flex items-start gap-2.5">
                        <flux:icon :name="$icon" variant="outline" class="size-4 shrink-0 text-brand-500" />
                        <div>
                            <div class="font-semibold text-ink">{{ $title }}</div>
                            <div class="text-ink-3">{{ $sub }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mt-20">
        <div class="flex border-b border-zinc-200">
            @foreach ([
                'specs' => 'Specifications',
                'overview' => 'Overview',
                'install' => 'Installation & service',
                'documents' => 'Documents',
                'reviews' => 'Reviews',
            ] as $id => $label)
                <button type="button" wire:click="$set('activeTab', '{{ $id }}')"
                    @class([
                        '-mb-px cursor-pointer border-b-2 px-5 py-3.5 text-[14px] transition',
                        'border-brand-500 font-semibold text-ink' => $activeTab === $id,
                        'border-transparent text-ink-3 hover:text-ink' => $activeTab !== $id,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="pt-8">
            {{-- Specs --}}
            @if ($activeTab === 'specs')
                <div class="grid max-w-5xl grid-cols-1 gap-x-14 md:grid-cols-2">
                    @php
                        $rows = collect();
                        if ($product->sku) {
                            $rows->push(['SKU', $product->sku]);
                        }
                        if ($product->model_number) {
                            $rows->push(['Model number', $product->model_number]);
                        }
                        if ($product->brand) {
                            $rows->push(['Brand', $product->brand->name]);
                        }
                        if ($product->weight) {
                            $rows->push(['Weight', rtrim(rtrim((string) $product->weight, '0'), '.').' '.($product->weight_unit ?? 'kg')]);
                        }
                        if ($dimensionStr) {
                            $rows->push(['Dimensions (W × L × H)', $dimensionStr]);
                        }
                        foreach ($product->productAttributes as $pa) {
                            $values = is_array($pa->values) ? implode(', ', $pa->values) : (string) ($pa->values ?? '');
                            if ($pa->attribute && $values !== '') {
                                $rows->push([$pa->attribute->name, $values]);
                            }
                        }
                    @endphp
                    @foreach ($rows as [$label, $value])
                        <div class="grid grid-cols-[1fr_1.3fr] gap-4 border-b border-zinc-200 py-3.5 text-[14px]">
                            <span class="text-ink-3">{{ $label }}</span>
                            <span class="text-ink">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Overview --}}
            @if ($activeTab === 'overview')
                <div class="grid max-w-5xl grid-cols-1 gap-12 md:grid-cols-2">
                    <div>
                        <h3 class="font-serif text-2xl">About this product</h3>
                        <div class="mt-4 text-[14.5px] leading-relaxed text-ink-2">
                            @if ($product->description)
                                {!! nl2br(e($product->description)) !!}
                            @else
                                <p>Sheffield supplies this unit with full factory commissioning, on-site training for kitchen staff, and access to our regional service network. Parts inventory is maintained in Nairobi, with most components available next-day across East Africa.</p>
                            @endif
                        </div>
                    </div>
                    @if ($product->brand)
                        <div>
                            <h3 class="font-serif text-2xl">About {{ $product->brand->name }}</h3>
                            <p class="mt-4 text-[14.5px] leading-relaxed text-ink-2">
                                Authorised distributor for {{ $product->brand->name }} across East Africa. Full parts and service support from the Nairobi headquarters.
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Install --}}
            @if ($activeTab === 'install')
                <div class="max-w-5xl">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        @foreach ([
                            ['map-pin', '1. Site survey', 'Engineer visits to confirm utilities, ventilation and access. Free within 50 km of Nairobi.'],
                            ['truck', '2. Delivery & commissioning', 'White-glove unboxing, levelling and first-run calibration with kitchen staff present.'],
                            ['wrench-screwdriver', '3. Service & spares', 'Quarterly preventive visits available. 98% of spares stocked locally for next-day dispatch.'],
                        ] as [$icon, $title, $body])
                            <div class="rounded-md bg-surface-sunken p-6">
                                <flux:icon :name="$icon" variant="outline" class="size-5 text-brand-500" />
                                <div class="mt-3 font-serif text-lg">{{ $title }}</div>
                                <p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-2">{{ $body }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-7 flex flex-wrap items-center justify-between gap-4 rounded-md bg-ink p-6 text-[#f3eadd]">
                        <div>
                            <div class="font-serif text-xl text-[#f3eadd]">Need a service contract?</div>
                            <div class="mt-1 text-[13px] text-[#c9bea4]">From KES&nbsp;24,000/year for one unit. Annual preventive + 48-hr response.</div>
                        </div>
                        <flux:button variant="primary">Get a service quote</flux:button>
                    </div>
                </div>
            @endif

            {{-- Documents --}}
            @if ($activeTab === 'documents')
                <div class="flex max-w-2xl flex-col gap-2">
                    @forelse ($product->downloadableFiles as $file)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($file->file_path) }}" target="_blank"
                            class="grid grid-cols-[auto_1fr_auto_auto] items-center gap-3.5 rounded border border-zinc-200 bg-white px-5 py-4 transition hover:border-zinc-400">
                            <flux:icon.document variant="outline" class="size-5 text-brand-500" />
                            <div>
                                <div class="text-[14px] font-medium">{{ $file->name }}</div>
                                @if ($file->file_size)
                                    <div class="text-[12px] text-ink-3">{{ number_format($file->file_size / 1024, 0) }} KB</div>
                                @endif
                            </div>
                            <span class="text-[12px] text-ink-3 uppercase">{{ pathinfo($file->file_name, PATHINFO_EXTENSION) ?: 'PDF' }}</span>
                            <flux:icon.arrow-down-tray variant="micro" class="size-4 text-ink-2" />
                        </a>
                    @empty
                        <div class="rounded-md bg-surface-sunken p-10 text-center text-ink-3">
                            <flux:icon.document variant="outline" class="mx-auto size-7" />
                            <div class="mt-2 text-[14px]">No downloadable documents for this product yet.</div>
                            <div class="mt-1 text-[12.5px]">Request the spec sheet — we'll email it to you.</div>
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Reviews --}}
            @if ($activeTab === 'reviews')
                <div class="grid max-w-5xl grid-cols-1 gap-12 lg:grid-cols-[1.4fr_1fr]">

                    {{-- Existing reviews --}}
                    <div>
                        @if ($this->approvedReviews->isNotEmpty())
                            <div class="flex items-center gap-4 border-b border-zinc-200 pb-5">
                                <div class="font-serif text-5xl tabular-nums">{{ number_format($this->averageRating, 1) }}</div>
                                <div>
                                    <div class="flex gap-0.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <flux:icon.star :variant="$i <= round($this->averageRating) ? 'solid' : 'outline'"
                                                class="size-4 text-amber-500" />
                                        @endfor
                                    </div>
                                    <div class="mt-1 text-[13px] text-ink-3">
                                        Based on {{ $this->approvedReviews->count() }} review{{ $this->approvedReviews->count() === 1 ? '' : 's' }}
                                    </div>
                                </div>
                            </div>

                            <div class="divide-y divide-zinc-200">
                                @foreach ($this->approvedReviews as $review)
                                    <div class="py-5" wire:key="review-{{ $review->id }}">
                                        <div class="flex items-center gap-0.5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <flux:icon.star :variant="$i <= $review->rating ? 'solid' : 'outline'"
                                                    class="size-3.5 text-amber-500" />
                                            @endfor
                                        </div>
                                        @if ($review->title)
                                            <div class="mt-2 font-semibold text-ink">{{ $review->title }}</div>
                                        @endif
                                        <p class="mt-1.5 text-[14px] leading-relaxed text-ink-2">{{ $review->body }}</p>
                                        <div class="mt-2 text-[12.5px] text-ink-3">
                                            {{ $review->author_name }} · {{ $review->created_at->format('d M Y') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-md bg-surface-sunken p-10 text-center">
                                <flux:icon.star variant="outline" class="mx-auto size-7 text-ink-4" />
                                <div class="mt-3 font-serif text-xl">No reviews yet.</div>
                                <p class="mt-1.5 text-[13.5px] text-ink-3">Be the first to share your experience after you've installed and used the unit.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Write a review --}}
                    <div>
                        <div class="rounded-md bg-surface-sunken p-6">
                            <h3 class="font-serif text-xl">Write a review</h3>
                            @auth
                                <form wire:submit="submitReview" class="mt-4 space-y-4">
                                    <flux:field>
                                        <flux:label>Rating</flux:label>
                                        <flux:select wire:model="reviewRating">
                                            @foreach ([5 => 'Excellent', 4 => 'Good', 3 => 'Average', 2 => 'Poor', 1 => 'Terrible'] as $value => $label)
                                                <flux:select.option value="{{ $value }}">{{ $value }} — {{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                    <flux:input wire:model="reviewTitle" label="Title" placeholder="Sum it up (optional)" />
                                    <flux:textarea wire:model="reviewBody" label="Your review" rows="4"
                                        placeholder="How has this unit performed in your kitchen?" />
                                    <flux:button type="submit" variant="primary" class="w-full">Submit review</flux:button>
                                    <p class="text-[12px] text-ink-3">Reviews are published once approved by our team.</p>
                                </form>
                            @else
                                <p class="mt-2 text-[13.5px] leading-relaxed text-ink-3">
                                    Sign in to share your experience with this product.
                                </p>
                                <flux:button :href="route('login')" variant="primary" class="mt-4 w-full">Sign in to review</flux:button>
                            @endauth
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Related --}}
    @if ($this->related->isNotEmpty())
        <div class="mt-20">
            <div class="mb-4 flex items-baseline justify-between border-b border-zinc-200 pb-3">
                <h2 class="text-[22px] font-semibold tracking-tight">Related equipment</h2>
                @if ($product->primaryCategory)
                    <a href="{{ route('category.show', $product->primaryCategory) }}" wire:navigate
                        class="inline-flex items-center gap-1 text-[13px] text-zinc-600 hover:text-zinc-900">
                        More in {{ $product->primaryCategory->name }} <flux:icon.arrow-right variant="micro" class="size-3.5" />
                    </a>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
                @foreach ($this->related as $rel)
                    <x-storefront.product-card :product="$rel" wire:key="rel-{{ $rel->id }}" />
                @endforeach
            </div>
        </div>
    @endif
</div>
