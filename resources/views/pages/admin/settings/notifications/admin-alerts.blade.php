<?php

use App\Livewire\Forms\Admin\Settings\CustomerNotificationSettingsForm;
use App\Livewire\Forms\Admin\Settings\NotificationSettingsForm;
use App\Settings\CustomerNotificationSettings;
use App\Settings\NotificationSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notifications')] class extends Component {
    public NotificationSettingsForm $adminForm;
    public CustomerNotificationSettingsForm $customerForm;

    public function mount(NotificationSettings $settings, CustomerNotificationSettings $customerSettings): void
    {
        $this->adminForm->fromSettings($settings);
        $this->customerForm->fromSettings($customerSettings);
    }

    public function save(NotificationSettings $settings, CustomerNotificationSettings $customerSettings): void
    {
        try {
            $this->adminForm->save($settings);
            $this->customerForm->save($customerSettings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Notification settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save notification settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Notifications')" :subheading="__('Control which events trigger notifications and through which channels')">
        <form wire:submit="save" class="space-y-6">

            {{-- ── ADMIN & STAFF NOTIFICATIONS ── --}}
            <flux:card class="p-0">

                {{-- Header --}}
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-5 py-3 flex items-center gap-2">
                    <flux:icon.bell class="size-4 text-zinc-500" />
                    <flux:heading>{{ __('Admin & Staff Notifications') }}</flux:heading>
                </div>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end gap-5 px-5 py-2.5 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/40">
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-500 w-9 text-center">Email</span>
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-400 w-9 text-center">SMS</span>
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-400 w-9 text-center">Push</span>
                </div>

                {{-- Orders & Payments --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.shopping-bag class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Orders & Payments') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_new_order',
                    'title' => __('New order placed'),
                    'description' => __('Notify when a customer places an order'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_failed_payment',
                    'title' => __('Payment failed'),
                    'description' => __('Alert when a payment attempt fails at checkout'),
                ])

                {{-- Customers & Reviews --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.users class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Customers & Reviews') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_new_user',
                    'title' => __('New customer registered'),
                    'description' => __('Notify when a new customer account is created'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_new_review',
                    'title' => __('New review submitted'),
                    'description' => __('Notify when a customer review is pending moderation'),
                ])

                {{-- Inventory --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.archive-box class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Inventory') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_low_stock',
                    'title' => __('Low stock alert'),
                    'description' => __('Send an email alert when a product hits the low stock threshold'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_out_of_stock',
                    'title' => __('Out of stock alert'),
                    'description' => __('Send an email alert when a product reaches zero stock'),
                ])

                {{-- Quotations --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.document-text class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Quotations') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_new_quote',
                    'title' => __('New quote request'),
                    'description' => __('Notify when a customer requests a quotation'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_quote_accepted',
                    'title' => __('Quote accepted'),
                    'description' => __('Notify when a customer accepts a quotation'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'adminForm.notify_quote_rejected',
                    'title' => __('Quote rejected'),
                    'description' => __('Notify when a customer rejects a quotation'),
                    'last' => true,
                ])
            </flux:card>

            {{-- ── CUSTOMER NOTIFICATIONS ── --}}
            <flux:card class="p-0">

                {{-- Header --}}
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-5 py-3 flex items-center gap-2">
                    <flux:icon.envelope class="size-4 text-zinc-500" />
                    <flux:heading>{{ __('Customer Notifications') }}</flux:heading>
                </div>

                {{-- Channel headers --}}
                <div class="flex items-center justify-end gap-5 px-5 py-2.5 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/40">
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-500 w-9 text-center">Email</span>
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-400 w-9 text-center">SMS</span>
                    <span class="text-[9px] font-extrabold tracking-widest uppercase text-zinc-400 w-9 text-center">Push</span>
                </div>

                {{-- Order lifecycle --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.shopping-bag class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Order Lifecycle') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'customerForm.order_confirmation',
                    'title' => __('Order confirmation'),
                    'description' => __('Sent immediately when a customer places an order'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'customerForm.order_updates',
                    'title' => __('Order updates'),
                    'description' => __('Sent when the order status changes — processing, shipped, delivered, cancelled or refunded'),
                ])

                {{-- Engagement --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.star class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Engagement') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'customerForm.abandoned_cart',
                    'title' => __('Abandoned cart reminder'),
                    'description' => __('Remind customers who left items in their cart'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'customerForm.review_request',
                    'title' => __('Review request'),
                    'description' => __('Ask customers to leave a review after delivery'),
                ])

                {{-- Quotations --}}
                <div class="flex items-center gap-2 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/20">
                    <flux:icon.document-text class="size-3.5 text-primary shrink-0" />
                    <span class="text-[11px] font-bold tracking-widest uppercase text-zinc-500">{{ __('Quotations') }}</span>
                </div>

                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'customerForm.quote_sent',
                    'title' => __('Quote sent'),
                    'description' => __('Sent when admin sends a priced quotation to the customer'),
                ])
                @include('pages.admin.settings.notifications.partials.alert-row', [
                    'model' => 'customerForm.quote_expiring_reminder',
                    'title' => __('Quote expiring reminder'),
                    'description' => __('Remind customers before their quotation expires'),
                    'last' => true,
                ])
            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
