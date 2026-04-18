<?php

use App\Livewire\Forms\Admin\Settings\OrderSettingsForm;
use App\Settings\OrderSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Orders')] class extends Component {
    public OrderSettingsForm $form;

    public function mount(OrderSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(OrderSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Order settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save order settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Orders')" :subheading="__('Order flow, checkout behaviour and cancellation rules')">
        <form wire:submit="save" class="space-y-6">

            {{-- Order Configuration --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Order configuration') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('Order ID prefix') }}" wire:model="form.order_id_prefix"
                            placeholder="ORD-" description="{{ __('e.g. ORD-0001, INV-0001') }}" />
                        <flux:input label="{{ __('Minimum order amount') }}" wire:model="form.minimum_order_amount"
                            type="number" min="0" step="0.01" placeholder="{{ __('0 — no minimum') }}" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Default order status') }}" wire:model="form.default_order_status">
                            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                            <flux:select.option value="processing">{{ __('Processing') }}</flux:select.option>
                            <flux:select.option value="on-hold">{{ __('On hold') }}</flux:select.option>
                        </flux:select>
                    </div>
                </div>
            </flux:card>

            {{-- Checkout & Cancellation --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Checkout & cancellation') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model.live="form.auto_cancel_unpaid"
                        label="{{ __('Auto-cancel unpaid orders') }}"
                        description="{{ __('Automatically cancel orders that have not been paid within the set window') }}" />

                    @if ($form->auto_cancel_unpaid)
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Auto-cancel after (hours)') }}"
                                wire:model="form.auto_cancel_hours" type="number" min="1" max="720"
                                description="{{ __('Between 1 and 720 hours (30 days)') }}" />
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Invoice Purchase Note --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Invoice') }}</flux:heading>
                </div>

                <div class="p-5">
                    <flux:textarea wire:model="form.purchase_note" rows="3"
                        label="{{ __('Purchase note') }}"
                        description="{{ __('This note will be printed on all invoices.') }}"
                        placeholder="{{ __('e.g. Thank you for your purchase! All goods remain the property of Sheffield Africa until full payment is received.') }}" />
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
