<?php

use App\Settings\MailSettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Mail Settings')] class extends Component {
    public string $active_driver = 'smtp';

    // SMTP
    public string $smtp_host = '';
    public int $smtp_port = 587;
    public string $smtp_username = '';
    public string $smtp_password = '';
    public string $smtp_encryption = 'tls';

    // Mailgun
    public string $mailgun_domain = '';
    public string $mailgun_secret = '';
    public string $mailgun_endpoint = 'api.mailgun.net';

    // Amazon SES
    public string $ses_key = '';
    public string $ses_secret = '';
    public string $ses_region = 'us-east-1';

    // Postmark
    public string $postmark_token = '';

    // Sender
    public string $from_address = '';
    public string $from_name = '';

    public function mount(MailSettings $settings): void
    {
        $this->active_driver = $settings->active_driver ?? 'smtp';

        $this->smtp_host = $settings->smtp_host ?? '';
        $this->smtp_port = $settings->smtp_port ?? 587;
        $this->smtp_username = $settings->smtp_username ?? '';
        $this->smtp_password = $settings->smtp_password ?? '';
        $this->smtp_encryption = $settings->smtp_encryption ?? 'tls';

        $this->mailgun_domain = $settings->mailgun_domain ?? '';
        $this->mailgun_secret = $settings->mailgun_secret ?? '';
        $this->mailgun_endpoint = $settings->mailgun_endpoint ?? 'api.mailgun.net';

        $this->ses_key = $settings->ses_key ?? '';
        $this->ses_secret = $settings->ses_secret ?? '';
        $this->ses_region = $settings->ses_region ?? 'us-east-1';

        $this->postmark_token = $settings->postmark_token ?? '';

        $this->from_address = $settings->from_address ?? '';
        $this->from_name = $settings->from_name ?? '';
    }

    public function activate(string $driver, MailSettings $settings): void
    {
        $settings->active_driver = $driver;
        $settings->save();
        $this->active_driver = $driver;
        $this->applyMailConfig();
        $this->dispatch('notify', variant: 'success', message: ucfirst($driver) . ' is now the active mail driver.');
    }

    public function saveSmtp(MailSettings $settings): void
    {
        $this->validateOnly('smtp_*', [
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'in:25,465,587,2525'],
            'smtp_username' => ['required', 'string', 'max:255'],
            'smtp_password' => ['required', 'string', 'max:255'],
            'smtp_encryption' => ['required', 'string', 'in:tls,ssl,none'],
        ]);

        try {
            $settings->smtp_host = $this->smtp_host;
            $settings->smtp_port = $this->smtp_port;
            $settings->smtp_username = $this->smtp_username;
            $settings->smtp_password = $this->smtp_password;
            $settings->smtp_encryption = $this->smtp_encryption;
            $this->saveSender($settings);
            $settings->save();

            $this->applyMailConfig();
            $this->modal('smtp-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'SMTP settings saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save SMTP settings.');
        }
    }

    public function saveMailgun(MailSettings $settings): void
    {
        $this->validateOnly('mailgun_*', [
            'mailgun_domain' => ['required', 'string', 'max:255'],
            'mailgun_secret' => ['required', 'string', 'max:255'],
            'mailgun_endpoint' => ['required', 'string', 'max:255'],
        ]);

        try {
            $settings->mailgun_domain = $this->mailgun_domain;
            $settings->mailgun_secret = $this->mailgun_secret;
            $settings->mailgun_endpoint = $this->mailgun_endpoint;
            $this->saveSender($settings);
            $settings->save();

            $this->applyMailConfig();
            $this->modal('mailgun-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'Mailgun settings saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save Mailgun settings.');
        }
    }

    public function saveSes(MailSettings $settings): void
    {
        $this->validateOnly('ses_*', [
            'ses_key' => ['required', 'string', 'max:255'],
            'ses_secret' => ['required', 'string', 'max:255'],
            'ses_region' => ['required', 'string', 'max:50'],
        ]);

        try {
            $settings->ses_key = $this->ses_key;
            $settings->ses_secret = $this->ses_secret;
            $settings->ses_region = $this->ses_region;
            $this->saveSender($settings);
            $settings->save();

            $this->applyMailConfig();
            $this->modal('ses-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'Amazon SES settings saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save SES settings.');
        }
    }

    public function savePostmark(MailSettings $settings): void
    {
        $this->validateOnly('postmark_*', [
            'postmark_token' => ['required', 'string', 'max:255'],
        ]);

        try {
            $settings->postmark_token = $this->postmark_token;
            $this->saveSender($settings);
            $settings->save();

            $this->applyMailConfig();
            $this->modal('postmark-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'Postmark settings saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save Postmark settings.');
        }
    }

    public function sendTestEmail(string $email): void
    {
        $this->validate(['_test_email' => 'required|email'], ['_test_email' => $email], ['_test_email' => 'email address']);

        try {
            $this->applyMailConfig();
            Mail::raw('This is a test email from your Sheffield Africa store.', function ($message) use ($email) {
                $message->to($email)->subject('Test Email — Sheffield Africa');
            });

            $this->dispatch('notify', variant: 'success', message: "Test email sent to {$email}.");
        } catch (\Throwable $e) {
            logger()->error('Failed to send test email.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to send. Check your mail configuration.');
        }
    }

    private function saveSender(MailSettings $settings): void
    {
        $this->validateOnly('from_*', [
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:100'],
        ]);

        $settings->from_address = $this->from_address;
        $settings->from_name = $this->from_name;
    }

    private function applyMailConfig(): void
    {
        config([
            'mail.default' => $this->active_driver,
            'mail.mailers.smtp.host' => $this->smtp_host,
            'mail.mailers.smtp.port' => $this->smtp_port,
            'mail.mailers.smtp.username' => $this->smtp_username,
            'mail.mailers.smtp.password' => $this->smtp_password,
            'mail.mailers.smtp.encryption' => $this->smtp_encryption === 'none' ? null : $this->smtp_encryption,
            'services.mailgun.domain' => $this->mailgun_domain,
            'services.mailgun.secret' => $this->mailgun_secret,
            'services.mailgun.endpoint' => $this->mailgun_endpoint,
            'services.ses.key' => $this->ses_key,
            'services.ses.secret' => $this->ses_secret,
            'services.ses.region' => $this->ses_region,
            'services.postmark.token' => $this->postmark_token,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);
    }
}; ?>

<div>
    @include('partials.settings-heading')

    <x-pages::admin.settings.layout :heading="__('Mail Settings')" :subheading="__('Configure and activate your outgoing mail driver')">

        {{-- Driver Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- SMTP --}}
            <flux:card class="p-0">
                <div class="flex items-start justify-between gap-3 p-5">
                    <div>
                        <flux:heading size="lg">SMTP</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">
                            Send mail via any standard SMTP server.
                        </flux:text>
                    </div>
                    <flux:switch wire:key="switch-smtp-{{ $active_driver }}" :checked="$active_driver === 'smtp'"
                        wire:click="activate('smtp')"
                        wire:confirm="{{ $active_driver === 'smtp' ? '' : 'Set SMTP as the active mail driver?' }}" />
                </div>

                <flux:separator />

                <div class="flex items-center justify-between px-5 py-2">
                    @if ($smtp_host && $smtp_username)
                        <flux:badge size="sm" color="green" variant="soft" icon="check-circle">Configured
                        </flux:badge>
                    @else
                        <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">Not
                            configured</flux:badge>
                    @endif

                    <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                        x-on:click="$flux.modal('smtp-config').show()" tooltip="Configure SMTP"
                        class="cursor-pointer" />
                </div>
            </flux:card>

            {{-- Mailgun --}}
            <flux:card class="p-0">
                <div class="flex items-start justify-between gap-3 p-5">
                    <div>
                        <flux:heading size="lg">Mailgun</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">
                            Transactional email via Mailgun's API.
                        </flux:text>
                    </div>
                    <flux:switch wire:key="switch-mailgun-{{ $active_driver }}" :checked="$active_driver === 'mailgun'"
                        wire:click="activate('mailgun')"
                        wire:confirm="{{ $active_driver === 'mailgun' ? '' : 'Set Mailgun as the active mail driver?' }}" />
                </div>

                <flux:separator />

                <div class="flex items-center justify-between px-5 py-2">
                    @if ($mailgun_domain && $mailgun_secret)
                        <flux:badge size="sm" color="green" variant="soft" icon="check-circle">Configured
                        </flux:badge>
                    @else
                        <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">Not
                            configured</flux:badge>
                    @endif

                    <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                        x-on:click="$flux.modal('mailgun-config').show()" tooltip="Configure Mailgun"
                        class="cursor-pointer" />
                </div>
            </flux:card>

            {{-- Amazon SES --}}
            <flux:card class="p-0">
                <div class="flex items-start justify-between gap-3 p-5">
                    <div>
                        <flux:heading size="lg">Amazon SES</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">
                            High-volume email delivery via AWS SES.
                        </flux:text>
                    </div>
                    <flux:switch wire:key="switch-ses-{{ $active_driver }}" :checked="$active_driver === 'ses'"
                        wire:click="activate('ses')"
                        wire:confirm="{{ $active_driver === 'ses' ? '' : 'Set Amazon SES as the active mail driver?' }}" />
                </div>

                <flux:separator />

                <div class="flex items-center justify-between px-5 py-2">
                    @if ($ses_key && $ses_secret)
                        <flux:badge size="sm" color="green" variant="soft" icon="check-circle">Configured
                        </flux:badge>
                    @else
                        <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">Not
                            configured</flux:badge>
                    @endif

                    <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                        x-on:click="$flux.modal('ses-config').show()" tooltip="Configure SES" class="cursor-pointer" />
                </div>
            </flux:card>

            {{-- Postmark --}}
            <flux:card class="p-0">
                <div class="flex items-start justify-between gap-3 p-5">
                    <div>
                        <flux:heading size="lg">Postmark</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">
                            Developer-friendly transactional email via Postmark.
                        </flux:text>
                    </div>
                    <flux:switch wire:key="switch-postmark-{{ $active_driver }}"
                        :checked="$active_driver === 'postmark'" wire:click="activate('postmark')"
                        wire:confirm="{{ $active_driver === 'postmark' ? '' : 'Set Postmark as the active mail driver?' }}" />
                </div>

                <flux:separator />

                <div class="flex items-center justify-between px-5 py-2">
                    @if ($postmark_token)
                        <flux:badge size="sm" color="green" variant="soft" icon="check-circle">Configured
                        </flux:badge>
                    @else
                        <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">Not
                            configured</flux:badge>
                    @endif

                    <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                        x-on:click="$flux.modal('postmark-config').show()" tooltip="Configure Postmark"
                        class="cursor-pointer" />
                </div>
            </flux:card>

        </div>


        {{-- Modals --}}

        {{-- Sender Details — shared partial inside each modal --}}

        {{-- SMTP Config --}}
        <flux:modal name="smtp-config" class="md:w-120">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">SMTP Configuration</flux:heading>
                    <flux:subheading>Enter your SMTP server credentials</flux:subheading>
                </div>

                <form wire:submit="saveSmtp" class="space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field class="col-span-2">
                            <flux:label>Host</flux:label>
                            <flux:input wire:model="smtp_host" placeholder="e.g. smtp.mailgun.org" />
                            <flux:error name="smtp_host" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Port</flux:label>
                            <flux:select wire:model="smtp_port">
                                <flux:select.option value="25">25</flux:select.option>
                                <flux:select.option value="465">465 (SSL)</flux:select.option>
                                <flux:select.option value="587">587 (TLS)</flux:select.option>
                                <flux:select.option value="2525">2525</flux:select.option>
                            </flux:select>
                            <flux:error name="smtp_port" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Encryption</flux:label>
                            <flux:select wire:model="smtp_encryption">
                                <flux:select.option value="tls">TLS</flux:select.option>
                                <flux:select.option value="ssl">SSL</flux:select.option>
                                <flux:select.option value="none">None</flux:select.option>
                            </flux:select>
                            <flux:error name="smtp_encryption" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Username</flux:label>
                            <flux:input wire:model="smtp_username" placeholder="SMTP username" />
                            <flux:error name="smtp_username" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Password</flux:label>
                            <flux:input wire:model="smtp_password" type="password" placeholder="SMTP password" />
                            <flux:error name="smtp_password" />
                        </flux:field>
                    </div>

                    <flux:separator />

                    @include('partials.mail-sender-fields')

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('smtp-config').close()"
                            class="cursor-pointer">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        {{-- Mailgun Config --}}
        <flux:modal name="mailgun-config" class="md:w-120">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Mailgun Configuration</flux:heading>
                    <flux:subheading>Enter your Mailgun API credentials</flux:subheading>
                </div>

                <form wire:submit="saveMailgun" class="space-y-4">
                    <flux:field>
                        <flux:label>Domain</flux:label>
                        <flux:input wire:model="mailgun_domain" placeholder="e.g. mg.yourdomain.com" />
                        <flux:error name="mailgun_domain" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Secret Key</flux:label>
                        <flux:input wire:model="mailgun_secret" type="password" placeholder="key-..." />
                        <flux:error name="mailgun_secret" />
                    </flux:field>

                    <flux:field>
                        <flux:label>API Endpoint</flux:label>
                        <flux:select wire:model="mailgun_endpoint">
                            <flux:select.option value="api.mailgun.net">api.mailgun.net (US)</flux:select.option>
                            <flux:select.option value="api.eu.mailgun.net">api.eu.mailgun.net (EU)</flux:select.option>
                        </flux:select>
                        <flux:error name="mailgun_endpoint" />
                    </flux:field>

                    <flux:separator />

                    @include('partials.mail-sender-fields')

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('mailgun-config').close()"
                            class="cursor-pointer">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        {{-- SES Config --}}
        <flux:modal name="ses-config" class="md:w-120">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Amazon SES Configuration</flux:heading>
                    <flux:subheading>Enter your AWS IAM credentials</flux:subheading>
                </div>

                <form wire:submit="saveSes" class="space-y-4">
                    <flux:field>
                        <flux:label>Access Key ID</flux:label>
                        <flux:input wire:model="ses_key" placeholder="AKIA..." />
                        <flux:error name="ses_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Secret Access Key</flux:label>
                        <flux:input wire:model="ses_secret" type="password" placeholder="Secret access key" />
                        <flux:error name="ses_secret" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Region</flux:label>
                        <flux:select wire:model="ses_region">
                            <flux:select.option value="us-east-1">us-east-1 (N. Virginia)</flux:select.option>
                            <flux:select.option value="us-west-2">us-west-2 (Oregon)</flux:select.option>
                            <flux:select.option value="eu-west-1">eu-west-1 (Ireland)</flux:select.option>
                            <flux:select.option value="eu-central-1">eu-central-1 (Frankfurt)</flux:select.option>
                            <flux:select.option value="ap-southeast-1">ap-southeast-1 (Singapore)</flux:select.option>
                            <flux:select.option value="af-south-1">af-south-1 (Cape Town)</flux:select.option>
                        </flux:select>
                        <flux:error name="ses_region" />
                    </flux:field>

                    <flux:separator />

                    @include('partials.mail-sender-fields')

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('ses-config').close()"
                            class="cursor-pointer">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        {{-- Postmark Config --}}
        <flux:modal name="postmark-config" class="md:w-120">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Postmark Configuration</flux:heading>
                    <flux:subheading>Enter your Postmark server token</flux:subheading>
                </div>

                <form wire:submit="savePostmark" class="space-y-4">
                    <flux:field>
                        <flux:label>Server Token</flux:label>
                        <flux:input wire:model="postmark_token" type="password"
                            placeholder="Server token from Postmark dashboard" />
                        <flux:error name="postmark_token" />
                    </flux:field>

                    <flux:separator />

                    @include('partials.mail-sender-fields')

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('postmark-config').close()"
                            class="cursor-pointer">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

    </x-pages::admin.settings.layout>
</div>
