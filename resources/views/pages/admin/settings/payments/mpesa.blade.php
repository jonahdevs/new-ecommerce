<?php

use App\Livewire\Forms\Admin\Settings\MpesaSettingsForm;
use App\Settings\MpesaSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('M-Pesa Settings')] class extends Component {
    public MpesaSettingsForm $form;

    public function mount(MpesaSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(MpesaSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('M-Pesa settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save M-Pesa settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('M-Pesa (Daraja API)')" :subheading="__('Configure Safaricom M-Pesa STK Push integration')">
        <form wire:submit="save" class="space-y-6">

            {{-- Status & Environment --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3 flex items-center justify-between">
                    <flux:heading>{{ __('M-Pesa status') }}</flux:heading>
                    <flux:checkbox wire:model.live="form.enabled" label="{{ __('Enabled') }}" />
                </div>

                @if ($form->enabled)
                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:select label="{{ __('Environment') }}" wire:model="form.environment">
                                <flux:select.option value="sandbox">{{ __('Sandbox (testing)') }}</flux:select.option>
                                <flux:select.option value="live">{{ __('Live (production)') }}</flux:select.option>
                            </flux:select>

                            <flux:select label="{{ __('Shortcode type') }}" wire:model="form.shortcode_type">
                                <flux:select.option value="paybill">{{ __('Paybill') }}</flux:select.option>
                                <flux:select.option value="till">{{ __('Till number') }}</flux:select.option>
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Shortcode') }}" wire:model="form.shortcode" placeholder="174379"
                                description="{{ __('Your Paybill or Till number') }}" />
                            <flux:input label="{{ __('Initiator name') }}" wire:model="form.initiator_name"
                                placeholder="{{ __('Required for B2C transactions') }}" />
                        </div>
                    </div>
                @endif
            </flux:card>

            {{-- Credentials --}}
            @if ($form->enabled)
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('API credentials') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Credentials are encrypted and stored securely. Leave blank to keep existing values.') }}
                        </flux:subheading>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('Consumer key') }}"
                                model="form.consumer_key" :hasValue="$form->has_consumer_key" />
                            <x-pages::admin.settings.payments.partials.encrypted-field
                                label="{{ __('Consumer secret') }}" model="form.consumer_secret" :hasValue="$form->has_consumer_secret" />
                        </div>

                        <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('Passkey') }}"
                            model="form.passkey" :hasValue="$form->has_passkey"
                            description="{{ __('Lipa Na M-Pesa Online passkey from the developer portal') }}" />

                        <x-pages::admin.settings.payments.partials.encrypted-field
                            label="{{ __('Initiator password') }}" model="form.initiator_password" :hasValue="$form->has_initiator_password"
                            description="{{ __('Required only for B2C/reversal transactions') }}" />
                    </div>
                </flux:card>

                {{-- Callback URL --}}
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Callback') }}</flux:heading>
                    </div>
                    <div class="p-5">
                        <flux:input label="{{ __('Callback URL') }}" wire:model="form.callback_url"
                            placeholder="{{ url('/api/mpesa/callback') }}"
                            description="{{ __('Leave blank to use the default. Register this URL in the Safaricom developer portal.') }}" />
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
