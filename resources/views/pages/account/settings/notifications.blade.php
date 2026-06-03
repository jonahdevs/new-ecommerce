<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::settings')] #[Title('Notifications')] class extends Component
{
    /** @var array<string, array<string, bool>> */
    public array $preferences = [];

    protected static array $defaults = [
        'orders' => [
            'confirmed' => true,
            'shipped' => true,
            'delivered' => true,
            'cancelled' => true,
        ],
        'quotes' => [
            'received' => true,
            'ready' => true,
            'expiring' => true,
        ],
        'marketing' => [
            'product_news' => false,
            'catalogs' => false,
            'offers' => false,
        ],
        'account' => [
            'security_alerts' => true,
            'login_alerts' => true,
        ],
    ];

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');

        $saved = Auth::user()->notification_preferences ?? [];

        $this->preferences = array_replace_recursive(static::$defaults, $saved);
    }

    public function save(): void
    {
        Auth::user()->update(['notification_preferences' => $this->preferences]);

        Flux::toast(variant: 'success', text: __('Notification preferences saved.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::account.settings.layout
        :heading="__('Notifications')"
        :subheading="__('Choose which emails Sheffield sends you.')">

        <form wire:submit="save" class="mt-6 space-y-8">

            {{-- Orders --}}
            <flux:card class="space-y-5">
                <div>
                    <flux:heading size="sm">Order updates</flux:heading>
                    <flux:text size="sm" class="mt-0.5 text-ink-3">Emails about your active orders and deliveries.</flux:text>
                </div>
                <flux:separator />
                <flux:switch wire:model="preferences.orders.confirmed"
                             label="Order confirmed"
                             description="When we receive and confirm your order." />
                <flux:switch wire:model="preferences.orders.shipped"
                             label="Order shipped"
                             description="When your order leaves our warehouse." />
                <flux:switch wire:model="preferences.orders.delivered"
                             label="Order delivered"
                             description="When your order has been delivered." />
                <flux:switch wire:model="preferences.orders.cancelled"
                             label="Order cancelled"
                             description="When an order is cancelled for any reason." />
            </flux:card>

            {{-- Quotes --}}
            <flux:card class="space-y-5">
                <div>
                    <flux:heading size="sm">Quotes</flux:heading>
                    <flux:text size="sm" class="mt-0.5 text-ink-3">Updates on your quotation requests.</flux:text>
                </div>
                <flux:separator />
                <flux:switch wire:model="preferences.quotes.received"
                             label="Quote received"
                             description="When our team receives your request for quotation." />
                <flux:switch wire:model="preferences.quotes.ready"
                             label="Quote ready for review"
                             description="When a quote is prepared and awaiting your approval." />
                <flux:switch wire:model="preferences.quotes.expiring"
                             label="Quote expiring soon"
                             description="A reminder 3 days before a quote expires." />
            </flux:card>

            {{-- Marketing --}}
            <flux:card class="space-y-5">
                <div>
                    <flux:heading size="sm">Marketing</flux:heading>
                    <flux:text size="sm" class="mt-0.5 text-ink-3">Product news and promotional content from Sheffield.</flux:text>
                </div>
                <flux:separator />
                <flux:switch wire:model="preferences.marketing.product_news"
                             label="Product news"
                             description="New arrivals, launches and featured equipment." />
                <flux:switch wire:model="preferences.marketing.catalogs"
                             label="Seasonal catalogs"
                             description="Our curated PDF catalogs sent twice a year." />
                <flux:switch wire:model="preferences.marketing.offers"
                             label="Special offers"
                             description="Clearance deals and limited-time promotions." />
            </flux:card>

            {{-- Account --}}
            <flux:card class="space-y-5">
                <div>
                    <flux:heading size="sm">Account & security</flux:heading>
                    <flux:text size="sm" class="mt-0.5 text-ink-3">Important alerts about your account activity.</flux:text>
                </div>
                <flux:separator />
                <flux:switch wire:model="preferences.account.security_alerts"
                             label="Security alerts"
                             description="Password changes, 2FA updates and suspicious activity." />
                <flux:switch wire:model="preferences.account.login_alerts"
                             label="New device sign-in"
                             description="When your account is accessed from a new device." />
            </flux:card>

            <div class="flex items-center gap-4">
                <flux:button variant="customer-primary" size="customer" type="submit">
                    Save preferences
                </flux:button>
            </div>

        </form>

    </x-pages::account.settings.layout>
</section>
