<?php

use App\Livewire\Forms\Admin\Settings\InventorySettingsForm;
use App\Settings\InventorySettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Inventory')] class extends Component {
    public InventorySettingsForm $form;

    public function mount(InventorySettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(InventorySettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Inventory settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save inventory settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Inventory')" :subheading="__('Stock tracking, thresholds and backorder rules')">
        <form wire:submit="save" class="space-y-6">

            {{-- Stock Management --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Stock management') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model.live="form.inventory_tracking_enabled"
                        label="{{ __('Enable inventory tracking') }}"
                        description="{{ __('Track stock levels per product and variant') }}" />

                    @if ($form->inventory_tracking_enabled)
                        <flux:separator />

                        <flux:checkbox wire:model.live="form.backorders_allowed" label="{{ __('Allow backorders') }}"
                            description="{{ __('Let customers order items that are currently out of stock') }}" />

                        @if ($form->backorders_allowed)
                            <flux:input label="{{ __('Backorder message') }}" wire:model="form.backorders_message"
                                placeholder="{{ __('Available on backorder') }}"
                                description="{{ __('Shown to customers on the product page when stock is zero') }}" />
                        @endif
                    @endif
                </div>
            </flux:card>

            {{-- Thresholds & Behaviour --}}
            @if ($form->inventory_tracking_enabled)
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Thresholds & behaviour') }}</flux:heading>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Low stock threshold') }}" wire:model="form.low_stock_threshold"
                                type="number" min="1"
                                description="{{ __('Notify admin when stock falls to or below this number') }}" />

                            <flux:select label="{{ __('Out of stock behaviour') }}"
                                wire:model="form.out_of_stock_behaviour">
                                <flux:select.option value="show_with_notice">
                                    {{ __('Show with out-of-stock notice') }}
                                </flux:select.option>
                                <flux:select.option value="show">
                                    {{ __('Show without notice') }}
                                </flux:select.option>
                                <flux:select.option value="hide">
                                    {{ __('Hide product') }}
                                </flux:select.option>
                            </flux:select>
                        </div>

                        <flux:separator />

                        <flux:checkbox wire:model="form.notify_admin_low_stock"
                            label="{{ __('Notify admin on low stock') }}"
                            description="{{ __('Send an email alert when a product hits the low stock threshold') }}" />

                        <flux:checkbox wire:model="form.notify_admin_out_of_stock"
                            label="{{ __('Notify admin when out of stock') }}"
                            description="{{ __('Send an email alert when a product reaches zero stock') }}" />
                    </div>
                </flux:card>
            @endif

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
