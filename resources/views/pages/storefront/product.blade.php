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
use App\Settings\ShippingSettings;
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

    public bool $showBundleModal = false;

    /** @var array<string, int> Selected quantities per grouped-child slug. */
    public array $groupedQty = [];

    /** @var array<string, string> Selected variation option per attribute slug (variable products). */
    public array $selectedOptions = [];

    public string $activeTab = 'specs';

    public int $galleryIdx = 0;

    public string $addonTab = 'accessories';

    public int $reviewRating = 5;

    public string $reviewTitle = '';

    public string $reviewBody = '';

    /** @var list<int> Related-product IDs, picked once at mount so they stay stable across round-trips. */
    public array $relatedIds = [];

    public function mount(Product $product): void
    {
        $this->product = $product->load([
            'brand',
            'primaryCategory',
            'images',
            'productAttributes' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order'),
            'productAttributes.attribute',
            'downloadableFiles',
            'accessories' => fn ($q) => $q->with(['images' => fn ($q) => $q->where('is_cover', true)->limit(1)])->orderBy('sort_order'),
            'spareParts'  => fn ($q) => $q->with(['images' => fn ($q) => $q->where('is_cover', true)->limit(1)])->orderBy('sort_order'),
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

        $this->relatedIds = $this->pickRelatedIds();

        $this->applySeo();
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
                'priceCurrency' => app(\App\Settings\LocalizationSettings::class)->currency,
                'availability' => $availability,
                'url' => url()->current(),
                'seller' => ['@type' => 'Organization', 'name' => 'Sheffield'],
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
        return app(\App\Settings\ReviewSettings::class)->reviews_enabled;
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

    public function submitReview(): void
    {
        $settings = app(\App\Settings\ReviewSettings::class);

        if (! $settings->reviews_enabled) {
            return;
        }

        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->validate([
            'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviewTitle' => ['nullable', 'string', 'max:120'],
            'reviewBody' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($settings->require_verified_purchase && ! $this->hasPurchasedProduct()) {
            $this->addError('reviewBody', 'Only customers who have purchased this product can review it.');

            return;
        }

        $approved = $settings->auto_approve;

        $this->product->reviews()->create([
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'rating' => $this->reviewRating,
            'title' => $this->reviewTitle ?: null,
            'body' => $this->reviewBody,
            'status' => $approved ? ReviewStatus::APPROVED : ReviewStatus::PENDING,
        ]);

        $this->reset(['reviewTitle', 'reviewBody']);
        $this->reviewRating = 5;

        if ($approved) {
            unset($this->approvedReviews);
        }

        Flux::toast(
            heading: 'Thank you!',
            text: $approved
                ? 'Your review is now live.'
                : 'Your review has been submitted for moderation.',
            variant: 'success',
        );
    }

    public function setAddonTab(string $tab): void
    {
        $this->addonTab = $tab;
    }

    /** Whether the signed-in user has an order containing this product. */
    private function hasPurchasedProduct(): bool
    {
        return auth()->user()
            ->orders()
            ->whereHas('items', fn ($query) => $query->where('product_id', $this->product->id))
            ->exists();
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
        <div
            x-data="{
                lens: null,
                lbOpen: false,
                lbIdx: {{ $galleryIdx }},
                gallery: @js($gallery->values()->map(fn ($img) => ['url' => $img->url, 'alt' => $img->alt ?? $product->name, 'label' => $img->alt ?? ''])),
                lbScale: 1, lbPan: { x:0, y:0 }, lbPanStart: null, lbPinchDist: 0,
                openLb(i) { this.lbIdx = i; this.lbOpen = true; this.lbReset(); document.body.style.overflow = 'hidden'; },
                closeLb() { this.lbOpen = false; document.body.style.overflow = ''; },
                prevLb() { this.lbIdx = (this.lbIdx - 1 + this.gallery.length) % this.gallery.length; this.lbReset(); },
                nextLb() { this.lbIdx = (this.lbIdx + 1) % this.gallery.length; this.lbReset(); },
                lbReset() { this.lbScale = 1; this.lbPan = { x:0, y:0 }; },
                lbClampPan(p, sc) {
                    const s = this.$refs.lbStage;
                    if (!s) return p;
                    const r = s.getBoundingClientRect();
                    const mx = (r.width  * (sc - 1)) / 2;
                    const my = (r.height * (sc - 1)) / 2;
                    return { x: Math.max(-mx, Math.min(mx, p.x)), y: Math.max(-my, Math.min(my, p.y)) };
                },
                lbZoomTo(next, cx, cy) {
                    const sc = Math.max(1, Math.min(4.5, next));
                    const s = this.$refs.lbStage;
                    if (s && cx != null) {
                        const r = s.getBoundingClientRect();
                        const ox = cx - r.left - r.width  / 2;
                        const oy = cy - r.top  - r.height / 2;
                        const prev = this.lbScale;
                        this.lbPan = this.lbClampPan({ x: this.lbPan.x - ox * (sc / prev - 1), y: this.lbPan.y - oy * (sc / prev - 1) }, sc);
                    } else {
                        this.lbPan = this.lbClampPan(this.lbPan, sc);
                    }
                    if (sc === 1) this.lbPan = { x:0, y:0 };
                    this.lbScale = sc;
                },
                lbWheel(e) { e.preventDefault(); this.lbZoomTo(this.lbScale * (e.deltaY < 0 ? 1.18 : 0.85), e.clientX, e.clientY); },
                lbDblClick(e) { this.lbScale > 1.02 ? this.lbReset() : this.lbZoomTo(2.6, e.clientX, e.clientY); },
                lbPointerDown(e) { if (this.lbScale > 1) { this.lbPanStart = { x: e.clientX, y: e.clientY, px: this.lbPan.x, py: this.lbPan.y }; } },
                lbPointerMove(e) { if (this.lbPanStart && this.lbScale > 1) { this.lbPan = this.lbClampPan({ x: this.lbPanStart.px + (e.clientX - this.lbPanStart.x), y: this.lbPanStart.py + (e.clientY - this.lbPanStart.y) }, this.lbScale); } },
                lbPointerUp() { this.lbPanStart = null; },
                lbTouchStart(e) { if (e.touches.length === 2) { this.lbPinchDist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY); } },
                lbTouchMove(e) {
                    if (e.touches.length === 2) {
                        e.preventDefault();
                        const d = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
                        if (this.lbPinchDist) this.lbZoomTo(this.lbScale * (d / this.lbPinchDist));
                        this.lbPinchDist = d;
                    }
                },
            }"
            @keydown.escape.window="if (lbOpen) closeLb()"
            @keydown.arrow-left.window="if (lbOpen) { prevLb(); }"
            @keydown.arrow-right.window="if (lbOpen) { nextLb(); }"
            @keydown.plus.window="if (lbOpen) lbZoomTo(lbScale * 1.3)"
            @keydown.minus.window="if (lbOpen) lbZoomTo(lbScale / 1.3)"
        >
            {{-- Gallery: vertical thumbs (left) + main image (right) --}}
            <div class="flex gap-2.5">

                {{-- Vertical thumbnail strip --}}
                @if ($gallery->count() > 0)
                    <div class="flex flex-col gap-2 overflow-y-auto" style="height: 520px; scrollbar-width: none;">
                        @foreach ($gallery as $i => $img)
                            <button type="button"
                                wire:click="$set('galleryIdx', {{ $i }})"
                                x-on:click="lbIdx = {{ $i }}"
                                @class([
                                    'w-[72px] cursor-pointer overflow-hidden rounded border bg-white p-1.5 transition',
                                    'flex-1 min-h-[56px]' => $gallery->count() > 1,
                                    'aspect-square' => $gallery->count() === 1,
                                    'border-brand-500 ring-1 ring-brand-500' => $i === $galleryIdx,
                                    'border-zinc-200 hover:border-zinc-400' => $i !== $galleryIdx,
                                ])>
                                <img src="{{ $img->url }}" alt="{{ $img->alt ?? '' }}"
                                    class="size-full object-contain" loading="lazy" />
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
                    @click="openLb(lbIdx)"
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
                            class="size-full object-contain p-6 transition-transform duration-75 will-change-transform"
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

            {{-- Lightbox overlay — teleported to <body> so page-fade's animation stacking context can't clip it --}}
            <template x-teleport="body">
            <div
                x-show="lbOpen"
                x-cloak
                x-transition:enter="transition-opacity duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[200] flex flex-col bg-zinc-900/40 backdrop-blur-xl"
                @click.self="closeLb()"
            >
                {{-- Top bar --}}
                <div class="flex shrink-0 items-center justify-between px-5 py-4">
                    <span class="font-mono text-[12px] tracking-[0.03em] text-white/70">
                        <span x-text="gallery[lbIdx]?.label || '{{ $product->name }}'"></span>
                        <template x-if="gallery.length > 1">
                            <span x-text="' · ' + (lbIdx + 1) + '/' + gallery.length"></span>
                        </template>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span x-text="Math.round(lbScale * 100) + '%'" class="min-w-10 text-right font-mono text-[12.5px] text-white/70"></span>
                        <button @click="lbZoomTo(lbScale / 1.4)" title="Zoom out"
                            class="inline-flex size-9 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white transition hover:bg-white/25">
                            <flux:icon.minus variant="micro" class="size-4" />
                        </button>
                        <button @click="lbZoomTo(lbScale * 1.4)" title="Zoom in"
                            class="inline-flex size-9 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white transition hover:bg-white/25">
                            <flux:icon.plus variant="micro" class="size-4" />
                        </button>
                        <button @click="lbReset()" title="Reset zoom"
                            class="inline-flex size-9 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white transition hover:bg-white/25">
                            <flux:icon.arrow-path variant="micro" class="size-4" />
                        </button>
                        <button @click="closeLb()" title="Close (Esc)"
                            class="inline-flex size-9 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white transition hover:bg-brand-500 hover:border-brand-500">
                            <flux:icon.x-mark variant="micro" class="size-4" />
                        </button>
                    </div>
                </div>

                {{-- Image area with zoom / pan / pinch --}}
                <div
                    x-ref="lbStage"
                    class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden touch-none"
                    :style="lbScale > 1 ? (lbPanStart ? 'cursor:grabbing' : 'cursor:grab') : 'cursor:zoom-in'"
                    x-init="$nextTick(() => { $el.addEventListener('wheel', e => lbWheel(e), { passive: false }); })"
                    @dblclick="lbDblClick($event)"
                    @pointerdown="lbPointerDown($event)"
                    @pointermove="lbPointerMove($event)"
                    @pointerup="lbPointerUp()"
                    @pointercancel="lbPointerUp()"
                    @touchstart.prevent="lbTouchStart($event)"
                    @touchmove.prevent="lbTouchMove($event)"
                >
                    <template x-if="gallery.length > 1">
                        <button @click.stop="prevLb()"
                            class="absolute left-4 z-10 inline-flex size-12 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-sm transition hover:bg-white/25">
                            <flux:icon.chevron-left class="size-5" />
                        </button>
                    </template>

                    <img :src="gallery[lbIdx]?.url" :alt="gallery[lbIdx]?.alt"
                        :style="`transform: translate(${lbPan.x}px, ${lbPan.y}px) scale(${lbScale}); transition: transform 0.08s ease`"
                        class="max-h-full max-w-[88%] select-none object-contain"
                        draggable="false" />

                    <template x-if="gallery.length > 1">
                        <button @click.stop="nextLb()"
                            class="absolute right-4 z-10 inline-flex size-12 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-sm transition hover:bg-white/25">
                            <flux:icon.chevron-right class="size-5" />
                        </button>
                    </template>
                </div>

                {{-- Hint --}}
                <div class="shrink-0 py-1 text-center font-mono text-[10.5px] tracking-[0.03em] text-white/50">
                    Scroll or pinch to zoom · drag to pan · double-click to toggle · ← → navigate · Esc close
                </div>

                {{-- Thumbnail strip --}}
                <div class="flex shrink-0 flex-wrap justify-center gap-2.5 px-4 pb-5">
                    <template x-for="(img, i) in gallery" :key="i">
                        <button @click="lbIdx = i"
                            class="size-[60px] overflow-hidden rounded-lg border-2 opacity-55 transition"
                            :class="i === lbIdx ? 'opacity-100 border-white' : 'border-transparent hover:opacity-85'">
                            <img :src="img.url" :alt="img.alt" class="size-full object-cover" />
                        </button>
                    </template>
                </div>
            </div>
            </template>{{-- end x-teleport --}}
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

            {{-- Price block --}}
            <div class="mt-6 border-y border-zinc-200 py-5">
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
                    if ($inStock) {
                        $chips->push(['tone' => 'good', 'icon' => 'check-circle', 'label' => 'In stock']);
                    } elseif ($isBackorder) {
                        $chips->push(['tone' => 'warn', 'icon' => 'clock', 'label' => 'Available on backorder']);
                    } elseif ($isOutOfStock) {
                        $chips->push(['tone' => 'bad', 'icon' => 'x-circle', 'label' => 'Out of stock']);
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
                    : ($ctaBackorder
                        ? 'Pre-order'
                        : 'Add to cart'.($displayPrice && ! $isGrouped && ! $isVariable ? ' · '.money($displayPrice * $qty) : ''));
            @endphp

            @if ($product->requires_quotation)
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @if ($this->quotesEnabled)
                        <flux:button variant="primary" icon="document-text" class="h-12! flex-1! px-6!"
                            :href="route('quote.request', ['product' => $product->slug])" wire:navigate>
                            Request a quote
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="chat-bubble-left-right" class="h-12! flex-1! px-6!"
                            :href="route('contact')" wire:navigate>
                            Contact for pricing
                        </flux:button>
                    @endif
                    <flux:button class="h-12! px-5!" :href="route('contact')" wire:navigate>
                        Talk to sales
                    </flux:button>
                </div>

            @elseif ($ctaOutOfStock)
                {{-- Out of stock — no stepper, two action buttons --}}
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <flux:button variant="primary" disabled class="h-12! flex-1! px-6!" icon="shopping-cart">
                        Out of stock
                    </flux:button>
                    <flux:button class="h-12! px-5!" :href="route('contact')" wire:navigate icon="bell">
                        Notify me
                    </flux:button>
                </div>

            @else
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @unless ($isGrouped || $isVariable)
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
                    @endunless

                    <flux:button variant="primary" wire:click="addThisToCart" class="h-12! flex-1! px-6!"
                        icon="{{ $isGrouped ? 'squares-2x2' : 'shopping-cart' }}">
                        {{ $addToCartLabel }}
                    </flux:button>

                    @if ($this->quotesEnabled && ! $isGrouped)
                        <flux:button class="h-12! px-5!" :href="route('quote.request', ['product' => $product->slug])" wire:navigate>
                            Request a quote
                        </flux:button>
                    @endif
                </div>

                {{-- Min order quantity note --}}
                @if (($product->min_order_quantity ?? 1) > 1)
                    <div class="mt-3 flex items-center gap-2 text-[12.5px] text-ink-3">
                        <flux:icon.information-circle variant="micro" class="size-4 shrink-0 text-ink-4" />
                        Minimum order quantity is {{ $product->min_order_quantity }} units.
                    </div>
                @endif
            @endif

            {{-- Trust grid — real signals only --}}
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
            @if ($trust->count() >= 2)
                <div class="mt-7 grid grid-cols-2 gap-3 border-t border-zinc-200 pt-5 text-[12.5px]">
                    @foreach ($trust as [$icon, $title, $sub])
                        <div class="flex items-start gap-2.5">
                            <flux:icon :name="$icon" variant="outline" class="size-4 shrink-0 text-brand-500" />
                            <div>
                                <div class="font-semibold text-ink">{{ $title }}</div>
                                <div class="text-ink-3">{{ $sub }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
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
    <div class="mt-20">
        @php
            $productTabs = [['id' => 'specs',    'label' => 'Specifications', 'count' => null]];
            $productTabs[] =  ['id' => 'overview', 'label' => 'Overview',        'count' => null];
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
                $activeTab = 'specs';
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
            <div class="mb-4 flex items-baseline justify-between">
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
