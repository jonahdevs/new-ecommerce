<?php

use App\Settings\CheckoutSettings;
use App\Settings\DownloadSettings;
use App\Settings\InventorySettings;
use App\Settings\QuotationSettings;
use App\Settings\ReviewSettings;
use App\Settings\ShippingSettings;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('App settings — Admin')] class extends Component
{
    #[Url]
    public string $section = 'inventory';

    // ─── Inventory ─────────────────────────────────────────────────────────────
    public bool $track_stock_by_default = true;

    public int $low_stock_threshold = 5;

    public string $out_of_stock_behavior = 'show';

    public bool $allow_backorders_by_default = false;

    // ─── Reviews ───────────────────────────────────────────────────────────────
    public bool $reviews_enabled = true;

    public bool $require_verified_purchase = true;

    public bool $auto_approve = false;

    // ─── Checkout ──────────────────────────────────────────────────────────────

    public int $min_order_value = 0;

    public string $order_prefix = 'SO-';

    public int $order_next_number = 1001;

    // ─── Quotations ────────────────────────────────────────────────────────────
    public bool $quotes_enabled = true;

    public int $default_validity_days = 30;

    public string $quote_prefix = 'QT-';

    public string $quote_terms = '';

    // ─── Downloads ─────────────────────────────────────────────────────────────
    public int $default_download_limit = 0;

    public int $default_expiry_days = 0;

    public bool $secure_downloads = true;

    // ─── Shipping ──────────────────────────────────────────────────────────────
    public int $free_shipping_threshold = 0;

    public int $handling_fee = 0;

    public bool $local_pickup_enabled = true;

    public string $pickup_address = '';

    public function mount(
        InventorySettings $inventory,
        ReviewSettings $reviews,
        CheckoutSettings $checkout,
        QuotationSettings $quotations,
        DownloadSettings $downloads,
        ShippingSettings $shipping,
    ): void {
        $this->track_stock_by_default = $inventory->track_stock_by_default;
        $this->low_stock_threshold = $inventory->low_stock_threshold;
        $this->out_of_stock_behavior = $inventory->out_of_stock_behavior;
        $this->allow_backorders_by_default = $inventory->allow_backorders_by_default;

        $this->reviews_enabled = $reviews->reviews_enabled;
        $this->require_verified_purchase = $reviews->require_verified_purchase;
        $this->auto_approve = $reviews->auto_approve;

        $this->min_order_value = $checkout->min_order_value;
        $this->order_prefix = $checkout->order_prefix;
        $this->order_next_number = $checkout->order_next_number;

        $this->quotes_enabled = $quotations->quotes_enabled;
        $this->default_validity_days = $quotations->default_validity_days;
        $this->quote_prefix = $quotations->quote_prefix;
        $this->quote_terms = $quotations->quote_terms;

        $this->default_download_limit = $downloads->default_download_limit;
        $this->default_expiry_days = $downloads->default_expiry_days;
        $this->secure_downloads = $downloads->secure_downloads;

        $this->free_shipping_threshold = $shipping->free_shipping_threshold;
        $this->handling_fee = $shipping->handling_fee;
        $this->local_pickup_enabled = $shipping->local_pickup_enabled;
        $this->pickup_address = $shipping->pickup_address;
    }

    public function saveInventory(InventorySettings $settings): void
    {
        $this->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'out_of_stock_behavior' => ['required', 'in:show,hide'],
        ]);

        $settings->fill([
            'track_stock_by_default' => $this->track_stock_by_default,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'out_of_stock_behavior' => $this->out_of_stock_behavior,
            'allow_backorders_by_default' => $this->allow_backorders_by_default,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Inventory settings updated.', variant: 'success');
    }

    public function saveReviews(ReviewSettings $settings): void
    {
        $settings->fill([
            'reviews_enabled' => $this->reviews_enabled,
            'require_verified_purchase' => $this->require_verified_purchase,
            'auto_approve' => $this->auto_approve,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Review settings updated.', variant: 'success');
    }

    public function saveCheckout(CheckoutSettings $settings): void
    {
        $this->validate([
            'min_order_value' => ['required', 'integer', 'min:0'],
            'order_prefix' => ['required', 'string', 'max:10'],
            'order_next_number' => ['required', 'integer', 'min:1'],
        ]);

        $settings->fill([
            'min_order_value' => (int) $this->min_order_value,
            'order_prefix' => $this->order_prefix,
            'order_next_number' => (int) $this->order_next_number,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Checkout settings updated.', variant: 'success');
    }

    public function saveQuotations(QuotationSettings $settings): void
    {
        $this->validate([
            'default_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'quote_prefix' => ['required', 'string', 'max:10'],
            'quote_terms' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings->fill([
            'quotes_enabled' => $this->quotes_enabled,
            'default_validity_days' => (int) $this->default_validity_days,
            'quote_prefix' => $this->quote_prefix,
            'quote_terms' => $this->quote_terms,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Quotation settings updated.', variant: 'success');
    }

    public function saveDownloads(DownloadSettings $settings): void
    {
        $this->validate([
            'default_download_limit' => ['required', 'integer', 'min:0'],
            'default_expiry_days' => ['required', 'integer', 'min:0'],
        ]);

        $settings->fill([
            'default_download_limit' => (int) $this->default_download_limit,
            'default_expiry_days' => (int) $this->default_expiry_days,
            'secure_downloads' => $this->secure_downloads,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Download settings updated.', variant: 'success');
    }

    public function saveShipping(ShippingSettings $settings): void
    {
        $this->validate([
            'free_shipping_threshold' => ['required', 'integer', 'min:0'],
            'handling_fee' => ['required', 'integer', 'min:0'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
        ]);

        $settings->fill([
            'free_shipping_threshold' => (int) $this->free_shipping_threshold,
            'handling_fee' => (int) $this->handling_fee,
            'local_pickup_enabled' => $this->local_pickup_enabled,
            'pickup_address' => $this->pickup_address,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Shipping settings updated.', variant: 'success');
    }
}; ?>

<x-admin.settings-shell tab="app" :section="$section">

    {{-- Inventory --}}
    @if ($section === 'inventory')
        <flux:card>
            <flux:heading>Inventory</flux:heading>
            <flux:subheading>Global defaults for stock tracking.</flux:subheading>

            <form wire:submit="saveInventory" class="mt-6 space-y-5">
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Track stock by default on new products</flux:label>
                    <flux:switch wire:model="track_stock_by_default" />
                </div>
                <flux:input wire:model="low_stock_threshold" type="number" min="0" label="Low stock threshold"
                    description="Products at or below this quantity are flagged low stock." />
                <flux:select wire:model="out_of_stock_behavior" label="When a product is out of stock">
                    <flux:select.option value="show">Show it (marked out of stock)</flux:select.option>
                    <flux:select.option value="hide">Hide it from the storefront</flux:select.option>
                </flux:select>
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Allow backorders by default</flux:label>
                    <flux:switch wire:model="allow_backorders_by_default" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Reviews --}}
    @if ($section === 'reviews')
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Reviews</flux:heading>
                    <flux:subheading>Customer review behavior and moderation.</flux:subheading>
                </div>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('admin.reviews.index')" wire:navigate>
                    Manage reviews
                </flux:button>
            </div>

            <form wire:submit="saveReviews" class="mt-6 space-y-5">
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Enable product reviews</flux:label>
                    <flux:switch wire:model.live="reviews_enabled" />
                </div>
                @if ($reviews_enabled)
                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <flux:label>Only verified purchasers can review</flux:label>
                        <flux:switch wire:model="require_verified_purchase" />
                    </div>
                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <div>
                            <flux:label>Auto-approve new reviews</flux:label>
                            <flux:text size="sm" class="text-xs">Off means reviews are held for moderation.</flux:text>
                        </div>
                        <flux:switch wire:model="auto_approve" />
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Checkout & cart --}}
    @if ($section === 'checkout')
        <flux:card>
            <flux:heading>Checkout & cart</flux:heading>
            <flux:subheading>How customers complete an order.</flux:subheading>

            <form wire:submit="saveCheckout" class="mt-6 space-y-5">
                <flux:input wire:model="min_order_value" type="number" min="0" label="Minimum order value (KES)"
                    description="0 means no minimum." />
                <flux:separator />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="order_prefix" label="Order number prefix" placeholder="SO-" />
                    <flux:input wire:model="order_next_number" type="number" min="1" label="Next order number" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Quotations --}}
    @if ($section === 'quotations')
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Quotations</flux:heading>
                    <flux:subheading>B2B quote requests and validity.</flux:subheading>
                </div>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('admin.quotes.index')" wire:navigate>
                    Manage quotes
                </flux:button>
            </div>

            <form wire:submit="saveQuotations" class="mt-6 space-y-5">
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Enable quotation requests</flux:label>
                    <flux:switch wire:model.live="quotes_enabled" />
                </div>
                @if ($quotes_enabled)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="default_validity_days" type="number" min="1" max="365" label="Default validity (days)" />
                        <flux:input wire:model="quote_prefix" label="Quote number prefix" placeholder="QT-" />
                    </div>
                    <flux:textarea wire:model="quote_terms" label="Default quote terms" rows="4"
                        placeholder="Terms shown on every quotation…" />
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Downloads --}}
    @if ($section === 'downloads')
        <flux:card>
            <flux:heading>Downloads</flux:heading>
            <flux:subheading>Defaults for downloadable products.</flux:subheading>

            <form wire:submit="saveDownloads" class="mt-6 space-y-5">
                <flux:input wire:model="default_download_limit" type="number" min="0" label="Default download limit"
                    description="0 means unlimited downloads." />
                <flux:input wire:model="default_expiry_days" type="number" min="0" label="Default expiry (days)"
                    description="0 means the link never expires." />
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <div>
                        <flux:label>Secure downloads</flux:label>
                        <flux:text size="sm" class="text-xs">Serve files through signed, expiring links.</flux:text>
                    </div>
                    <flux:switch wire:model="secure_downloads" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Shipping & delivery --}}
    @if ($section === 'shipping')
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Shipping & delivery</flux:heading>
                    <flux:subheading>Global delivery defaults. Per-area rates live in Delivery Zones.</flux:subheading>
                </div>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('admin.delivery-zones')" wire:navigate>
                    Delivery zones
                </flux:button>
            </div>

            <form wire:submit="saveShipping" class="mt-6 space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="free_shipping_threshold" type="number" min="0" label="Free shipping threshold (KES)"
                        description="0 disables free shipping." />
                    <flux:input wire:model="handling_fee" type="number" min="0" label="Handling fee (KES)" />
                </div>
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Offer local pickup</flux:label>
                    <flux:switch wire:model.live="local_pickup_enabled" />
                </div>
                @if ($local_pickup_enabled)
                    <flux:textarea wire:model="pickup_address" label="Pickup address" rows="3"
                        placeholder="Where customers collect orders…" />
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

</x-admin.settings-shell>
