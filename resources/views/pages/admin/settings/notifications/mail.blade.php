<?php

use App\Livewire\Forms\Admin\Settings\MailSettingsForm;
use App\Settings\MailSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mail Configuration')] class extends Component {
    public MailSettingsForm $form;

    public function mount(MailSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(MailSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('Mail settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save mail settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }

    public function sendTest(): void
    {
        try {
            \Illuminate\Support\Facades\Mail::raw(__('This is a test email from your Sheffield Africa store.'), fn($msg) => $msg->to(auth()->user()->email)->subject(__('Test Email — Sheffield Africa')));
            $this->dispatch('notify', variant: 'success', message: __('Test email sent to :email.', ['email' => auth()->user()->email]));
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: __('Failed to send test email: :error', ['error' => $e->getMessage()]));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Mail configuration')" :subheading="__('SMTP and sender settings for all outgoing emails')">
        <form wire:submit="save" class="space-y-6">

            {{-- SMTP --}}
            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('SMTP configuration') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Mailer driver') }}" wire:model.live="form.mailer">
                            <flux:select.option value="smtp">SMTP</flux:select.option>
                            <flux:select.option value="ses">Amazon SES</flux:select.option>
                            <flux:select.option value="mailgun">Mailgun</flux:select.option>
                            <flux:select.option value="sendgrid">Sendgrid</flux:select.option>
                            <flux:select.option value="log">{{ __('Log (dev only)') }}</flux:select.option>
                            <flux:select.option value="array">{{ __('Array (testing)') }}</flux:select.option>
                        </flux:select>

                        <flux:select label="{{ __('Encryption') }}" wire:model="form.encryption">
                            <flux:select.option value="tls">TLS</flux:select.option>
                            <flux:select.option value="ssl">SSL</flux:select.option>
                            <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        </flux:select>
                    </div>

                    @if (in_array($form->mailer, ['smtp', 'ses', 'mailgun', 'sendgrid']))
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Host') }}" wire:model="form.host"
                                placeholder="smtp.mailtrap.io" />
                            <flux:input label="{{ __('Port') }}" wire:model="form.port" type="number"
                                placeholder="587" />
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Username') }}" wire:model="form.username"
                                placeholder="{{ __('SMTP username') }}" autocomplete="off" />
                            <x-pages::admin.settings.payments.partials.encrypted-field label="{{ __('Password') }}"
                                model="form.password" :hasValue="$form->has_password" />
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Sender Identity --}}
            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Sender identity') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('From name') }}" wire:model="form.from_name"
                            placeholder="Sheffield Africa" />
                        <flux:input label="{{ __('From email') }}" wire:model="form.from_address" type="email"
                            placeholder="no-reply@yourstore.com" />
                    </div>

                    <flux:input label="{{ __('Reply-to email') }}" wire:model="form.reply_to_address" type="email"
                        placeholder="support@yourstore.com"
                        description="{{ __('Optional — defaults to the from email if left blank') }}" />
                </div>
            </flux:card>

            <flux:separator />

            <div class="flex items-center justify-between">
                <flux:button type="button" wire:click="sendTest"
                    wire:confirm="{{ __('Send a test email to your account email?') }}" class="cursor-pointer">
                    {{ __('Send test email') }}
                </flux:button>

                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
