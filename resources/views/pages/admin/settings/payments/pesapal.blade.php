<?php

use App\Livewire\Forms\Admin\Settings\PesapalSettingsForm;
use App\Settings\PesapalSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('PesaPal Settings')] class extends Component {
    public PesapalSettingsForm $form;

    public function mount(PesapalSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(PesapalSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('PesaPal settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save PesaPal settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('PesaPal')" :subheading="__('Configure PesaPal — handles M-Pesa, Airtel, cards and bank transfers')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3 flex items-center justify-between">
                    <flux:heading>{{ __('PesaPal status') }}</flux:heading>
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

                        <flux:input label="{{ __('IPN ID') }}" wire:model="form.ipn_id"
                            placeholder="{{ __('Instant Payment Notification ID') }}"
                            description="{{ __('Register your IPN URL in the PesaPal merchant portal first, then paste the ID here.') }}" />
                    </div>
                </flux:card>

                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Callback') }}</flux:heading>
                    </div>
                    <div class="p-5">
                        <flux:input label="{{ __('Callback URL') }}" wire:model="form.callback_url"
                            placeholder="{{ url('/api/pesapal/callback') }}"
                            description="{{ __('Register this URL in the PesaPal merchant portal.') }}" />
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
