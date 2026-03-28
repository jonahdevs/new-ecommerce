<?php

use App\Livewire\Forms\Admin\Settings\CustomerNotificationSettingsForm;
use App\Settings\CustomerNotificationSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Customer Emails')] class extends Component {
    public CustomerNotificationSettingsForm $form;

    public function mount(CustomerNotificationSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(CustomerNotificationSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('Customer email settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save customer notification settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Customer emails')" :subheading="__('Transactional emails sent automatically to customers')">
        <form wire:submit="save" class="space-y-6">

            {{-- Order lifecycle --}}
            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Order lifecycle') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model="form.order_confirmation" label="{{ __('Order confirmation') }}"
                        description="{{ __('Sent immediately when a customer places an order') }}" />
                    <flux:checkbox wire:model="form.order_processing" label="{{ __('Order processing') }}"
                        description="{{ __('Sent when the order status moves to processing') }}" />
                    <flux:checkbox wire:model="form.order_shipped" label="{{ __('Order shipped') }}"
                        description="{{ __('Sent with tracking information when the order is dispatched') }}" />
                    <flux:checkbox wire:model="form.order_delivered" label="{{ __('Order delivered') }}"
                        description="{{ __('Sent when the order is marked as delivered') }}" />
                    <flux:checkbox wire:model="form.order_cancelled" label="{{ __('Order cancelled') }}"
                        description="{{ __('Sent when an order is cancelled') }}" />
                    <flux:checkbox wire:model="form.order_refunded" label="{{ __('Order refunded') }}"
                        description="{{ __('Sent when a refund is issued') }}" />
                </div>
            </flux:card>

            {{-- Engagement --}}
            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Engagement emails') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model.live="form.abandoned_cart" label="{{ __('Abandoned cart reminder') }}"
                        description="{{ __('Remind customers who left items in their cart without checking out') }}" />

                    @if ($form->abandoned_cart)
                        <flux:input label="{{ __('Send reminder after (hours)') }}"
                            wire:model="form.abandoned_cart_delay" type="number" min="1" max="72"
                            description="{{ __('Between 1 and 72 hours after the cart was abandoned') }}" />
                    @endif

                    <flux:separator />

                    <flux:checkbox wire:model.live="form.review_request" label="{{ __('Review request') }}"
                        description="{{ __('Ask customers to leave a review after their order is delivered') }}" />

                    @if ($form->review_request)
                        <flux:input label="{{ __('Send request after (days)') }}"
                            wire:model="form.review_request_delay" type="number" min="1" max="30"
                            description="{{ __('Between 1 and 30 days after delivery') }}" />
                    @endif
                </div>
            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
