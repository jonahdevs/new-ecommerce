<?php

use App\Settings\CheckoutSettings;
use App\Settings\InventorySettings;
use App\Settings\NotificationSettings;
use App\Settings\QuotationSettings;
use App\Settings\ReviewSettings;
use App\Settings\ShippingSettings;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('App settings — Admin')] class extends Component {
    #[Url]
    public string $section = 'inventory';

    // ==================================================
    // INVENTORY
    // ==================================================
    public bool $track_stock_by_default = true;

    public int $low_stock_threshold = 5;

    public string $out_of_stock_behavior = 'show';

    public bool $allow_backorders_by_default = false;

    // ==================================================
    // REVIEWS
    // ==================================================
    public bool $reviews_enabled = true;

    public bool $require_verified_purchase = true;

    public bool $auto_approve = false;

    // ==================================================
    // CHECKOUT
    // ==================================================

    public int $min_order_value = 0;

    public string $order_prefix = 'SHF-';

    // ==================================================
    // QUOTATIONS
    // ==================================================
    public bool $quotes_enabled = true;

    public int $default_validity_days = 30;

    public string $quote_prefix = 'RFQ-';

    public string $quote_terms = '';

    // ==================================================
    // SHIPPING
    // ==================================================
    public bool $local_pickup_enabled = true;

    public string $pickup_address = '';

    // ==================================================
    // NOTIFICATION EMAIL ROUTING
    // ==================================================
    public string $staff_email_routing = 'individual';

    public string $staff_central_email = '';

    // ==================================================
    // NOTIFICATION CHANNELS
    // ==================================================
    public bool $email_channel_enabled = true;
    public bool $inapp_channel_enabled = true;
    public bool $whatsapp_channel_enabled = false;
    public string $whatsapp_api_token = '';
    public string $whatsapp_phone_number_id = '';
    public string $whatsapp_business_account_id = '';

    // ==================================================
    // CUSTOMER NOTIFICATIONS — PER CHANNEL
    // ==================================================
    public bool $customer_order_confirmation_email = true;
    public bool $customer_order_confirmation_inapp = true;
    public bool $customer_order_confirmation_whatsapp = false;
    public bool $customer_order_updates_email = true;
    public bool $customer_order_updates_inapp = true;
    public bool $customer_order_updates_whatsapp = false;
    public bool $customer_quote_received_email = true;
    public bool $customer_quote_received_inapp = true;
    public bool $customer_quote_received_whatsapp = false;
    public bool $customer_quote_updates_email = true;
    public bool $customer_quote_updates_inapp = true;
    public bool $customer_quote_updates_whatsapp = false;
    public bool $customer_marketing_email = true;
    public bool $customer_marketing_inapp = false;
    public bool $customer_marketing_whatsapp = false;
    public bool $customer_account_security_email = true;
    public bool $customer_account_security_inapp = true;
    public bool $customer_account_security_whatsapp = false;

    // ==================================================
    // STAFF NOTIFICATIONS — PER CHANNEL
    // ==================================================
    public bool $staff_new_order_email = true;
    public bool $staff_new_order_inapp = true;
    public bool $staff_new_order_whatsapp = false;
    public bool $staff_new_review_email = true;
    public bool $staff_new_review_inapp = true;
    public bool $staff_new_review_whatsapp = false;
    public bool $staff_low_stock_email = true;
    public bool $staff_low_stock_inapp = true;
    public bool $staff_low_stock_whatsapp = false;
    public bool $staff_new_quote_email = true;
    public bool $staff_new_quote_inapp = true;
    public bool $staff_new_quote_whatsapp = false;
    public bool $staff_quote_decision_email = true;
    public bool $staff_quote_decision_inapp = true;
    public bool $staff_quote_decision_whatsapp = false;

    public function mount(InventorySettings $inventory, ReviewSettings $reviews, CheckoutSettings $checkout, QuotationSettings $quotations, ShippingSettings $shipping, NotificationSettings $notifications): void
    {
        $this->track_stock_by_default = $inventory->track_stock_by_default;
        $this->low_stock_threshold = $inventory->low_stock_threshold;
        $this->out_of_stock_behavior = $inventory->out_of_stock_behavior;
        $this->allow_backorders_by_default = $inventory->allow_backorders_by_default;

        $this->reviews_enabled = $reviews->reviews_enabled;
        $this->require_verified_purchase = $reviews->require_verified_purchase;
        $this->auto_approve = $reviews->auto_approve;

        $this->min_order_value = $checkout->min_order_value;
        $this->order_prefix = $checkout->order_prefix;

        $this->quotes_enabled = $quotations->quotes_enabled;
        $this->default_validity_days = $quotations->default_validity_days;
        $this->quote_prefix = $quotations->quote_prefix;
        $this->quote_terms = $quotations->quote_terms;

        $this->local_pickup_enabled = $shipping->local_pickup_enabled;
        $this->pickup_address = $shipping->pickup_address;

        $this->staff_email_routing = $notifications->staff_email_routing;
        $this->staff_central_email = $notifications->staff_central_email ?? '';

        $this->email_channel_enabled = $notifications->email_channel_enabled;
        $this->inapp_channel_enabled = $notifications->inapp_channel_enabled;
        $this->whatsapp_channel_enabled = $notifications->whatsapp_channel_enabled;
        $this->whatsapp_api_token = $notifications->whatsapp_api_token ?? '';
        $this->whatsapp_phone_number_id = $notifications->whatsapp_phone_number_id ?? '';
        $this->whatsapp_business_account_id = $notifications->whatsapp_business_account_id ?? '';

        $this->customer_order_confirmation_email = $notifications->customer_order_confirmation_email;
        $this->customer_order_confirmation_inapp = $notifications->customer_order_confirmation_inapp;
        $this->customer_order_confirmation_whatsapp = $notifications->customer_order_confirmation_whatsapp;
        $this->customer_order_updates_email = $notifications->customer_order_updates_email;
        $this->customer_order_updates_inapp = $notifications->customer_order_updates_inapp;
        $this->customer_order_updates_whatsapp = $notifications->customer_order_updates_whatsapp;
        $this->customer_quote_received_email = $notifications->customer_quote_received_email;
        $this->customer_quote_received_inapp = $notifications->customer_quote_received_inapp;
        $this->customer_quote_received_whatsapp = $notifications->customer_quote_received_whatsapp;
        $this->customer_quote_updates_email = $notifications->customer_quote_updates_email;
        $this->customer_quote_updates_inapp = $notifications->customer_quote_updates_inapp;
        $this->customer_quote_updates_whatsapp = $notifications->customer_quote_updates_whatsapp;
        $this->customer_marketing_email = $notifications->customer_marketing_email;
        $this->customer_marketing_inapp = $notifications->customer_marketing_inapp;
        $this->customer_marketing_whatsapp = $notifications->customer_marketing_whatsapp;
        $this->customer_account_security_email = $notifications->customer_account_security_email;
        $this->customer_account_security_inapp = $notifications->customer_account_security_inapp;
        $this->customer_account_security_whatsapp = $notifications->customer_account_security_whatsapp;
        $this->staff_new_order_email = $notifications->staff_new_order_email;
        $this->staff_new_order_inapp = $notifications->staff_new_order_inapp;
        $this->staff_new_order_whatsapp = $notifications->staff_new_order_whatsapp;
        $this->staff_new_review_email = $notifications->staff_new_review_email;
        $this->staff_new_review_inapp = $notifications->staff_new_review_inapp;
        $this->staff_new_review_whatsapp = $notifications->staff_new_review_whatsapp;
        $this->staff_low_stock_email = $notifications->staff_low_stock_email;
        $this->staff_low_stock_inapp = $notifications->staff_low_stock_inapp;
        $this->staff_low_stock_whatsapp = $notifications->staff_low_stock_whatsapp;
        $this->staff_new_quote_email = $notifications->staff_new_quote_email;
        $this->staff_new_quote_inapp = $notifications->staff_new_quote_inapp;
        $this->staff_new_quote_whatsapp = $notifications->staff_new_quote_whatsapp;
        $this->staff_quote_decision_email = $notifications->staff_quote_decision_email;
        $this->staff_quote_decision_inapp = $notifications->staff_quote_decision_inapp;
        $this->staff_quote_decision_whatsapp = $notifications->staff_quote_decision_whatsapp;
    }

    public function saveInventory(InventorySettings $settings): void
    {
        $this->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'out_of_stock_behavior' => ['required', 'in:show,hide'],
        ]);

        $settings
            ->fill([
                'track_stock_by_default' => $this->track_stock_by_default,
                'low_stock_threshold' => (int) $this->low_stock_threshold,
                'out_of_stock_behavior' => $this->out_of_stock_behavior,
                'allow_backorders_by_default' => $this->allow_backorders_by_default,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Inventory settings updated.', variant: 'success');
    }

    public function saveReviews(ReviewSettings $settings): void
    {
        $settings
            ->fill([
                'reviews_enabled' => $this->reviews_enabled,
                'require_verified_purchase' => $this->require_verified_purchase,
                'auto_approve' => $this->auto_approve,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Review settings updated.', variant: 'success');
    }

    public function saveCheckout(CheckoutSettings $settings): void
    {
        $this->validate([
            'min_order_value' => ['required', 'integer', 'min:0'],
            'order_prefix' => ['required', 'string', 'max:10'],
        ]);

        $settings
            ->fill([
                'min_order_value' => (int) $this->min_order_value,
                'order_prefix' => $this->order_prefix,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Checkout settings updated.', variant: 'success');
    }

    public function saveQuotations(QuotationSettings $settings): void
    {
        $this->validate([
            'default_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'quote_prefix' => ['required', 'string', 'max:10'],
            'quote_terms' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings
            ->fill([
                'quotes_enabled' => $this->quotes_enabled,
                'default_validity_days' => (int) $this->default_validity_days,
                'quote_prefix' => $this->quote_prefix,
                'quote_terms' => $this->quote_terms,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Quotation settings updated.', variant: 'success');
    }

    public function saveNotifications(NotificationSettings $settings): void
    {
        $this->validate([
            'staff_email_routing'  => ['required', 'in:individual,central'],
            'staff_central_email'  => $this->staff_email_routing === 'central'
                ? ['required', 'email', 'max:255']
                : ['nullable', 'email', 'max:255'],
        ]);

        $settings
            ->fill([
                'staff_email_routing'                 => $this->staff_email_routing,
                'staff_central_email'                 => filled($this->staff_central_email) ? $this->staff_central_email : null,
                'email_channel_enabled'               => $this->email_channel_enabled,
                'inapp_channel_enabled'               => $this->inapp_channel_enabled,
                'whatsapp_channel_enabled'            => $this->whatsapp_channel_enabled,
                'whatsapp_api_token'                  => $this->whatsapp_api_token ?: null,
                'whatsapp_phone_number_id'            => $this->whatsapp_phone_number_id ?: null,
                'whatsapp_business_account_id'        => $this->whatsapp_business_account_id ?: null,
                'customer_order_confirmation_email'    => $this->customer_order_confirmation_email,
                'customer_order_confirmation_inapp'    => $this->customer_order_confirmation_inapp,
                'customer_order_confirmation_whatsapp' => $this->customer_order_confirmation_whatsapp,
                'customer_order_updates_email'         => $this->customer_order_updates_email,
                'customer_order_updates_inapp'         => $this->customer_order_updates_inapp,
                'customer_order_updates_whatsapp'      => $this->customer_order_updates_whatsapp,
                'customer_quote_received_email'        => $this->customer_quote_received_email,
                'customer_quote_received_inapp'        => $this->customer_quote_received_inapp,
                'customer_quote_received_whatsapp'     => $this->customer_quote_received_whatsapp,
                'customer_quote_updates_email'         => $this->customer_quote_updates_email,
                'customer_quote_updates_inapp'         => $this->customer_quote_updates_inapp,
                'customer_quote_updates_whatsapp'      => $this->customer_quote_updates_whatsapp,
                'customer_marketing_email'             => $this->customer_marketing_email,
                'customer_marketing_inapp'             => $this->customer_marketing_inapp,
                'customer_marketing_whatsapp'          => $this->customer_marketing_whatsapp,
                'customer_account_security_email'      => $this->customer_account_security_email,
                'customer_account_security_inapp'      => $this->customer_account_security_inapp,
                'customer_account_security_whatsapp'   => $this->customer_account_security_whatsapp,
                'staff_new_order_email' => $this->staff_new_order_email,
                'staff_new_order_inapp' => $this->staff_new_order_inapp,
                'staff_new_order_whatsapp' => $this->staff_new_order_whatsapp,
                'staff_new_review_email' => $this->staff_new_review_email,
                'staff_new_review_inapp' => $this->staff_new_review_inapp,
                'staff_new_review_whatsapp' => $this->staff_new_review_whatsapp,
                'staff_low_stock_email' => $this->staff_low_stock_email,
                'staff_low_stock_inapp' => $this->staff_low_stock_inapp,
                'staff_low_stock_whatsapp' => $this->staff_low_stock_whatsapp,
                'staff_new_quote_email' => $this->staff_new_quote_email,
                'staff_new_quote_inapp' => $this->staff_new_quote_inapp,
                'staff_new_quote_whatsapp' => $this->staff_new_quote_whatsapp,
                'staff_quote_decision_email' => $this->staff_quote_decision_email,
                'staff_quote_decision_inapp' => $this->staff_quote_decision_inapp,
                'staff_quote_decision_whatsapp' => $this->staff_quote_decision_whatsapp,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Notification settings updated.', variant: 'success');
    }

    public function saveShipping(ShippingSettings $settings): void
    {
        $this->validate([
            'pickup_address' => ['nullable', 'string', 'max:500'],
        ]);

        $settings
            ->fill([
                'local_pickup_enabled' => $this->local_pickup_enabled,
                'pickup_address' => $this->pickup_address,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Shipping settings updated.', variant: 'success');
    }

}; ?>

<x-admin.settings-shell tab="app" :section="$section">

    {{-- Inventory --}}
    @if ($section === 'inventory')
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Inventory</flux:heading>
            </div>

            <form wire:submit="saveInventory" class="space-y-5 p-6">
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
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Reviews</flux:heading>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.reviews.index')" wire:navigate>
                    Manage reviews
                </flux:button>
            </div>

            <form wire:submit="saveReviews" class="space-y-5 p-6">
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
                            <flux:text size="sm" class="text-xs">Off means reviews are held for moderation.
                            </flux:text>
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
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Checkout & cart</flux:heading>
            </div>

            <form wire:submit="saveCheckout" class="space-y-5 p-6">
                <flux:input wire:model="min_order_value" type="number" min="0" label="Minimum order value (KES)"
                    description="0 means no minimum." />
                <flux:separator />
                <flux:input wire:model="order_prefix" label="Order number prefix" placeholder="SHF-"
                    description="Order numbers are formatted {prefix}{year}-{sequence}." />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Quotations --}}
    @if ($section === 'quotations')
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Quotations</flux:heading>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.quotes.index')" wire:navigate>
                    Manage quotes
                </flux:button>
            </div>

            <form wire:submit="saveQuotations" class="space-y-5 p-6">
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Enable quotation requests</flux:label>
                    <flux:switch wire:model.live="quotes_enabled" />
                </div>
                @if ($quotes_enabled)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="default_validity_days" type="number" min="1" max="365"
                            label="Default validity (days)" />
                        <flux:input wire:model="quote_prefix" label="Quote number prefix" placeholder="RFQ-" />
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

    {{-- Shipping & delivery --}}
    @if ($section === 'shipping')
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Shipping & delivery</flux:heading>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.delivery-zones')" wire:navigate>
                    Delivery zones
                </flux:button>
            </div>

            <form wire:submit="saveShipping" class="space-y-5 p-6">
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

    {{-- Notifications (global master switches) --}}
    @if ($section === 'notifications')
        <form wire:submit="saveNotifications" class="space-y-6">

            {{-- Channels --}}
            <div class="grid grid-cols-3 gap-4">

                {{-- Email --}}
                <flux:card class="flex flex-col gap-0 p-0">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <flux:icon.envelope variant="micro" class="size-4 shrink-0 text-zinc-400" />
                            <p class="text-sm font-medium dark:text-white">Email</p>
                        </div>
                        <flux:switch wire:model="email_channel_enabled" />
                    </div>
                    <p class="border-b border-zinc-100 px-4 pb-3 text-xs text-zinc-400 dark:border-zinc-700">
                        Sends transactional and marketing emails via your configured mail driver.
                    </p>
                    <div class="flex items-center justify-end px-4 py-2">
                        <flux:button size="xs" variant="ghost" icon="cog-6-tooth" disabled tooltip="No configuration required" />
                    </div>
                </flux:card>

                {{-- In-app --}}
                <flux:card class="flex flex-col gap-0 p-0">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <flux:icon.bell variant="micro" class="size-4 shrink-0 text-zinc-400" />
                            <p class="text-sm font-medium dark:text-white">In-app</p>
                        </div>
                        <flux:switch wire:model="inapp_channel_enabled" />
                    </div>
                    <p class="border-b border-zinc-100 px-4 pb-3 text-xs text-zinc-400 dark:border-zinc-700">
                        Shows notifications inside the admin dashboard and customer account area.
                    </p>
                    <div class="flex items-center justify-end px-4 py-2">
                        <flux:button size="xs" variant="ghost" icon="cog-6-tooth" disabled tooltip="No configuration required" />
                    </div>
                </flux:card>

                {{-- WhatsApp --}}
                <flux:card class="flex flex-col gap-0 p-0">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <flux:icon.chat-bubble-left-ellipsis variant="micro" class="size-4 shrink-0 text-zinc-400" />
                            <p class="text-sm font-medium dark:text-white">WhatsApp</p>
                        </div>
                        <flux:switch wire:model="whatsapp_channel_enabled" />
                    </div>
                    <p class="border-b border-zinc-100 px-4 pb-3 text-xs text-zinc-400 dark:border-zinc-700">
                        Sends messages via WhatsApp Business API. Requires API credentials to activate.
                    </p>
                    <div class="flex items-center justify-end px-4 py-2">
                        <flux:modal.trigger name="whatsapp-config">
                            <flux:button size="xs" variant="ghost" icon="cog-6-tooth" tooltip="Configure WhatsApp" />
                        </flux:modal.trigger>
                    </div>
                </flux:card>
            </div>

            {{-- WhatsApp config modal --}}
            <flux:modal name="whatsapp-config" class="w-full max-w-md">
                <flux:heading>WhatsApp Configuration</flux:heading>
                <flux:subheading class="mt-1">Enter your WhatsApp Business API credentials. These are available from your Meta Developer dashboard.</flux:subheading>

                <div class="mt-6 space-y-4">
                    <flux:input wire:model="whatsapp_business_account_id"
                        label="Business Account ID"
                        placeholder="123456789012345" />

                    <flux:input wire:model="whatsapp_phone_number_id"
                        label="Phone Number ID"
                        placeholder="123456789012345" />

                    <flux:input wire:model="whatsapp_api_token"
                        label="API Token"
                        type="password"
                        placeholder="••••••••••••••••" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Save credentials</flux:button>
                </div>
            </flux:modal>

            {{-- Customer notifications --}}
            <flux:card class="p-0">

                {{-- Title --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 px-5 py-3 dark:border-zinc-600">
                    <flux:icon.users class="size-4 text-zinc-500" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Customer notifications</flux:heading>
                </div>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end border-b border-zinc-200 bg-zinc-50 px-5 py-2.5 dark:border-zinc-600 dark:bg-zinc-800/40">
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500">Email</span>
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500">In-app</span>
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-400">WhatsApp</span>
                </div>

                @php
                    $customerGroups = [
                        [
                            'label' => 'Orders & Shipping',
                            'icon'  => 'shopping-bag',
                            'rows'  => [
                                ['key' => 'customer_order_confirmation', 'label' => 'Order Confirmation', 'desc' => 'Sent when a customer places an order and payment is received.'],
                                ['key' => 'customer_order_updates',      'label' => 'Order Updates',      'desc' => 'Covers shipped, delivered, cancelled and refunded status changes.'],
                            ],
                        ],
                        [
                            'label' => 'Quotations',
                            'icon'  => 'document-text',
                            'rows'  => [
                                ['key' => 'customer_quote_received', 'label' => 'Quote Received', 'desc' => 'Acknowledgement when a customer submits a quote request.'],
                                ['key' => 'customer_quote_updates',  'label' => 'Quote Updates',  'desc' => 'Covers when a quote is priced, sent, and about to expire.'],
                            ],
                        ],
                        [
                            'label' => 'Marketing & Account',
                            'icon'  => 'megaphone',
                            'rows'  => [
                                ['key' => 'customer_marketing',        'label' => 'Marketing Emails',  'desc' => 'Product news, catalogs and special offers.'],
                                ['key' => 'customer_account_security', 'label' => 'Account & Security','desc' => 'Password changes, 2FA updates, new device sign-ins.'],
                            ],
                        ],
                    ];
                @endphp

                @foreach ($customerGroups as $group)
                    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-600 dark:bg-zinc-800/20">
                        <flux:icon :icon="$group['icon']" class="size-3.5 shrink-0 text-brand-500" />
                        <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">{{ $group['label'] }}</span>
                    </div>
                    @foreach ($group['rows'] as $row)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5
                            @if (!$loop->last || !$loop->parent->last) border-b border-zinc-200 dark:border-zinc-700 @endif">
                            <div class="flex-1">
                                <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</div>
                                <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $row['desc'] }}</div>
                            </div>
                            <div class="flex shrink-0 items-center">
                                <div class="flex w-16 justify-center"><flux:switch wire:model="{{ $row['key'] }}_email" /></div>
                                <div class="flex w-16 justify-center"><flux:switch wire:model="{{ $row['key'] }}_inapp" /></div>
                                <div class="flex w-16 justify-center" :class="! whatsapp_channel_enabled && 'opacity-40'"><flux:switch wire:model="{{ $row['key'] }}_whatsapp" :disabled="! $whatsapp_channel_enabled" /></div>
                            </div>
                        </div>
                    @endforeach
                @endforeach

            </flux:card>

            {{-- Staff alerts --}}
            <flux:card class="p-0">

                {{-- Title --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 px-5 py-3 dark:border-zinc-600">
                    <flux:icon.bell class="size-4 text-zinc-500" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Admin & Staff notifications</flux:heading>
                </div>

                {{-- Email routing row --}}
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">Email routing</div>
                        <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                            @if ($staff_email_routing === 'central' && filled($staff_central_email))
                                Sending to central inbox: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $staff_central_email }}</span>
                            @elseif ($staff_email_routing === 'central')
                                Central inbox selected — configure an email address.
                            @else
                                Each qualifying staff member receives their own copy.
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <flux:select wire:model.live="staff_email_routing" size="sm" class="w-36!">
                            <flux:select.option value="individual">Individual</flux:select.option>
                            <flux:select.option value="central">Central inbox</flux:select.option>
                        </flux:select>
                        @if ($staff_email_routing === 'central')
                            <flux:modal.trigger name="central-email-config">
                                <flux:button size="xs" variant="ghost" icon="cog-6-tooth"
                                    tooltip="Configure central inbox"
                                    :class="!filled($staff_central_email) ? 'text-amber-500!' : ''" />
                            </flux:modal.trigger>
                        @endif
                    </div>
                </div>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end border-b border-zinc-200 bg-zinc-50 px-5 py-2.5 dark:border-zinc-600 dark:bg-zinc-800/40">
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500">Email</span>
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-500">In-app</span>
                    <span class="w-16 shrink-0 whitespace-nowrap text-center text-[9px] font-extrabold uppercase tracking-widest text-zinc-400">WhatsApp</span>
                </div>

                {{-- Orders & Payments --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-600 dark:bg-zinc-800/20">
                    <flux:icon.shopping-bag class="size-3.5 shrink-0 text-brand-500" />
                    <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">Orders & Payments</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">New order placed</div>
                        <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">Notify when a customer places an order.</div>
                    </div>
                    <div class="flex shrink-0 items-center">
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_new_order_email" /></div>
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_new_order_inapp" /></div>
                        <div class="flex w-16 justify-center" :class="! whatsapp_channel_enabled && 'opacity-40'"><flux:switch wire:model="staff_new_order_whatsapp" :disabled="! $whatsapp_channel_enabled" /></div>
                    </div>
                </div>

                {{-- Customers & Reviews --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-600 dark:bg-zinc-800/20">
                    <flux:icon.users class="size-3.5 shrink-0 text-brand-500" />
                    <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">Customers & Reviews</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">New review submitted</div>
                        <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">Notify when a customer review is pending moderation.</div>
                    </div>
                    <div class="flex shrink-0 items-center">
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_new_review_email" /></div>
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_new_review_inapp" /></div>
                        <div class="flex w-16 justify-center" :class="! whatsapp_channel_enabled && 'opacity-40'"><flux:switch wire:model="staff_new_review_whatsapp" :disabled="! $whatsapp_channel_enabled" /></div>
                    </div>
                </div>

                {{-- Inventory --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-600 dark:bg-zinc-800/20">
                    <flux:icon.archive-box class="size-3.5 shrink-0 text-brand-500" />
                    <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">Inventory</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">Low stock alert</div>
                        <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">Send an alert when a product hits the low stock threshold.</div>
                    </div>
                    <div class="flex shrink-0 items-center">
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_low_stock_email" /></div>
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_low_stock_inapp" /></div>
                        <div class="flex w-16 justify-center" :class="! whatsapp_channel_enabled && 'opacity-40'"><flux:switch wire:model="staff_low_stock_whatsapp" :disabled="! $whatsapp_channel_enabled" /></div>
                    </div>
                </div>

                {{-- Quotations --}}
                <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50/60 px-5 py-3 dark:border-zinc-600 dark:bg-zinc-800/20">
                    <flux:icon.document-text class="size-3.5 shrink-0 text-brand-500" />
                    <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">Quotations</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">New quote request</div>
                        <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">Notify when a customer requests a quotation.</div>
                    </div>
                    <div class="flex shrink-0 items-center">
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_new_quote_email" /></div>
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_new_quote_inapp" /></div>
                        <div class="flex w-16 justify-center" :class="! whatsapp_channel_enabled && 'opacity-40'"><flux:switch wire:model="staff_new_quote_whatsapp" :disabled="! $whatsapp_channel_enabled" /></div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                    <div class="flex-1">
                        <div class="mb-0.5 text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">Quote accepted / declined</div>
                        <div class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">Notify when a customer responds to a prepared quotation.</div>
                    </div>
                    <div class="flex shrink-0 items-center">
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_quote_decision_email" /></div>
                        <div class="flex w-16 justify-center"><flux:switch wire:model="staff_quote_decision_inapp" /></div>
                        <div class="flex w-16 justify-center" :class="! whatsapp_channel_enabled && 'opacity-40'"><flux:switch wire:model="staff_quote_decision_whatsapp" :disabled="! $whatsapp_channel_enabled" /></div>
                    </div>
                </div>

            </flux:card>

            {{-- Central inbox config modal --}}
            <flux:modal name="central-email-config" class="w-full max-w-md">
                <flux:heading>Central inbox</flux:heading>
                <flux:subheading class="mt-1">All staff notification emails will be sent to this address instead of individual staff members.</flux:subheading>

                <div class="mt-6">
                    <flux:input wire:model="staff_central_email" type="email"
                        label="Email address"
                        placeholder="notifications@yourcompany.com"
                        description="New orders, quote requests, low-stock alerts and more will all arrive here." />
                    @error('staff_central_email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:modal.close>
                        <flux:button variant="primary" type="button">Done</flux:button>
                    </flux:modal.close>
                </div>
            </flux:modal>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    @endif

</x-admin.settings-shell>
