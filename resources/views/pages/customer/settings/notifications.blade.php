<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer-settings'), Title('Notification Preferences')] class extends Component {
    /**
     * Per-topic, per-channel preferences. Persisted as JSON on users.notification_preferences.
     *
     * @var array<string, array<string, bool>>
     */
    public array $prefs = [];

    /**
     * Newsletter subscription toggle
     */
    public bool $newsletter_subscribed = false;

    /**
     * The default preferences when the user has none stored.
     *
     * @return array<string, array<string, bool>>
     */
    public static function defaults(): array
    {
        return [
            'order_confirmation' => ['email' => true, 'sms' => false, 'push' => false],
            'order_updates' => ['email' => true, 'sms' => false, 'push' => false],
            'quote_received' => ['email' => true, 'sms' => false, 'push' => false],
            'quote_updates' => ['email' => true, 'sms' => false, 'push' => false],
            'special_offers' => ['email' => true, 'sms' => false, 'push' => false],
            'new_arrivals' => ['email' => false, 'sms' => false, 'push' => false],
            'review_reminders' => ['email' => true, 'sms' => false, 'push' => false],
            'security_alerts' => ['email' => true, 'sms' => true, 'push' => false],
            'password_changes' => ['email' => true, 'sms' => true, 'push' => false],
        ];
    }

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $stored = Auth::user()->notification_preferences ?? [];

        // Merge defaults so newly added topics default sensibly even on existing users.
        $this->prefs = array_replace_recursive(self::defaults(), $stored);

        // Load newsletter subscription
        $this->newsletter_subscribed = Auth::user()->newsletter_subscribed ?? false;
    }

    public function save(): void
    {
        Auth::user()->update([
            'notification_preferences' => $this->prefs,
            'newsletter_subscribed' => $this->newsletter_subscribed,
        ]);

        $this->dispatch('toast', message: __('Notification preferences updated'), type: 'success');
    }
}; ?>

<div>
    <x-customer.settings-card title="Notification" titleEm="Preferences">
        <x-slot:icon>
            <flux:icon.bell />
        </x-slot:icon>

        {{-- Channel headers --}}
        <div class="flex items-center justify-end px-5 py-2.5 border-b border-zinc-200 gap-5">
            <span class="text-[9px] font-extrabold tracking-widest uppercase text-on-surface-variant w-9 text-center">Email</span>
            <span class="text-[9px] font-extrabold tracking-widest uppercase text-on-surface-variant/40 w-9 text-center">SMS</span>
            <span class="text-[9px] font-extrabold tracking-widest uppercase text-on-surface-variant/40 w-9 text-center">Push</span>
        </div>

        {{-- Orders & Shipping --}}
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <flux:icon.shopping-bag class="w-3.5 h-3.5 text-primary shrink-0" />
            <span class="text-[11px] font-bold tracking-widest uppercase text-on-surface-variant">Orders & Shipping</span>
        </div>
        <x-customer.notification-row topic="order_confirmation" title="Order Confirmation"
            description="Receive confirmation when your order is placed" />
        <x-customer.notification-row topic="order_updates" title="Order Updates"
            description="Get notified when your order is processed, shipped, delivered, cancelled or refunded" />

        {{-- Quotations --}}
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <flux:icon.document-text class="w-3.5 h-3.5 text-primary shrink-0" />
            <span class="text-[11px] font-bold tracking-widest uppercase text-on-surface-variant">Quotations</span>
        </div>
        <x-customer.notification-row topic="quote_received" title="Quote Request Confirmed"
            description="Confirmation email when your quote request is received by our team" />
        <x-customer.notification-row topic="quote_updates" title="Quote Updates"
            description="Notifications when your quotation is priced, sent or about to expire" />

        {{-- Promotions --}}
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <flux:icon.tag class="w-3.5 h-3.5 text-primary shrink-0" />
            <span class="text-[11px] font-bold tracking-widest uppercase text-on-surface-variant">Promotions & Offers</span>
        </div>
        <x-customer.notification-row topic="special_offers" title="Special Offers"
            description="Exclusive deals, discounts and promotions" />
        <x-customer.notification-row topic="new_arrivals" title="New Arrivals"
            description="Be the first to know about new products" />

        {{-- Reviews --}}
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <flux:icon.star class="w-3.5 h-3.5 text-primary shrink-0" />
            <span class="text-[11px] font-bold tracking-widest uppercase text-on-surface-variant">Reviews & Feedback</span>
        </div>
        <x-customer.notification-row topic="review_reminders" title="Review Reminders"
            description="Reminders to review products you've purchased" />

        {{-- Account & Security --}}
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <flux:icon.shield-check class="w-3.5 h-3.5 text-primary shrink-0" />
            <span class="text-[11px] font-bold tracking-widest uppercase text-on-surface-variant">Account & Security</span>
        </div>
        <x-customer.notification-row topic="security_alerts" title="Security Alerts"
            description="Important updates about your account security" />
        <x-customer.notification-row topic="password_changes" title="Password Changes"
            description="Notifications when your password is changed" />

        {{-- Marketing & Newsletter --}}
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-zinc-200 bg-zinc-50/40">
            <flux:icon.envelope class="w-3.5 h-3.5 text-primary shrink-0" />
            <span class="text-[11px] font-bold tracking-widest uppercase text-on-surface-variant">Marketing Communications</span>
        </div>
        <div class="flex items-center justify-between px-5 py-3.5">
            <div class="flex-1">
                <div class="text-[13px] font-semibold text-on-surface mb-0.5">Newsletter Subscription</div>
                <div class="text-[12px] text-on-surface-variant">Receive updates about new products, promotions, and special
                    offers</div>
            </div>
            <button type="button" wire:click="$toggle('newsletter_subscribed')" @class([
                'relative w-9.5 h-5.5 rounded-full shrink-0 transition-colors cursor-pointer ml-4',
                'bg-primary' => $newsletter_subscribed,
                'bg-zinc-200' => !$newsletter_subscribed,
            ])>
                <div @class([
                    'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform',
                    'translate-x-4' => $newsletter_subscribed,
                ])></div>
            </button>
        </div>

        <x-slot:footer>
            <flux:button type="button" wire:click="save" variant="customer-primary" size="customer-lg">
                <span wire:loading.remove wire:target="save">Save Preferences</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </x-slot:footer>
    </x-customer.settings-card>
</div>
