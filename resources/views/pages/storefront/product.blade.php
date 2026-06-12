<?php

use App\Enums\ProductType;
use App\Enums\ReviewStatus;
use App\Enums\StockStatus;
use App\Livewire\Concerns\InteractsWithStorefront;
use App\Models\AttributeValue;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Showroom;
use App\Settings\QuotationSettings;
use App\Settings\ReviewSettings;
use App\Settings\ShippingSettings;
use Flux\Flux;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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

    public bool $showBundleModal = false;

    /** @var array<string, int> Selected quantities per grouped-child slug. */
    public array $groupedQty = [];

    /** @var array<string, string> Selected variation option per attribute slug (variable products). */
    public array $selectedOptions = [];

    public string $activeTab = 'overview';

    public int $galleryIdx = 0;

    public string $addonTab = 'accessories';

    /** @var list<int> Related-product IDs, picked once at mount so they stay stable across round-trips. */
    public array $relatedIds = [];

    /** @var list<int> Same-brand product IDs (excluding self), picked once at mount. */
    public array $brandProductIds = [];

    /** @var list<int> Co-viewed product IDs derived from the recently_viewed table. */
    public array $alsoViewedIds = [];

    /** @var list<int> The auth user's own recently viewed IDs (excluding this product). */
    public array $recentlyViewedIds = [];

    public function mount(Product $product): void
    {
        $this->product = $product->load([
            'brand',
            'primaryCategory',
            'images',
            'productAttributes' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order'),
            'productAttributes.attribute',
            'downloadableFiles',
            'accessories' => fn ($q) => $q->visibleInCatalog()->published()->with(['images' => fn ($q) => $q->where('is_cover', true)->limit(1)])->orderBy('sort_order'),
            'spareParts'  => fn ($q) => $q->visibleInCatalog()->published()->with(['images' => fn ($q) => $q->where('is_cover', true)->limit(1)])->orderBy('sort_order'),
        ]);

        if ($this->product->type === ProductType::BUNDLE) {
            $this->product->load([
                'bundleItems.product.brand',
                'bundleItems.product.images' => fn ($q) => $q->where('is_cover', true)->limit(1),
                'bundleItems.variant',
            ]);
        } elseif ($this->product->type === ProductType::GROUPED) {
            $this->product->load([
                'groupedItems.brand',
                'groupedItems.images' => fn ($q) => $q->where('is_cover', true)->limit(1),
            ]);
        } elseif ($this->product->type === ProductType::VARIABLE) {
            $this->product->load([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'variants.attributeValues.attribute',
            ]);
            $this->preselectDefaultVariant();
        }

        $this->relatedIds         = $this->pickRelatedIds();
        $this->brandProductIds    = $this->pickBrandProductIds();
        $this->alsoViewedIds      = $this->pickAlsoViewedIds();
        $this->recentlyViewedIds  = $this->pickRecentlyViewedIds();

        $this->applySeo();

        if (auth()->check()) {
            \App\Models\RecentlyViewed::upsert(
                [['user_id' => auth()->id(), 'product_id' => $product->id, 'viewed_at' => now()]],
                uniqueBy: ['user_id', 'product_id'],
                update: ['viewed_at'],
            );
        }
    }

    /**
     * Pick the related-product IDs once. Randomised here (not in the render-time
     * computed) so the selection doesn't reshuffle on every Livewire round-trip.
     *
     * @return list<int>
     */
    private function pickRelatedIds(): array
    {
        $categoryId = $this->product->primary_category_id;

        return Product::query()
            ->where('visibility', 'visible')
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where('id', '!=', $this->product->id)
            ->when($categoryId, fn ($q) => $q->where('primary_category_id', $categoryId))
            ->inRandomOrder()
            ->take(6)
            ->pluck('id')
            ->all();
    }

    private function pickBrandProductIds(): array
    {
        if (! $this->product->brand_id) {
            return [];
        }

        return Product::query()
            ->where('visibility', 'visible')
            ->where('stock_status', StockStatus::IN_STOCK)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where('id', '!=', $this->product->id)
            ->where('brand_id', $this->product->brand_id)
            ->inRandomOrder()
            ->take(6)
            ->pluck('id')
            ->all();
    }

    private function pickAlsoViewedIds(): array
    {
        return DB::table('recently_viewed as r1')
            ->join('recently_viewed as r2', function ($join) {
                $join->on('r1.user_id', '=', 'r2.user_id')
                    ->where('r2.product_id', '!=', $this->product->id);
            })
            ->where('r1.product_id', $this->product->id)
            ->groupBy('r2.product_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(6)
            ->pluck('r2.product_id')
            ->all();
    }

    private function pickRecentlyViewedIds(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return \App\Models\RecentlyViewed::where('user_id', auth()->id())
            ->where('product_id', '!=', $this->product->id)
            ->orderByDesc('viewed_at')
            ->limit(8)
            ->pluck('product_id')
            ->all();
    }

    /**
     * Pre-select the default variant (or the first in-stock one) so the page
     * opens with a concrete price/stock rather than an empty selection.
     */
    private function preselectDefaultVariant(): void
    {
        $default = $this->product->variants->firstWhere('id', $this->product->default_variant_id)
            ?? $this->product->variants->first(fn ($v) => $v->stock_status === StockStatus::IN_STOCK)
            ?? $this->product->variants->first();

        if ($default) {
            $this->selectedOptions = $default->attributeValues
                ->mapWithKeys(fn ($value) => [$value->attribute->slug => $value->slug])
                ->all();
        }
    }

    public function selectOption(string $attributeSlug, string $valueSlug): void
    {
        $this->selectedOptions[$attributeSlug] = $valueSlug;
        $this->resetErrorBag('variant');
    }

    /**
     * Whether an in-stock variant exists for this option value given the other
     * currently-selected options (so impossible combinations are disabled).
     */
    public function isOptionAvailable(string $attributeSlug, string $valueSlug): bool
    {
        $others = collect($this->selectedOptions)->filter(fn ($v, $k) => $k !== $attributeSlug && $v !== '');

        return $this->product->variants->contains(function (ProductVariant $variant) use ($attributeSlug, $valueSlug, $others) {
            $combo = $variant->attributeValues->mapWithKeys(fn ($value) => [$value->attribute->slug => $value->slug]);

            if (($combo[$attributeSlug] ?? null) !== $valueSlug) {
                return false;
            }

            foreach ($others as $slug => $value) {
                if (($combo[$slug] ?? null) !== $value) {
                    return false;
                }
            }

            return $variant->stock_status === StockStatus::IN_STOCK;
        });
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
            ?: trim(($brand ? $brand.' ' : '').$product->name).'';

        $description = $product->meta_description
            ?: ($product->short_description ?: Str::limit(strip_tags((string) $product->description), 160))
            ?: 'Authorised distributor for '.$product->name.' across East Africa. Install, service and spares from Sheffield.';

        $imageUrl = $product->cover_url;

        // ==================================================
        // META + OG + TWITTER
        // ==================================================
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

        // ==================================================
        // JSON-LD PRODUCT SCHEMA
        // ==================================================
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
                'priceCurrency' => app(\App\Settings\LocalizationSettings::class)->currency,
                'availability' => $availability,
                'url' => url()->current(),
                'seller' => ['@type' => 'Organization', 'name' => 'Sheffield'],
            ]);
        }

        $reviewCount = $product->reviews()->where('status', ReviewStatus::APPROVED)->count();
        if ($reviewCount > 0) {
            $avgRating = $product->reviews()->where('status', ReviewStatus::APPROVED)->avg('rating');
            JsonLdMulti::addValue('aggregateRating', [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $avgRating, 1),
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ]);
        }
    }

    public function rendering($view): void
    {
        // Mirror for layouts that read $title (and keeps the SEO bridge in head.blade.php in sync).
        $view->title($this->product->meta_title ?: $this->product->name.'');
    }

    public function incQty(): void
    {
        $this->qty = min(99, $this->qty + 1);
    }

    public function decQty(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function incGroupedQty(string $slug): void
    {
        $this->groupedQty[$slug] = min(99, ($this->groupedQty[$slug] ?? 0) + 1);
    }

    public function decGroupedQty(string $slug): void
    {
        $this->groupedQty[$slug] = max(0, ($this->groupedQty[$slug] ?? 0) - 1);
    }

    /**
     * Bundles and grouped products open a configuration modal first; everything
     * else goes straight into the cart.
     */
    public function addThisToCart(): void
    {
        if ($this->product->type === ProductType::VARIABLE) {
            $variant = $this->selectedVariant;

            if (! $variant) {
                $this->addError('variant', 'Please select '.$this->variationAttributes->pluck('name')->join(' and ').'.');

                return;
            }

            if ($variant->stock_status !== StockStatus::IN_STOCK) {
                $this->addError('variant', 'Sorry, that combination is out of stock.');

                return;
            }

            $this->addToCart($this->product->slug, $this->qty, $variant->id);

            return;
        }

        if (in_array($this->product->type, [ProductType::BUNDLE, ProductType::GROUPED], true)) {
            $this->showBundleModal = true;

            return;
        }

        $this->addToCart($this->product->slug, $this->qty);
    }

    /** Add the bundle to the cart as a single SKU. */
    public function addBundleToCart(): void
    {
        StorefrontSession::addToCart($this->product->slug, $this->qty);
        $this->showBundleModal = false;

        $this->dispatch('cart-updated');
        $this->dispatch('cart-qty-changed', slug: $this->product->slug, qty: StorefrontSession::cartQuantity($this->product->slug));
        Flux::toast(heading: 'Added to cart', text: 'Bundle has been added to your cart.', variant: 'success');
    }

    /** Add each chosen grouped-child product to the cart as its own line. */
    public function addGroupedToCart(): void
    {
        $children = $this->product->groupedItems->keyBy('slug');
        $added = 0;

        foreach ($this->groupedQty as $slug => $qty) {
            $qty = max(0, (int) $qty);

            if ($qty > 0 && $children->has($slug)) {
                StorefrontSession::addToCart($slug, $qty);
                $added += $qty;
            }
        }

        if ($added === 0) {
            $this->addError('groupedQty', 'Choose a quantity for at least one item.');

            return;
        }

        $this->groupedQty = [];
        $this->showBundleModal = false;

        $this->dispatch('cart-updated');
        Flux::toast(heading: 'Added to cart', text: $added.' '.\Illuminate\Support\Str::plural('item', $added).' added to your cart.', variant: 'success');
    }

    #[Computed]
    public function related(): Collection
    {
        if ($this->relatedIds === []) {
            return new Collection();
        }

        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('id', $this->relatedIds)
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $this->relatedIds, true))
            ->values();
    }

    #[Computed]
    public function brandProducts(): Collection
    {
        if ($this->brandProductIds === []) {
            return new Collection();
        }

        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('id', $this->brandProductIds)
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $this->brandProductIds, true))
            ->values();
    }

    #[Computed]
    public function alsoViewed(): Collection
    {
        if ($this->alsoViewedIds === []) {
            return new Collection();
        }

        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('id', $this->alsoViewedIds)
            ->where('visibility', 'visible')
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $this->alsoViewedIds, true))
            ->values();
    }

    #[Computed]
    public function recentlyViewedProducts(): Collection
    {
        if ($this->recentlyViewedIds === []) {
            return new Collection();
        }

        return Product::query()
            ->with(['brand', 'taxClass', 'images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->whereIn('id', $this->recentlyViewedIds)
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $this->recentlyViewedIds, true))
            ->values();
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

    #[Computed]
    public function reviewsEnabled(): bool
    {
        return app(ReviewSettings::class)->reviews_enabled;
    }

    #[Computed]
    public function quotesEnabled(): bool
    {
        return app(QuotationSettings::class)->quotes_enabled;
    }

    /** City of the head-office showroom, used as the real stock location. */
    #[Computed]
    public function stockLocation(): ?string
    {
        return Showroom::where('is_hq', true)->value('city')
            ?? Showroom::orderBy('sort_order')->value('city');
    }

    /** Active delivery zones, powering the real delivery coverage badge. */
    #[Computed]
    public function deliveryZones(): Collection
    {
        return DeliveryZone::query()->active()->orderBy('sort_order')->get();
    }

    /**
     * Bundle price: the parent's own price when set, otherwise the summed
     * required-component total (price_override ?? the component's own price).
     */
    #[Computed]
    public function bundlePriceCents(): ?int
    {
        if ($this->product->type !== ProductType::BUNDLE) {
            return null;
        }

        $parent = $this->product->sale_price ?? $this->product->price;
        if ($parent !== null) {
            return (int) $parent;
        }

        $sum = $this->product->bundleItems
            ->reject(fn ($item) => $item->is_optional)
            ->sum(fn ($item) => (int) ($item->price_override ?? $item->product?->sale_price ?? $item->product?->price ?? 0) * max(1, (int) $item->quantity));

        return $sum > 0 ? (int) $sum : null;
    }

    /**
     * Variation attributes with their selectable values (resolved to
     * AttributeValue models for labels and colour swatches).
     *
     * @return \Illuminate\Support\Collection<int, array{slug: string, name: string, values: \Illuminate\Support\Collection<int, AttributeValue>}>
     */
    #[Computed]
    public function variationAttributes(): \Illuminate\Support\Collection
    {
        if ($this->product->type !== ProductType::VARIABLE) {
            return collect();
        }

        return $this->product->productAttributes
            ->filter(fn ($pa) => $pa->is_variation_attribute && $pa->attribute)
            ->sortBy('sort_order')
            ->map(function ($pa) {
                $slugs = is_array($pa->values) ? $pa->values : [];

                return [
                    'slug' => $pa->attribute->slug,
                    'name' => $pa->attribute->name,
                    'values' => AttributeValue::where('attribute_id', $pa->attribute_id)
                        ->whereIn('slug', $slugs)
                        ->orderBy('sort_order')
                        ->get(),
                ];
            })
            ->values();
    }

    /** The variant matching the full current selection, if any. */
    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        if ($this->product->type !== ProductType::VARIABLE) {
            return null;
        }

        $selected = collect($this->selectedOptions)->filter(fn ($v) => $v !== '');

        if ($selected->count() < $this->variationAttributes->count()) {
            return null;
        }

        return $this->product->variants->first(function (ProductVariant $variant) use ($selected) {
            $combo = $variant->attributeValues->mapWithKeys(fn ($value) => [$value->attribute->slug => $value->slug]);

            foreach ($selected as $slug => $value) {
                if (($combo[$slug] ?? null) !== $value) {
                    return false;
                }
            }

            return true;
        });
    }

    public function setAddonTab(string $tab): void
    {
        $this->addonTab = $tab;
    }

}; ?>

@php
    // For variable products the headline figures track the chosen variant
    // (its compare_at_price holds the sale price, mirroring product.sale_price).
    $variant = $product->type === \App\Enums\ProductType::VARIABLE ? $this->selectedVariant : null;

    if ($variant) {
        $price = $variant->compare_at_price ?? $variant->price;
        $compareAt = $variant->compare_at_price ? $variant->price : null;
        $inStock = $variant->stock_status === \App\Enums\StockStatus::IN_STOCK;
        $stockQty = $variant->stock_quantity;
        $skuDisplay = $variant->sku;
    } else {
        $price = $product->sale_price ?? $product->price;
        $compareAt = $product->sale_price ? $product->price : null;
        $inStock = $product->stock_status === \App\Enums\StockStatus::IN_STOCK;
        $stockQty = $product->stock_quantity;
        $skuDisplay = $product->sku;
    }

    // Headline display prices honour the store's tax display setting; $price stays
    // the stored (charged) amount that feeds the add-to-cart total below.
    $tax = app(\App\Support\TaxCalculator::class);
    $displayPrice = $price !== null ? $tax->displayPriceCents($product, (int) $price) : null;
    $displayCompareAt = $compareAt !== null ? $tax->displayPriceCents($product, (int) $compareAt) : null;
    $isOnSale = $compareAt !== null;

    $isWished   = StorefrontSession::isWishlisted($product->slug);
    $isCompared = StorefrontSession::isCompared($product->slug);

    // Explicit stock states used for CTA and chips
    $isBackorder  = $variant
        ? $variant->stock_status === \App\Enums\StockStatus::BACKORDER
        : $product->stock_status === \App\Enums\StockStatus::BACKORDER;
    $isOutOfStock = ! $inStock && ! $isBackorder;

    $gallery = $product->images->take(6); // cap thumbnails

    // Grouped products have no parent price; surface the cheapest child as a "from" price.
    $groupedFromCents = null;
    if ($product->type === \App\Enums\ProductType::GROUPED && $displayPrice === null) {
        $groupedFromCents = $product->groupedItems
            ->map(fn ($child) => $child->sale_price ?? $child->price)
            ->filter()
            ->min();
    }

    $dimensionStr = collect([
        $product->width ? rtrim(rtrim((string) $product->width, '0'), '.') : null,
        $product->length ? rtrim(rtrim((string) $product->length, '0'), '.') : null,
        $product->height ? rtrim(rtrim((string) $product->height, '0'), '.') : null,
    ])->filter()->implode(' × ');
    $dimensionStr = $dimensionStr !== '' ? $dimensionStr.' '.($product->dimension_unit ?? 'cm') : null;
@endphp

<div class="shell page-fade pt-6 pb-24">
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

    {{-- Main: gallery + details + buy-box (3-column on lg+, stacked below) --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)_minmax(300px,340px)] lg:gap-10 xl:gap-12">
        {{-- Gallery --}}
        <div
            x-data="{
                lens: null,
                lbIdx: {{ $galleryIdx }},
                gallery: @js($gallery->values()->map(fn ($img) => ['url' => $img->url, 'alt' => $img->alt ?? $product->name, 'label' => $img->alt ?? ''])),
                prevLb() { this.lbIdx = (this.lbIdx - 1 + this.gallery.length) % this.gallery.length; },
                nextLb() { this.lbIdx = (this.lbIdx + 1) % this.gallery.length; },
            }"
        >
            {{-- Gallery: thumbnails scroll horizontally below the image on mobile,
                 and sit as a vertical strip on the left at md+. --}}
            <div class="flex flex-col-reverse gap-2.5 md:flex-row">

                {{-- Thumbnail strip --}}
                @if ($gallery->count() > 0)
                    <div class="flex gap-2 overflow-x-auto md:max-h-[520px] md:flex-col md:overflow-x-visible md:overflow-y-auto" style="scrollbar-width: none;">
                        @foreach ($gallery as $i => $img)
                            <button type="button"
                                wire:click="$set('galleryIdx', {{ $i }})"
                                x-on:click="lbIdx = {{ $i }}"
                                @class([
                                    'aspect-square size-16 shrink-0 cursor-pointer overflow-hidden rounded bg-white transition md:size-[72px]',
                                    'border-2 border-brand-500' => $i === $galleryIdx,
                                    'border border-zinc-200 hover:border-zinc-400' => $i !== $galleryIdx,
                                ])>
                                <img src="{{ $img->url }}" alt="{{ $img->alt ?? '' }}"
                                    class="size-full object-cover" loading="lazy" />
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Main image --}}
                <div
                    class="group relative min-w-0 flex-1 cursor-zoom-in overflow-hidden rounded-md border border-zinc-200 bg-white"
                    style="aspect-ratio: 1; max-height: 520px;"
                    @mousemove="const r = $el.getBoundingClientRect(); lens = { x: Math.max(0,Math.min(100,(($event.clientX-r.left)/r.width)*100)), y: Math.max(0,Math.min(100,(($event.clientY-r.top)/r.height)*100)) }"
                    @mouseleave="lens = null"
                    @click="$flux.modal('product-gallery').show()"
                >
                    @if ($isOnSale)
                        <span class="absolute top-4 left-4 z-10 text-[11px] font-bold tracking-[0.08em] text-brand-500 uppercase">● Sale</span>
                    @endif

                    <div class="absolute top-4 right-4 z-10 flex gap-1.5" @click.stop>
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
                            class="size-full object-cover transition-transform duration-75 will-change-transform"
                            :style="lens ? `transform:scale(2.3);transform-origin:${lens.x}% ${lens.y}%` : ''"
                            draggable="false" />
                    @else
                        <div class="grid size-full place-items-center text-ink-4">
                            <flux:icon.photo variant="outline" class="size-12" />
                        </div>
                    @endif

                    {{-- Hover hint --}}
                    @if ($gallery->isNotEmpty())
                        <div class="pointer-events-none absolute bottom-4 left-1/2 -translate-x-1/2 inline-flex items-center gap-1.5 rounded-full bg-[rgba(12,20,33,0.62)] px-3 py-1.5 text-[10.5px] tracking-[0.02em] text-white opacity-0 backdrop-blur-sm transition-opacity duration-150 group-hover:opacity-100">
                            <flux:icon.magnifying-glass variant="micro" class="size-3.5" />
                            Hover to zoom · click to expand
                        </div>
                    @endif

                    {{-- Counter --}}
                    @if ($gallery->count() > 1)
                        <div class="absolute right-3 bottom-3 font-mono text-[11px] text-ink-4 tabular-nums">
                            {{ $galleryIdx + 1 }} / {{ $gallery->count() }}
                        </div>
                    @endif
                </div>

            </div>{{-- end gallery flex row --}}

            {{-- Image modal (Flux) — opened via $flux.modal('product-gallery').show() on the main image --}}
            <flux:modal name="product-gallery" class="w-full max-w-xl">
                <div class="flex flex-col gap-4">
                    <flux:heading size="lg">Product Images</flux:heading>

                    {{-- Square image stage --}}
                    <div class="group relative mx-auto aspect-square w-full max-w-[min(100%,60vh)] overflow-hidden bg-white">
                        <template x-if="gallery.length > 1">
                            <button type="button" @click="prevLb()" aria-label="Previous image"
                                class="absolute top-1/2 left-3 z-10 inline-flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white text-ink-2 opacity-0 shadow-sm transition group-hover:opacity-100 hover:bg-surface-sunken">
                                <flux:icon.chevron-left variant="micro" class="size-4" />
                            </button>
                        </template>

                        <img :src="gallery[lbIdx]?.url" :alt="gallery[lbIdx]?.alt"
                            class="size-full select-none object-contain" draggable="false" />

                        <template x-if="gallery.length > 1">
                            <button type="button" @click="nextLb()" aria-label="Next image"
                                class="absolute top-1/2 right-3 z-10 inline-flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white text-ink-2 opacity-0 shadow-sm transition group-hover:opacity-100 hover:bg-surface-sunken">
                                <flux:icon.chevron-right variant="micro" class="size-4" />
                            </button>
                        </template>
                    </div>

                    {{-- Thumbnail strip --}}
                    <template x-if="gallery.length > 1">
                        <div class="flex flex-wrap justify-center gap-2.5">
                            <template x-for="(img, i) in gallery" :key="i">
                                <button type="button" @click="lbIdx = i"
                                    class="size-14 cursor-pointer overflow-hidden rounded border-2 bg-white transition"
                                    :class="i === lbIdx ? 'border-brand-500' : 'border-zinc-200 hover:border-zinc-400'">
                                    <img :src="img.url" :alt="img.alt" class="size-full object-cover" />
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </flux:modal>
        </div>

        {{-- Center column — product details --}}
        <div>
            @if ($product->brand)
                <div class="text-[11.5px] font-bold tracking-[0.12em] text-brand-blue-500 uppercase">
                    {{ $product->brand->name }}
                </div>
            @endif
            <h1 class="mt-2 font-serif text-3xl leading-tight font-normal lg:text-4xl">{{ $product->name }}</h1>

            {{-- Rating summary — scrolls to the Reviews tab --}}
            @if ($this->reviewsEnabled && $this->approvedReviews->isNotEmpty())
                <button type="button" wire:click="$set('activeTab', 'reviews')"
                    onclick="document.getElementById('product-tabs')?.scrollIntoView({ behavior: 'smooth' })"
                    class="mt-3 inline-flex cursor-pointer items-center gap-2 text-[13px] text-ink-3 transition hover:text-ink">
                    <span class="flex items-center gap-0.5">
                        @for ($s = 1; $s <= 5; $s++)
                            <flux:icon.star :variant="$s <= round($this->averageRating) ? 'solid' : 'outline'" class="size-4 text-amber-500" />
                        @endfor
                    </span>
                    <span class="font-semibold text-ink">{{ number_format($this->averageRating, 1) }}</span>
                    <span class="underline-offset-2 hover:underline">{{ $this->approvedReviews->count() }} {{ \Illuminate\Support\Str::plural('review', $this->approvedReviews->count()) }}</span>
                </button>
            @endif
            @if ($product->short_description)
                <div class="pdp-rich-text mt-3 text-[15px] leading-relaxed text-ink-2">{!! $product->short_description !!}</div>
            @endif

            <div class="mt-5 flex items-center gap-4 text-[13px] text-ink-3">
                @if ($skuDisplay)
                    <span>SKU: <span class="text-ink-2 tabular-nums">{{ $skuDisplay }}</span></span>
                @endif
                @if ($product->model_number)
                    <span>·</span>
                    <span>Model: <span class="text-ink-2">{{ $product->model_number }}</span></span>
                @endif
            </div>

            {{-- Key specifications --}}
            @php
                $keySpecs = $product->productAttributes
                    ->where('is_variation_attribute', false)
                    ->filter(fn ($pa) => filled($pa->values))
                    ->take(6);
            @endphp
            @if ($keySpecs->isNotEmpty())
                <div class="mt-6 border-t border-zinc-200 pt-5">
                    <div class="mb-3 text-[12px] font-bold tracking-[0.08em] text-ink-2 uppercase">Key specifications</div>
                    <ul class="space-y-2 text-[13.5px] text-ink-2">
                        @foreach ($keySpecs as $pa)
                            <li class="flex gap-2">
                                <flux:icon.check variant="micro" class="mt-0.5 size-3.5 shrink-0 text-brand-500" />
                                <span><span class="text-ink-3">{{ $pa->attribute?->name }}:</span> {{ collect($pa->values)->implode(', ') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{-- Price block --}}
            <div class="mt-4 border-y border-zinc-200 py-4">
                <div class="flex flex-wrap items-baseline gap-3.5">
                    @if ($displayCompareAt)
                        <span class="text-lg text-ink-4 line-through tabular-nums whitespace-nowrap">{{ money($displayCompareAt) }}</span>
                    @endif
                    <span class="font-serif text-4xl tabular-nums">
                        @if ($displayPrice)
                            {{ money($displayPrice) }}
                        @elseif ($groupedFromCents)
                            <span class="text-base text-ink-3">From</span> {{ money($groupedFromCents) }}
                        @else
                            <span class="text-base text-ink-3">Quote on request</span>
                        @endif
                    </span>
                    @if ($displayPrice && $tax->priceDisplaySuffix())
                        <span class="text-[12.5px] text-ink-3">{{ $tax->priceDisplaySuffix() }}</span>
                    @endif
                </div>

                @php
                    $isGroupedType  = $product->type === \App\Enums\ProductType::GROUPED;
                    $isBundledType  = $product->type === \App\Enums\ProductType::BUNDLE;
                    $stockLineText  = match(true) {
                        $isGroupedType                       => 'Configure your set below',
                        $inStock && $stockQty !== null       => $stockQty.' in stock'.($this->stockLocation ? ' — '.$this->stockLocation : ''),
                        $inStock                             => 'In stock'.($this->stockLocation ? ' — '.$this->stockLocation : ''),
                        $isBackorder                         => 'Available on backorder',
                        default                              => 'Out of stock at present',
                    };
                @endphp
                <div class="mt-3 flex flex-wrap items-center gap-3 text-[13px] text-ink-2">
                    <span class="inline-flex items-center gap-1.5">
                        <span @class([
                            'size-2 rounded-full',
                            'bg-emerald-600' => $inStock,
                            'bg-amber-500'   => ! $inStock && $isBackorder,
                            'bg-zinc-400'    => ! $inStock && ! $isBackorder,
                        ])></span>
                        {{ $stockLineText }}
                    </span>
                </div>

                {{-- Status chips --}}
                @php
                    $chips = collect();
                    if ($isOnSale && $displayCompareAt && $displayPrice && $displayCompareAt > $displayPrice) {
                        $discPct  = (int) round((1 - $displayPrice / $displayCompareAt) * 100);
                        $saved    = $displayCompareAt - $displayPrice;
                        $chips->push(['tone' => 'sale', 'icon' => 'tag', 'label' => 'Save '.$discPct.'% · '.money($saved)]);
                    }
                    if ($product->is_virtual) {
                        $chips->push(['tone' => 'info', 'icon' => 'bolt', 'label' => 'Digital — no shipping']);
                    }
                    if ($product->is_downloadable) {
                        $chips->push(['tone' => 'info', 'icon' => 'arrow-down-tray', 'label' => 'Downloadable files']);
                    }
                    if ($product->min_order_quantity > 1) {
                        $chips->push(['tone' => '', 'icon' => 'cube', 'label' => 'Min order '.$product->min_order_quantity]);
                    }
                @endphp
                @if ($chips->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($chips as $chip)
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[12px] font-semibold',
                                'border-brand-300 bg-brand-50 text-brand-600' => $chip['tone'] === 'sale',
                                'border-emerald-200 bg-emerald-50 text-emerald-700' => $chip['tone'] === 'good',
                                'border-amber-200 bg-amber-50 text-amber-700' => $chip['tone'] === 'warn',
                                'border-red-200 bg-red-50 text-red-700' => $chip['tone'] === 'bad',
                                'border-blue-200 bg-blue-50 text-blue-700' => $chip['tone'] === 'info',
                                'border-zinc-200 bg-white text-ink-2' => $chip['tone'] === '',
                            ])>
                                <flux:icon :name="$chip['icon']" variant="micro" class="size-3.5" />
                                {{ $chip['label'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Variation selector --}}
            @if ($product->type === \App\Enums\ProductType::VARIABLE && $this->variationAttributes->isNotEmpty())
                <div class="mt-6 space-y-4">
                    @foreach ($this->variationAttributes as $attr)
                        @php $chosen = $selectedOptions[$attr['slug']] ?? null; @endphp
                        <div wire:key="attr-{{ $attr['slug'] }}">
                            <div class="mb-2 text-[12px] font-semibold text-ink-2">
                                {{ $attr['name'] }}
                                @if ($chosen)
                                    <span class="font-normal text-ink-3">· {{ optional($attr['values']->firstWhere('slug', $chosen))->label ?: $chosen }}</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($attr['values'] as $val)
                                    @php
                                        $isSel = $chosen === $val->slug;
                                        $avail = $this->isOptionAvailable($attr['slug'], $val->slug);
                                    @endphp
                                    @if ($val->color_code)
                                        <button type="button" wire:click="selectOption('{{ $attr['slug'] }}', '{{ $val->slug }}')"
                                            @disabled(! $avail)
                                            title="{{ $val->label ?: $val->value }}{{ $avail ? '' : ' — out of stock' }}"
                                            @class([
                                                'size-9 rounded-full border-2 transition',
                                                'border-ink ring-1 ring-ink ring-offset-1' => $isSel,
                                                'border-zinc-200' => ! $isSel,
                                                'cursor-pointer hover:border-zinc-400' => $avail,
                                                'cursor-not-allowed opacity-30' => ! $avail,
                                            ])
                                            style="background-color: {{ $val->color_code }}">
                                            <span class="sr-only">{{ $val->label ?: $val->value }}</span>
                                        </button>
                                    @else
                                        <button type="button" wire:click="selectOption('{{ $attr['slug'] }}', '{{ $val->slug }}')"
                                            @disabled(! $avail)
                                            @class([
                                                'min-w-11 rounded border px-3 py-2 text-[13px] font-medium transition',
                                                'border-ink bg-ink text-white' => $isSel,
                                                'border-zinc-200 text-ink hover:border-zinc-400 cursor-pointer' => ! $isSel && $avail,
                                                'cursor-not-allowed text-ink-4 line-through opacity-50' => ! $avail,
                                            ])>
                                            {{ $val->label ?: $val->value }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <flux:error name="variant" />

                    {{-- Variant summary bar — shown once a full combination is selected --}}
                    @if ($variant)
                        @php
                            $vPrice   = $variant->compare_at_price ?? $variant->price;
                            $vDisplay = $vPrice ? $tax->displayPriceCents($product, (int) $vPrice) : null;
                            $vInStock = $variant->stock_status === \App\Enums\StockStatus::IN_STOCK;
                            $vBack    = $variant->stock_status === \App\Enums\StockStatus::BACKORDER;
                        @endphp
                        <div class="mt-3 flex flex-wrap items-center gap-x-2.5 gap-y-1 rounded-lg bg-surface-sunken px-3 py-2.5 text-[12.5px] text-ink-3">
                            <flux:icon.cube variant="micro" class="size-3.5 shrink-0 text-ink-4" />
                            <span>Selected: <b class="font-mono text-ink">{{ $variant->sku }}</b></span>
                            @if ($vDisplay)
                                <span>·</span>
                                <span class="font-semibold text-ink">{{ money($vDisplay) }}</span>
                            @endif
                            <span>·</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span @class(['size-2 rounded-full', 'bg-emerald-500' => $vInStock, 'bg-amber-500' => $vBack, 'bg-red-500' => ! $vInStock && ! $vBack])></span>
                                {{ $vInStock ? 'In stock' : ($vBack ? 'On backorder' : 'Out of stock') }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Qty + CTAs --}}
            @php
                $isGrouped  = $product->type === \App\Enums\ProductType::GROUPED;
                $isVariable = $product->type === \App\Enums\ProductType::VARIABLE;
                // For variable: treat selected-variant stock as OOS/backorder; unselected = can still try
                $ctaOutOfStock = $isOutOfStock && ! $isGrouped && ! $product->requires_quotation
                    && (! $isVariable || $variant !== null);
                $ctaBackorder  = $isBackorder && ! $isGrouped && ! $product->requires_quotation;
                $addToCartLabel = $isGrouped
                    ? 'Choose your items'
                    : ($ctaBackorder ? 'Pre-order' : 'Add to cart');
                $socialSettings = app(\App\Settings\SocialSettings::class);
                $whatsappNumber = preg_replace('/\D+/', '', (string) $socialSettings->whatsapp_number);
                $whatsappOrderEnabled = $socialSettings->whatsapp_order_enabled && filled($whatsappNumber);
            @endphp

            <div x-data="{
                share() {
                    if (navigator.share) {
                        navigator.share({ title: @js($product->name), text: @js(\Illuminate\Support\Str::limit(strip_tags((string) $product->short_description), 100)), url: window.location.href });
                    } else {
                        navigator.clipboard.writeText(window.location.href).then(() => $flux.toast({ text: 'Link copied!', variant: 'success' }));
                    }
                }
            }">
            @if ($product->requires_quotation)
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @if ($this->quotesEnabled)
                        <flux:button variant="customer-primary" size="customer-lg"
                            :href="route('quote.request', ['product' => $product->slug])" wire:navigate>
                            Request a quote
                        </flux:button>
                    @else
                        <flux:button variant="customer-primary" size="customer-lg"
                            :href="route('contact')" wire:navigate>
                            Contact for pricing
                        </flux:button>
                    @endif
                    <flux:button variant="customer-outline" size="customer-lg" :href="route('contact')" wire:navigate>
                        Talk to sales
                    </flux:button>
                    <flux:tooltip content="Share">
                        <flux:button icon="share" square variant="customer-outline" size="customer-lg" aria-label="Share" @click="share()" />
                    </flux:tooltip>
                </div>

            @elseif ($ctaOutOfStock)
                {{-- Out of stock — no stepper, two action buttons --}}
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <flux:button variant="customer-primary" size="customer-lg" disabled>
                        Out of stock
                    </flux:button>
                    <flux:button variant="customer-outline" size="customer-lg" :href="route('contact')" wire:navigate icon="bell">
                        Notify me
                    </flux:button>
                    <flux:tooltip content="Share">
                        <flux:button icon="share" square variant="customer-outline" size="customer-lg" aria-label="Share" @click="share()" />
                    </flux:tooltip>
                </div>

            @else
                {{-- Quantity counter --}}
                @unless ($isGrouped || $isVariable)
                    <div class="mt-6">
                        <p class="mb-2 text-[13px] font-semibold text-ink-2">Quantity</p>
                        <div class="flex items-center gap-1.5">
                            <button type="button" wire:click="decQty" aria-label="Decrease quantity"
                                class="flex size-9 cursor-pointer items-center justify-center rounded border border-zinc-300 text-ink-2 transition hover:bg-surface-sunken">
                                <flux:icon.minus class="size-3.5" />
                            </button>
                            <span class="w-10 text-center text-[14px] font-semibold tabular-nums text-ink">{{ $qty }}</span>
                            <button type="button" wire:click="incQty" aria-label="Increase quantity"
                                class="flex size-9 cursor-pointer items-center justify-center rounded border border-zinc-300 text-ink-2 transition hover:bg-surface-sunken">
                                <flux:icon.plus class="size-3.5" />
                            </button>
                        </div>
                    </div>
                @endunless

                {{-- Primary actions --}}
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <flux:button variant="customer-primary" size="customer-lg" wire:click="addThisToCart">
                        {{ $addToCartLabel }}
                    </flux:button>

                    @if ($this->quotesEnabled && ! $isGrouped)
                        <flux:button variant="customer-outline" size="customer-lg" :href="route('quote.request', ['product' => $product->slug])" wire:navigate>
                            Request a quote
                        </flux:button>
                    @endif
                    <flux:tooltip content="Share">
                        <flux:button icon="share" square variant="customer-outline" size="customer-lg" aria-label="Share" @click="share()" />
                    </flux:tooltip>
                </div>

                {{-- Min order quantity note --}}
                @if (($product->min_order_quantity ?? 1) > 1)
                    <div class="mt-3 flex items-center gap-2 text-[12.5px] text-ink-3">
                        <flux:icon.information-circle variant="micro" class="size-4 shrink-0 text-ink-4" />
                        Minimum order quantity is {{ $product->min_order_quantity }} units.
                    </div>
                @endif
            @endif
            </div>{{-- end CTA / share wrapper --}}

            {{-- Order via WhatsApp --}}
            @if ($whatsappOrderEnabled)
                @php
                    $waPrice = $displayPrice
                        ? strip_tags(money($displayPrice))
                        : ($groupedFromCents ? 'From '.strip_tags(money($groupedFromCents)) : 'Quote on request');
                    $waText = "Hello, I want to buy\n\n*{$product->name}*\n*Price:* {$waPrice}\n*URL:* ".url()->current();
                    $waUrl  = 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($waText);
                @endphp
                <div class="mt-4">
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 bg-[#25D366] px-6 py-2.5 font-serif text-[13px] font-extrabold uppercase tracking-wider text-white transition hover:bg-[#20bd5a]">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        Order on WhatsApp
                    </a>
                </div>
            @endif
        </div>{{-- end product details column --}}

        {{-- Side panel — policies, delivery & seller (no price, no cart) --}}
        <div class="lg:sticky lg:top-24 lg:self-start">
            <flux:card>

            {{-- Trust signals --}}
            @php
                $trust = collect();
                if ($product->brand) {
                    $trust->push(['check-badge', 'Authorised distributor', $product->brand->name]);
                }
                if ($this->deliveryZones->isNotEmpty()) {
                    $counties = $this->deliveryZones->pluck('county')->filter()->unique()->values();
                    $coverage = $counties->isNotEmpty()
                        ? $counties->take(3)->implode(', ').($counties->count() > 3 ? ' +'.($counties->count() - 3).' more' : '')
                        : $this->deliveryZones->count().' '.\Illuminate\Support\Str::plural('zone', $this->deliveryZones->count());
                    $trust->push(['truck', 'Delivery coverage', $coverage]);
                }
                if ($this->stockLocation) {
                    $trust->push(['building-storefront', 'Ships from', $this->stockLocation]);
                }
                if ($product->downloadableFiles->isNotEmpty()) {
                    $trust->push(['document-text', 'Spec sheets & manuals', $product->downloadableFiles->count().' '.\Illuminate\Support\Str::plural('document', $product->downloadableFiles->count())]);
                }
            @endphp
            {{-- Delivery / seller facts --}}
            @php
                $storeName = app(\App\Settings\BrandingSettings::class)->store_name ?: config('app.name', 'Sheffield');
            @endphp
            <div class="flex flex-col gap-3 text-[12.5px]">
                <div class="flex items-start gap-2.5">
                    <flux:icon.building-storefront variant="outline" class="size-4 shrink-0 text-brand-500" />
                    <div>
                        <div class="font-semibold text-ink">Sold by {{ $storeName }}</div>
                        @if ($product->brand)
                            <div class="text-ink-3">Authorised {{ $product->brand->name }} distributor</div>
                        @endif
                    </div>
                </div>
                @foreach ($trust as [$icon, $title, $sub])
                    @continue($icon === 'check-badge')
                    <div class="flex items-start gap-2.5">
                        <flux:icon :name="$icon" variant="outline" class="size-4 shrink-0 text-brand-500" />
                        <div>
                            <div class="font-semibold text-ink">{{ $title }}</div>
                            <div class="text-ink-3">{{ $sub }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Policy quick-links --}}
            <div class="mt-4 flex flex-col divide-y divide-zinc-100 border-t border-zinc-200 pt-4 text-[13.5px]">
                <a href="{{ route('page.show', 'returns-policy') }}" wire:navigate class="group flex items-center justify-between gap-3 py-2.5">
                    <span class="flex items-center gap-2.5 text-ink-2">
                        <flux:icon.arrow-uturn-left variant="outline" class="size-4 shrink-0 text-brand-500" />
                        Return &amp; refund policy
                    </span>
                    <flux:icon.chevron-right variant="micro" class="size-4 shrink-0 text-ink-4 transition group-hover:text-ink-2" />
                </a>
                <a href="{{ route('page.show', 'shipping-policy') }}" wire:navigate class="group flex items-center justify-between gap-3 py-2.5">
                    <span class="flex items-center gap-2.5 text-ink-2">
                        <flux:icon.truck variant="outline" class="size-4 shrink-0 text-brand-500" />
                        Shipping &amp; delivery
                    </span>
                    <flux:icon.chevron-right variant="micro" class="size-4 shrink-0 text-ink-4 transition group-hover:text-ink-2" />
                </a>
            </div>

            {{-- Secure payments --}}
            <div class="mt-4 border-t border-zinc-200 pt-4">
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                    <span class="flex items-center gap-1.5 text-[13px] font-semibold text-ink">
                        <flux:icon.shield-check variant="micro" class="size-4 text-emerald-600" />
                        Secure payments
                    </span>
                    <div class="flex items-center gap-1">
                        @foreach (['Visa', 'Mastercard', 'M-Pesa'] as $method)
                            <span class="rounded border border-zinc-200 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-ink-3">{{ $method }}</span>
                        @endforeach
                    </div>
                </div>
                <p class="mt-2 text-[12px] leading-relaxed text-ink-3">
                    Every payment is protected with SSL encryption and trusted M-Pesa &amp; card processing.
                </p>
            </div>

            {{-- Talk to sales --}}
            <a href="{{ route('contact') }}" wire:navigate
                class="mt-4 flex items-center gap-1.5 border-t border-zinc-200 pt-4 text-[12.5px] font-semibold text-brand-500 underline-offset-2 hover:underline">
                <flux:icon.chat-bubble-left-right variant="micro" class="size-4" />
                Questions? Talk to sales
            </a>
            </flux:card>{{-- end side panel --}}
        </div>{{-- end side panel column --}}
    </div>

    {{-- Accessories / spare parts add-on carousel --}}
    @php
        $hasAccessories = $product->accessories->isNotEmpty();
        $hasSpareParts  = $product->spareParts->isNotEmpty();
        // Auto-correct stale tab: if 'accessories' is selected but none exist, switch to 'spares'
        if ($addonTab === 'accessories' && ! $hasAccessories && $hasSpareParts) {
            $addonTab = 'spares';
        }
        if ($addonTab === 'spares' && ! $hasSpareParts && $hasAccessories) {
            $addonTab = 'accessories';
        }
    @endphp
    @if ($hasAccessories || $hasSpareParts)
        <div class="mt-16">
            {{-- Tab bar --}}
            <div class="flex border-b border-zinc-200">
                @if ($hasAccessories)
                    <button type="button" wire:click="setAddonTab('accessories')"
                        @class([
                            '-mb-px border-b-2 px-5 py-3.5 text-[14px] transition',
                            'border-brand-500 font-semibold text-ink' => $addonTab === 'accessories',
                            'border-transparent text-ink-3 hover:text-ink' => $addonTab !== 'accessories',
                        ])>
                        Accessories
                        <span class="ml-1 text-[11px] text-ink-4">{{ $product->accessories->count() }}</span>
                    </button>
                @endif
                @if ($hasSpareParts)
                    <button type="button" wire:click="setAddonTab('spares')"
                        @class([
                            '-mb-px border-b-2 px-5 py-3.5 text-[14px] transition',
                            'border-brand-500 font-semibold text-ink' => $addonTab === 'spares',
                            'border-transparent text-ink-3 hover:text-ink' => $addonTab !== 'spares',
                        ])>
                        Spare parts
                        <span class="ml-1 text-[11px] text-ink-4">{{ $product->spareParts->count() }}</span>
                    </button>
                @endif
            </div>

            {{-- Carousel --}}
            @php $addonItems = $addonTab === 'spares' ? $product->spareParts : $product->accessories; @endphp
            <div class="relative mt-5" data-addon-carousel>
                <button type="button"
                    class="addon-prev absolute -left-4 top-[40%] z-10 -translate-y-1/2 hidden size-9 items-center justify-center rounded-full border border-zinc-200 bg-white shadow-sm text-ink-2 transition hover:bg-surface-sunken sm:inline-flex disabled:opacity-30">
                    <flux:icon.chevron-left variant="micro" class="size-4" />
                </button>

                <div class="swiper overflow-hidden">
                    <div class="swiper-wrapper items-stretch">
                        @foreach ($addonItems as $addon)
                            @php
                                $addonPrice   = $addon->sale_price ?? $addon->price;
                                $addonTax     = app(\App\Support\TaxCalculator::class);
                                $addonDisplay = $addonPrice ? $addonTax->displayPriceCents($addon, (int) $addonPrice) : null;
                                $addonInStock = $addon->stock_status === \App\Enums\StockStatus::IN_STOCK;
                                $addonCover   = $addon->images->first()?->url ?? $addon->cover_url;
                            @endphp
                            <div class="swiper-slide !h-auto overflow-hidden rounded-lg border border-zinc-200 bg-white transition hover:shadow-md"
                                 wire:key="addon-{{ $addon->id }}">
                                <a href="{{ route('product.show', $addon) }}" wire:navigate class="block">
                                    <div class="relative h-[132px] overflow-hidden bg-surface-sunken">
                                        @if ($addonCover)
                                            <img src="{{ $addonCover }}" alt="{{ $addon->name }}"
                                                class="absolute inset-0 size-full object-cover" loading="lazy" />
                                        @else
                                            <div class="grid size-full place-items-center text-ink-4">
                                                <flux:icon.photo class="size-8" />
                                            </div>
                                        @endif
                                    </div>
                                </a>
                                <div class="flex flex-col border-t border-zinc-200 p-3">
                                    <a href="{{ route('product.show', $addon) }}" wire:navigate
                                        class="min-h-[34px] text-[13px] font-medium leading-snug text-ink line-clamp-2">
                                        {{ $addon->name }}
                                    </a>
                                    @if ($addon->sku)
                                        <div class="mt-1 font-mono text-[10.5px] text-ink-4">{{ $addon->sku }}</div>
                                    @endif
                                    <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] {{ $addonInStock ? 'text-emerald-600' : 'text-ink-3' }}">
                                        <span class="size-1.5 rounded-full {{ $addonInStock ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                        {{ $addonInStock ? 'In stock' : 'Made to order' }}
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <span class="text-[14px] font-bold tabular-nums text-ink">
                                            {!! $addonDisplay ? money($addonDisplay) : 'POA' !!}
                                        </span>
                                        @if ($addonInStock)
                                            <button type="button" wire:click="addToCart('{{ $addon->slug }}')"
                                                class="inline-flex h-[34px] items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 text-[12.5px] font-semibold text-ink transition hover:bg-brand-500 hover:border-brand-500 hover:text-white">
                                                <flux:icon.plus variant="micro" class="size-3.5" />
                                                Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="button"
                    class="addon-next absolute -right-4 top-[40%] z-10 -translate-y-1/2 hidden size-9 items-center justify-center rounded-full border border-zinc-200 bg-white shadow-sm text-ink-2 transition hover:bg-surface-sunken sm:inline-flex disabled:opacity-30">
                    <flux:icon.chevron-right variant="micro" class="size-4" />
                </button>
            </div>
        </div>
    @endif

    @assets
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    @endassets

    @script
    <script>
        let addonSwiper = null;

        const initAddonSwiper = () => {
            const wrap = $wire.$el.querySelector('[data-addon-carousel]');
            if (!wrap) return;
            if (addonSwiper) { addonSwiper.destroy(true, true); addonSwiper = null; }
            addonSwiper = new Swiper(wrap.querySelector('.swiper'), {
                slidesPerView: 2.5,
                spaceBetween: 14,
                freeMode: true,
                grabCursor: true,
                navigation: {
                    nextEl: wrap.querySelector('.addon-next'),
                    prevEl: wrap.querySelector('.addon-prev'),
                },
                breakpoints: {
                    640:  { slidesPerView: 3.5 },
                    1024: { slidesPerView: 5.5 },
                },
            });
        };

        initAddonSwiper();

        const cleanupHook = Livewire.hook('commit', ({ component, succeed }) => {
            if (component.el === $wire.$el) {
                succeed(() => initAddonSwiper());
            }
        });

        document.addEventListener('livewire:navigating', () => {
            cleanupHook();
            addonSwiper?.destroy(true, true);
        }, { once: true });
    </script>
    @endscript

    {{-- Tabs --}}
    <div class="mt-16" id="product-tabs">
        @php
            $productTabs = [['id' => 'overview', 'label' => 'Description',    'count' => null]];
            $productTabs[] =  ['id' => 'specs',    'label' => 'Specifications', 'count' => null];
            if ($product->downloadableFiles->isNotEmpty()) {
                $productTabs[] = [
                    'id'    => $product->is_downloadable ? 'downloads' : 'documents',
                    'label' => $product->is_downloadable ? 'Downloads' : 'Documents',
                    'count' => $product->downloadableFiles->count(),
                ];
            }
            if ($this->reviewsEnabled) {
                $productTabs[] = ['id' => 'reviews', 'label' => 'Reviews', 'count' => $this->approvedReviews->count() ?: null];
            }
            // Guard activeTab against tabs that no longer exist
            $validTabIds = array_column($productTabs, 'id');
            if (! in_array($activeTab, $validTabIds, true)) {
                $activeTab = 'overview';
            }
        @endphp
        <div class="flex border-b border-zinc-200">
            @foreach ($productTabs as $tab)
                <button type="button" wire:click="$set('activeTab', '{{ $tab['id'] }}')"
                    @class([
                        '-mb-px cursor-pointer border-b-2 px-5 py-3.5 text-[14px] transition whitespace-nowrap',
                        'border-brand-500 font-semibold text-ink' => $activeTab === $tab['id'],
                        'border-transparent text-ink-3 hover:text-ink' => $activeTab !== $tab['id'],
                    ])>
                    {{ $tab['label'] }}
                    @if ($tab['count'])
                        <span class="ml-1 text-[11px] {{ $activeTab === $tab['id'] ? 'text-ink-4' : 'text-ink-4' }}">{{ $tab['count'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="pt-8">
            {{-- Specs --}}
            @if ($activeTab === 'specs')
                @if (filled($product->technical_specification))
                    <div class="max-w-5xl pdp-rich-text text-[14px] leading-relaxed text-ink-2">
                        {!! $product->technical_specification !!}
                    </div>
                @else
                    <div class="max-w-5xl text-[14px] text-ink-3">No specifications listed for this product yet.</div>
                @endif
            @endif

            {{-- Overview --}}
            @if ($activeTab === 'overview')
                @php $brandBlurb = $product->brand?->description; @endphp
                @if (filled($product->description) || filled($brandBlurb))
                    <div class="grid max-w-5xl grid-cols-1 gap-12 md:grid-cols-2">
                        @if (filled($product->description))
                            <div>
                                <h3 class="font-serif text-2xl">About this product</h3>
                                <div class="pdp-rich-text mt-4 text-[14.5px] leading-relaxed text-ink-2">{!! $product->description !!}</div>
                            </div>
                        @endif
                        @if (filled($brandBlurb))
                            <div>
                                <h3 class="font-serif text-2xl">About {{ $product->brand->name }}</h3>
                                <div class="mt-4 text-[14.5px] leading-relaxed text-ink-2">{!! nl2br(e($brandBlurb)) !!}</div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="max-w-5xl text-[14px] text-ink-3">No overview available for this product yet.</div>
                @endif
            @endif

            {{-- Documents / Downloads --}}
            @if (in_array($activeTab, ['documents', 'downloads']))
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
            @if ($activeTab === 'reviews' && $this->reviewsEnabled)
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
                                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12.5px] text-ink-3">
                                            <span>{{ $review->author_name }} · {{ $review->created_at->format('d M Y') }}</span>
                                            @if ($review->verified_purchase)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                    <svg class="size-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                                    Verified Purchase
                                                </span>
                                            @endif
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

                </div>
            @endif
        </div>
    </div>

    {{-- You May Also Like (same category) --}}
    @if ($this->related->isNotEmpty())
        <div class="mt-16">
            <div class="mb-4 flex items-baseline justify-between">
                <h2 class="text-[22px] font-semibold tracking-tight">You May Also Like</h2>
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

    {{-- More from [Brand] --}}
    @if ($this->brandProducts->isNotEmpty())
        <div class="mt-16">
            <div class="mb-4 flex items-baseline justify-between">
                <h2 class="text-[22px] font-semibold tracking-tight">More from {{ $product->brand->name }}</h2>
                @if ($product->brand)
                    <a href="{{ route('catalog', ['brand' => [$product->brand->id]]) }}" wire:navigate
                        class="inline-flex items-center gap-1 text-[13px] text-zinc-600 hover:text-zinc-900">
                        View all {{ $product->brand->name }} <flux:icon.arrow-right variant="micro" class="size-3.5" />
                    </a>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
                @foreach ($this->brandProducts as $bp)
                    <x-storefront.product-card :product="$bp" wire:key="bp-{{ $bp->id }}" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Customers who viewed this also viewed --}}
    @if ($this->alsoViewed->isNotEmpty())
        <div class="mt-16">
            <div class="mb-4">
                <h2 class="text-[22px] font-semibold tracking-tight">Customers who viewed this also viewed</h2>
            </div>
            <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
                @foreach ($this->alsoViewed as $av)
                    <x-storefront.product-card :product="$av" wire:key="av-{{ $av->id }}" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recently Viewed (auth users only) --}}
    @auth
        @if ($this->recentlyViewedProducts->isNotEmpty())
            <div class="mt-16">
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 class="text-[22px] font-semibold tracking-tight">Recently Viewed</h2>
                    <a href="{{ route('account.recently-viewed') }}" wire:navigate
                        class="inline-flex items-center gap-1 text-[13px] text-zinc-600 hover:text-zinc-900">
                        View all <flux:icon.arrow-right variant="micro" class="size-3.5" />
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4 2xl:grid-cols-6">
                    @foreach ($this->recentlyViewedProducts as $rv)
                        <x-storefront.product-card :product="$rv" wire:key="rv-{{ $rv->id }}" />
                    @endforeach
                </div>
            </div>
        @endif
    @endauth

    {{-- Bundle / grouped add-to-cart modal --}}
    @if (in_array($product->type, [\App\Enums\ProductType::BUNDLE, \App\Enums\ProductType::GROUPED], true))
        <flux:modal wire:model.self="showBundleModal" class="md:w-[560px]">
            @if ($product->type === \App\Enums\ProductType::BUNDLE)
                <flux:heading>What's in this bundle</flux:heading>
                <flux:subheading>{{ $product->name }} ships as a single package made up of the components below.</flux:subheading>

                <div class="mt-5 divide-y divide-zinc-100">
                    @foreach ($product->bundleItems as $item)
                        @php
                            $child = $item->product;
                            $lineCents = (int) ($item->price_override ?? $child?->sale_price ?? $child?->price ?? 0);
                        @endphp
                        <div class="flex items-center gap-3 py-3" wire:key="bundle-{{ $item->id }}">
                            <div class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                @if ($child?->cover_url)
                                    <img src="{{ $child->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                @else
                                    <div class="grid size-full place-items-center text-ink-4"><flux:icon.cube variant="micro" class="size-4" /></div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[13px] font-semibold text-ink">{{ $child?->name ?? 'Component unavailable' }}</div>
                                <div class="mt-0.5 flex items-center gap-2 text-[12px] text-ink-3">
                                    <span class="tabular-nums">Qty {{ $item->quantity }}</span>
                                    @if ($item->is_optional)
                                        <flux:badge size="sm" color="zinc" inset="top bottom">Optional</flux:badge>
                                    @endif
                                </div>
                            </div>
                            <div class="text-[12.5px] font-semibold tabular-nums text-ink">{!! $lineCents ? money($lineCents) : '—' !!}</div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-4">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-ink-3">Bundle price</div>
                        <div class="font-serif text-2xl tabular-nums">{!! $this->bundlePriceCents ? money($this->bundlePriceCents) : 'Quote on request' !!}</div>
                    </div>
                    <flux:button variant="primary" icon="shopping-cart" wire:click="addBundleToCart">Add to cart</flux:button>
                </div>
            @else
                <flux:heading>Choose your items</flux:heading>
                <flux:subheading>Set a quantity for any product in this set — each is added to your cart on its own.</flux:subheading>

                <div class="mt-5 divide-y divide-zinc-100">
                    @foreach ($product->groupedItems as $child)
                        @php $childPrice = $child->sale_price ?? $child->price; @endphp
                        <div class="flex items-center gap-3 py-3" wire:key="grouped-{{ $child->id }}">
                            <div class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                @if ($child->cover_url)
                                    <img src="{{ $child->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                @else
                                    <div class="grid size-full place-items-center text-ink-4"><flux:icon.cube variant="micro" class="size-4" /></div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[13px] font-semibold text-ink">{{ $child->name }}</div>
                                <div class="text-[12px] text-ink-3 tabular-nums">{!! $childPrice ? money($childPrice) : 'POA' !!}</div>
                            </div>
                            <div class="inline-flex h-9 shrink-0 items-stretch overflow-hidden rounded border border-zinc-200">
                                <button type="button" wire:click="decGroupedQty('{{ $child->slug }}')" aria-label="Decrease quantity"
                                    class="grid w-8 cursor-pointer place-items-center text-ink-2 transition hover:bg-surface-sunken">
                                    <flux:icon.minus variant="micro" class="size-3.5" />
                                </button>
                                <div class="grid w-9 place-items-center border-x border-zinc-200 text-[13px] font-semibold tabular-nums">
                                    {{ $groupedQty[$child->slug] ?? 0 }}
                                </div>
                                <button type="button" wire:click="incGroupedQty('{{ $child->slug }}')" aria-label="Increase quantity"
                                    class="grid w-8 cursor-pointer place-items-center text-ink-2 transition hover:bg-surface-sunken">
                                    <flux:icon.plus variant="micro" class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:error name="groupedQty" class="mt-3" />

                <div class="mt-5 flex justify-end gap-3 border-t border-zinc-200 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" icon="shopping-cart" wire:click="addGroupedToCart">Add to cart</flux:button>
                </div>
            @endif
        </flux:modal>
    @endif
</div>
