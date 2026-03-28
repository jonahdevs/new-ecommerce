<?php

use App\Livewire\Forms\Admin\Settings\PaypalSettingsForm;
use App\Settings\PaypalSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('PayPal Settings')] class extends Component {
    public PaypalSettingsForm $form;

    public function mount(PaypalSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(PaypalSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('PayPal settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save PayPal settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('PayPal')" :subheading="__('Configure PayPal wallet and card payments')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b px-4 py-3 flex items-center justify-between">
                    <flux:heading>{{ __('PayPal status') }}</flux:heading>
                    <flux:checkbox wire:model.live="form.enabled" label="{{ __('Enabled') }}" />
                </div>

                @if ($form->enabled)
                    <div class="p-5">
                        <flux:select label="{{ __('Environment') }}" wire:model="form.environment">
                            <flux:select.option value="sandbox">{{ __('Sandbox (testing)') }}</flux:select.option>
                            <flux:select.option value="live">{{ __('Live (production)') }}</flux:select.option>
                        </flux:select>
                    </div>
                @endif
            </flux:card>

            @if ($form->enabled)
                <flux:card class="p-0">
                    <div class="border-b px-4 py-3">
                        <flux:heading>{{ __('API credentials') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Credentials are encrypted and stored securely. Leave blank to keep existing values.') }}
                        </flux:subheading>
                    </div>

                    <div class="p-5 space-y-5">
                        <flux:input label="{{ __('Client ID') }}" wire:model="form.client_id" placeholder="AYSq3R..."
                            description="{{ __('Found in your PayPal developer dashboard') }}" />

                        <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('Client secret') }}"
                            model="form.client_secret" :hasValue="$form->has_client_secret" />
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
