<?php

use App\Livewire\Forms\Admin\Settings\PaymentSettingsForm;
use App\Settings\PaymentSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cash on Delivery')] class extends Component {
    public PaymentSettingsForm $form;

    public function mount(PaymentSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(PaymentSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('Cash on delivery settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save COD settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Cash on delivery')" :subheading="__('Available alongside any payment mode')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Cash on delivery') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model.live="form.cod_enabled" label="{{ __('Enable cash on delivery') }}"
                        description="{{ __('Allow customers to pay when their order is delivered') }}" />

                    @if ($form->cod_enabled)
                        <flux:textarea label="{{ __('Checkout instructions') }}" wire:model="form.cod_instructions"
                            rows="3"
                            placeholder="{{ __('Pay with cash when your order arrives. Our delivery agent will collect payment.') }}"
                            description="{{ __('Shown to the customer at checkout when they select this payment method') }}" />
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
