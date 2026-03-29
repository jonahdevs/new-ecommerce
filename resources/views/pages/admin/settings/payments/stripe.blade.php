<?php

use App\Livewire\Forms\Admin\Settings\StripeSettingsForm;
use App\Settings\StripeSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Stripe Settings')] class extends Component {
    public StripeSettingsForm $form;

    public function mount(StripeSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(StripeSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Stripe settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save Stripe settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Stripe')" :subheading="__('Configure Stripe for card, Apple Pay and Google Pay')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3 flex items-center justify-between">
                    <flux:heading>{{ __('Stripe status') }}</flux:heading>
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
                        <flux:heading>{{ __('API keys') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Credentials are encrypted and stored securely. Leave blank to keep existing values.') }}
                        </flux:subheading>
                    </div>

                    <div class="p-5 space-y-5">
                        <flux:input label="{{ __('Publishable key') }}" wire:model="form.public_key"
                            placeholder="pk_test_..." description="{{ __('Safe to expose — used in the browser') }}" />

                        <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('Secret key') }}"
                            model="form.secret_key" :hasValue="$form->has_secret_key"
                            description="{{ __('Never expose this — server-side only') }}" />

                        <x-pages::admin.settings.payments.partials.encrypted-field
                            label="{{ __('Webhook signing secret') }}" model="form.webhook_secret" :hasValue="$form->has_webhook_secret"
                            description="{{ __('Found in the Stripe dashboard under Webhooks. Register: ') . url('/api/stripe/webhook') }}" />
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
