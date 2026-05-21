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
    <x-pages::admin.settings.layout :heading="__('Inventory')" :subheading="__('Stock tracking and low stock threshold')">
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

                        <flux:input label="{{ __('Low stock threshold') }}" wire:model="form.low_stock_threshold"
                            type="number" min="1"
                            description="{{ __('Admin is alerted when a product\'s stock falls to or below this number') }}" />
                    @endif
                </div>
            </flux:card>

            <flux:callout icon="information-circle" color="blue">
                <flux:callout.heading>{{ __('Stock notifications') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Low stock and out-of-stock email alerts are configured under') }}
                    <flux:link href="{{ route('admin.settings.notifications') }}" wire:navigate>{{ __('Settings → Notifications') }}</flux:link>.
                </flux:callout.text>
            </flux:callout>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
