<?php

use App\Livewire\Forms\Admin\Settings\NotificationSettingsForm;
use App\Settings\NotificationSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Alerts')] class extends Component {
    public NotificationSettingsForm $form;

    public function mount(NotificationSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(NotificationSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('Admin alert settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save notification settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Admin alerts')" :subheading="__('Choose which events trigger an admin email notification')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Alert triggers') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model="form.notify_new_order" label="{{ __('New order placed') }}"
                        description="{{ __('Notify when a customer places an order') }}" />

                    <flux:checkbox wire:model="form.notify_failed_payment" label="{{ __('Payment failed') }}"
                        description="{{ __('Alert when a payment attempt fails at checkout') }}" />

                    <flux:checkbox wire:model="form.notify_low_stock" label="{{ __('Low stock alert') }}"
                        description="{{ __('Notify when a product hits the low stock threshold') }}" />

                    <flux:checkbox wire:model="form.notify_new_review" label="{{ __('New review submitted') }}"
                        description="{{ __('Notify when a customer review is pending moderation') }}" />

                    <flux:checkbox wire:model="form.notify_new_user" label="{{ __('New customer registered') }}"
                        description="{{ __('Notify when a new customer account is created') }}" />
                </div>
            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Notification recipient') }}</flux:heading>
                </div>

                <div class="p-5">
                    <flux:input label="{{ __('Admin notification email') }}" wire:model="form.admin_notification_email"
                        type="email" placeholder="{{ __('Leave blank to use the store email') }}"
                        description="{{ __('All admin alerts will be sent to this address. Defaults to your store email if left empty.') }}" />
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
