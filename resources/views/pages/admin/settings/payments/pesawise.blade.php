<?php

use App\Livewire\Forms\Admin\Settings\PesawiseSettingsForm;
use App\Settings\PesawiseSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('PesaWise Settings')] class extends Component {
    public PesawiseSettingsForm $form;

    public function mount(PesawiseSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(PesawiseSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('PesaWise settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save PesaWise settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('PesaWise')" :subheading="__('Configure PesaWise — handles M-Pesa, Airtel, cards and bank payments')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b px-4 py-3 flex items-center justify-between">
                    <flux:heading>{{ __('PesaWise status') }}</flux:heading>
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
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('API key') }}"
                                model="form.api_key" :hasValue="$form->has_api_key" />
                            <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('API secret') }}"
                                model="form.api_secret" :hasValue="$form->has_api_secret" />
                        </div>

                        <flux:input label="{{ __('Merchant account number') }}" wire:model="form.account_number"
                            placeholder="{{ __('Your PesaWise merchant account number') }}" />
                    </div>
                </flux:card>

                <flux:card class="p-0">
                    <div class="border-b px-4 py-3">
                        <flux:heading>{{ __('Callback') }}</flux:heading>
                    </div>
                    <div class="p-5">
                        <flux:input label="{{ __('Callback URL') }}" wire:model="form.callback_url"
                            placeholder="{{ url('/api/pesawise/callback') }}"
                            description="{{ __('Register this URL in your PesaWise dashboard.') }}" />
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
