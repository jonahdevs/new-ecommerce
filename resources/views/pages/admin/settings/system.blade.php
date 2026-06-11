<?php

use App\Settings\EmailApiSettings;
use App\Settings\EmailSettings;
use App\Settings\IntegrationSettings;
use App\Settings\MaintenanceSettings;
use App\Settings\SecuritySettings;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('System settings — Admin')] class extends Component {
    #[Url]
    public string $section = 'email';

    // ==================================================
    // EMAIL & SMS
    // ==================================================
    public string $mail_driver = 'smtp';

    public string $sms_provider = 'none';

    public string $sms_sender_id = '';

    public ?string $configuringMailDriver = null;

    public bool $showMailDriverModal = false;

    // SMTP credentials
    public ?string $smtp_host = null;

    public ?int $smtp_port = null;

    public ?string $smtp_encryption = null;

    public ?string $smtp_username = null;

    public ?string $smtp_password = null;

    // Mailgun credentials
    public ?string $mailgun_domain = null;

    public ?string $mailgun_secret = null;

    // Amazon SES credentials
    public ?string $ses_key = null;

    public ?string $ses_secret = null;

    public ?string $ses_region = null;

    // Postmark credentials
    public ?string $postmark_token = null;

    // Resend credentials
    public ?string $resend_key = null;

    // ==================================================
    // INTEGRATIONS
    // ==================================================
    public bool $google_login_enabled = false;

    public ?string $google_client_id = null;

    public ?string $google_client_secret = null;

    public ?string $google_redirect_url = null;

    public string $google_maps_api_key = '';

    public string $map_provider = 'leaflet';

    public bool $recaptcha_enabled = false;

    public string $recaptcha_site_key = '';

    public ?string $configuringIntegration = null;

    public bool $showIntegrationModal = false;

    // ==================================================
    // SAP / KRA
    // ==================================================
    public bool $sap_enabled = false;

    public bool $sap_auto_sync_orders = true;

    public bool $sap_sync_price = true;

    public bool $sap_sync_quantity = true;

    public string $sap_base_url = '';

    public string $sap_api_key = '';

    public string $sap_webhook_secret = '';



    // ==================================================
    // SECURITY
    // ==================================================
    public int $min_password_length = 8;

    public bool $require_two_factor = false;

    public int $session_lifetime = 120;

    // ==================================================
    // MAINTENANCE
    // ==================================================
    public bool $maintenance_mode = false;

    public string $maintenance_message = '';

    public function mount(EmailSettings $email, EmailApiSettings $emailApi, IntegrationSettings $integrations, SecuritySettings $security, MaintenanceSettings $maintenance): void
    {
        $this->mail_driver = $email->mail_driver;
        $this->sms_provider = $email->sms_provider;
        $this->sms_sender_id = $email->sms_sender_id;

        $this->smtp_host = $emailApi->smtp_host;
        $this->smtp_port = $emailApi->smtp_port;
        $this->smtp_encryption = $emailApi->smtp_encryption;
        $this->smtp_username = $emailApi->smtp_username;
        $this->smtp_password = $emailApi->smtp_password;
        $this->mailgun_domain = $emailApi->mailgun_domain;
        $this->mailgun_secret = $emailApi->mailgun_secret;
        $this->ses_key = $emailApi->ses_key;
        $this->ses_secret = $emailApi->ses_secret;
        $this->ses_region = $emailApi->ses_region;
        $this->postmark_token = $emailApi->postmark_token;
        $this->resend_key = $emailApi->resend_key;

        $this->google_login_enabled = $integrations->google_login_enabled;
        $this->google_client_id = $integrations->google_client_id;
        $this->google_client_secret = $integrations->google_client_secret;
        $this->google_redirect_url = $integrations->google_redirect_url;
        $this->google_maps_api_key = $integrations->google_maps_api_key;
        $this->map_provider = $integrations->map_provider;
        $this->recaptcha_enabled = $integrations->recaptcha_enabled;
        $this->recaptcha_site_key = $integrations->recaptcha_site_key;

        $this->sap_enabled = $integrations->sap_enabled;
        $this->sap_auto_sync_orders = $integrations->sap_auto_sync_orders;
        $this->sap_sync_price = $integrations->sap_sync_price;
        $this->sap_sync_quantity = $integrations->sap_sync_quantity;
        $this->sap_base_url = $integrations->sap_base_url ?? '';
        $this->sap_api_key = $integrations->sap_api_key ?? '';
        $this->sap_webhook_secret = $integrations->sap_webhook_secret ?? '';

        $this->min_password_length = $security->min_password_length;
        $this->require_two_factor = $security->require_two_factor;
        $this->session_lifetime = $security->session_lifetime;

        $this->maintenance_mode = $maintenance->maintenance_mode;
        $this->maintenance_message = $maintenance->maintenance_message;
    }

    public function saveSms(EmailSettings $settings): void
    {
        $this->validate([
            'sms_provider'  => ['required', 'in:none,africastalking,twilio'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
        ]);

        $settings->fill([
            'sms_provider'  => $this->sms_provider,
            'sms_sender_id' => $this->sms_sender_id,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'SMS settings updated.', variant: 'success');
    }

    public function setMailDriver(string $driver): void
    {
        $this->mail_driver = $driver;
        app(EmailSettings::class)->fill(['mail_driver' => $driver])->save();
        Flux::toast(heading: 'Driver changed', text: ucfirst($driver).' is now the active mail driver.', variant: 'success');
    }

    public function configureMailDriver(string $driver): void
    {
        $this->configuringMailDriver = $driver;
        $this->showMailDriverModal = true;
    }

    public function saveMailDriverConfig(EmailApiSettings $api): void
    {
        $this->validate([
            'smtp_host'       => ['nullable', 'string', 'max:255'],
            'smtp_port'       => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
            'smtp_username'   => ['nullable', 'string', 'max:255'],
            'mailgun_domain'  => ['nullable', 'string', 'max:255'],
            'ses_region'      => ['nullable', 'string', 'max:50'],
        ]);

        $api->fill([
            'smtp_host'       => $this->smtp_host ?: null,
            'smtp_port'       => $this->smtp_port ?: null,
            'smtp_encryption' => $this->smtp_encryption ?: null,
            'smtp_username'   => $this->smtp_username ?: null,
            'smtp_password'   => $this->smtp_password ?: null,
            'mailgun_domain'  => $this->mailgun_domain ?: null,
            'mailgun_secret'  => $this->mailgun_secret ?: null,
            'ses_key'         => $this->ses_key ?: null,
            'ses_secret'      => $this->ses_secret ?: null,
            'ses_region'      => $this->ses_region ?: null,
            'postmark_token'  => $this->postmark_token ?: null,
            'resend_key'      => $this->resend_key ?: null,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Mail driver credentials updated.', variant: 'success');
        $this->showMailDriverModal = false;
    }

    public function updatedGoogleLoginEnabled(): void
    {
        app(IntegrationSettings::class)->fill(['google_login_enabled' => $this->google_login_enabled])->save();
    }

    public function saveGoogleLoginConfig(IntegrationSettings $settings): void
    {
        $this->validate([
            'google_client_id'     => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:255'],
            'google_redirect_url'  => ['nullable', 'url', 'max:255'],
        ]);

        $settings->fill([
            'google_client_id'     => $this->google_client_id ?: null,
            'google_client_secret' => $this->google_client_secret ?: null,
            'google_redirect_url'  => $this->google_redirect_url ?: null,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Google login settings updated.', variant: 'success');
        $this->showIntegrationModal = false;
    }

    public function updatedRecaptchaEnabled(): void
    {
        app(IntegrationSettings::class)->fill(['recaptcha_enabled' => $this->recaptcha_enabled])->save();
    }

    public function configureIntegration(string $key): void
    {
        $this->configuringIntegration = $key;
        $this->showIntegrationModal = true;
    }

    public function saveIntegrationConfig(IntegrationSettings $settings): void
    {
        $this->validate([
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            'map_provider'        => ['required', 'in:leaflet,google'],
            'recaptcha_site_key'  => ['nullable', 'string', 'max:255'],
        ]);

        $settings->fill([
            'google_maps_api_key' => $this->google_maps_api_key ?: null,
            'map_provider'        => $this->map_provider,
            'recaptcha_site_key'  => $this->recaptcha_site_key ?: null,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Integration settings updated.', variant: 'success');
        $this->showIntegrationModal = false;
    }

    public function updatedSapEnabled(): void
    {
        app(IntegrationSettings::class)->fill(['sap_enabled' => $this->sap_enabled])->save();
    }

    public function saveSapConfig(IntegrationSettings $settings): void
    {
        $this->validate([
            'sap_base_url'       => ['nullable', 'url', 'max:255'],
            'sap_api_key'        => ['nullable', 'string', 'max:255'],
            'sap_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $settings->fill([
            'sap_enabled'          => $this->sap_enabled,
            'sap_auto_sync_orders' => $this->sap_auto_sync_orders,
            'sap_sync_price'       => $this->sap_sync_price,
            'sap_sync_quantity'    => $this->sap_sync_quantity,
            'sap_base_url'         => $this->sap_base_url ?: null,
            'sap_api_key'          => $this->sap_api_key ?: null,
            'sap_webhook_secret'   => $this->sap_webhook_secret ?: null,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'SAP settings updated.', variant: 'success');
        $this->showIntegrationModal = false;
    }

    public function saveSecurity(SecuritySettings $settings): void
    {
        $this->validate([
            'min_password_length' => ['required', 'integer', 'min:6', 'max:64'],
            'session_lifetime' => ['required', 'integer', 'min:5', 'max:43200'],
        ]);

        $settings
            ->fill([
                'min_password_length' => (int) $this->min_password_length,
                'require_two_factor' => $this->require_two_factor,
                'session_lifetime' => (int) $this->session_lifetime,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Security settings updated.', variant: 'success');
    }

    public function saveMaintenance(MaintenanceSettings $settings): void
    {
        $this->validate([
            'maintenance_message' => ['required', 'string', 'max:500'],
        ]);

        $settings
            ->fill([
                'maintenance_mode' => $this->maintenance_mode,
                'maintenance_message' => $this->maintenance_message,
            ])
            ->save();

        Flux::toast(heading: $this->maintenance_mode ? 'Maintenance mode on' : 'Maintenance mode off', text: $this->maintenance_mode ? 'The storefront now shows a maintenance message.' : 'The storefront is live.', variant: $this->maintenance_mode ? 'warning' : 'success');
    }
}; ?>

<x-admin.settings-shell tab="system" :section="$section">

    {{-- Email & SMS --}}
    @if ($section === 'email')
    <div class="space-y-6">

        {{-- Mail drivers grid --}}
        @php
            $emailApi = app(\App\Settings\EmailApiSettings::class);
            $smtpHost = $emailApi->smtp_host ?: config('mail.mailers.smtp.host');
            $mailDrivers = [
                [
                    'key'          => 'smtp',
                    'name'         => 'SMTP',
                    'icon'         => 'server',
                    'description'  => 'Send via any SMTP server. Works with Gmail, Outlook, Mailhog, and more.',
                    'active'       => $mail_driver === 'smtp',
                    'configurable' => true,
                    'connected'    => (bool) ($smtpHost && $smtpHost !== '127.0.0.1'),
                ],
                [
                    'key'          => 'ses',
                    'name'         => 'Amazon SES',
                    'icon'         => 'cloud',
                    'description'  => 'Scalable transactional email via AWS Simple Email Service.',
                    'active'       => $mail_driver === 'ses',
                    'configurable' => true,
                    'connected'    => (bool) (($emailApi->ses_key ?: config('services.ses.key'))),
                ],
                [
                    'key'          => 'mailgun',
                    'name'         => 'Mailgun',
                    'icon'         => 'bolt',
                    'description'  => 'Developer-friendly transactional email API.',
                    'active'       => $mail_driver === 'mailgun',
                    'configurable' => true,
                    'connected'    => (bool) ($emailApi->mailgun_domain && $emailApi->mailgun_secret),
                ],
                [
                    'key'          => 'postmark',
                    'name'         => 'Postmark',
                    'icon'         => 'paper-airplane',
                    'description'  => 'Fast delivery with detailed analytics for transactional email.',
                    'active'       => $mail_driver === 'postmark',
                    'configurable' => true,
                    'connected'    => (bool) ($emailApi->postmark_token ?: config('services.postmark.key')),
                ],
                [
                    'key'          => 'resend',
                    'name'         => 'Resend',
                    'icon'         => 'arrow-path',
                    'description'  => 'Modern email API built for developers.',
                    'active'       => $mail_driver === 'resend',
                    'configurable' => true,
                    'connected'    => (bool) ($emailApi->resend_key ?: config('services.resend.key')),
                ],
                [
                    'key'          => 'log',
                    'name'         => 'Log (Dev)',
                    'icon'         => 'document-text',
                    'description'  => 'Writes emails to the application log. For local development only.',
                    'active'       => $mail_driver === 'log',
                    'configurable' => false,
                    'connected'    => true,
                ],
            ];
        @endphp

        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Mail Drivers</flux:heading>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                @foreach ($mailDrivers as $driver)
                    <div class="flex flex-col rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="p-5">
                            {{-- Top: name/icon + active toggle --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <flux:icon :name="$driver['icon']" class="size-5 text-zinc-500 dark:text-zinc-400" />
                                    <span class="text-sm font-semibold dark:text-white">{{ $driver['name'] }}</span>
                                    @if ($driver['active'])
                                        <flux:badge color="blue" size="sm">Active</flux:badge>
                                    @endif
                                </div>
                                <flux:switch
                                    :checked="$driver['active']"
                                    wire:click="setMailDriver('{{ $driver['key'] }}')" />
                            </div>

                            {{-- Description --}}
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $driver['description'] }}</p>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between border-t border-zinc-200 px-5 py-2 dark:border-zinc-700">
                            <flux:badge
                                :color="$driver['connected'] ? 'green' : 'zinc'"
                                size="sm"
                                :icon="$driver['connected'] ? 'check' : 'x-mark'">
                                {{ $driver['connected'] ? 'Connected' : 'Not connected' }}
                            </flux:badge>
                            <flux:button size="sm" variant="ghost" icon="cog-6-tooth"
                                wire:click="configureMailDriver('{{ $driver['key'] }}')"
                                :disabled="! $driver['configurable']"
                                tooltip="{{ $driver['configurable'] ? 'Configure' : 'No configuration needed' }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- SMS --}}
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">SMS</flux:heading>
            </div>
            <form wire:submit="saveSms" class="space-y-4 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="sms_provider" label="SMS provider">
                        <flux:select.option value="none">None</flux:select.option>
                        <flux:select.option value="africastalking">Africa's Talking</flux:select.option>
                        <flux:select.option value="twilio">Twilio</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="sms_sender_id" label="Sender ID" placeholder="e.g. SHEFFIELD" />
                </div>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Mail driver config modal --}}
        <flux:modal wire:model.self="showMailDriverModal" class="w-full max-w-lg" :dismissible="true">
            <form wire:submit="saveMailDriverConfig" class="space-y-5">
                @if ($configuringMailDriver === 'smtp')
                    <div>
                        <flux:heading size="lg">SMTP Settings</flux:heading>
                        <flux:subheading>Leave blank to use .env values.</flux:subheading>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="smtp_host" label="Host" placeholder="{{ config('mail.mailers.smtp.host') }}" />
                        <flux:input wire:model="smtp_port" label="Port" type="number" placeholder="{{ config('mail.mailers.smtp.port') }}" />
                    </div>
                    <flux:select wire:model="smtp_encryption" label="Encryption">
                        <flux:select.option value="">Use .env ({{ config('mail.mailers.smtp.scheme') ?: 'none' }})</flux:select.option>
                        <flux:select.option value="tls">TLS</flux:select.option>
                        <flux:select.option value="ssl">SSL</flux:select.option>
                        <flux:select.option value="none">None</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="smtp_username" label="Username" />
                    <flux:input wire:model="smtp_password" label="Password" type="password" viewable />

                @elseif ($configuringMailDriver === 'ses')
                    <div>
                        <flux:heading size="lg">Amazon SES Settings</flux:heading>
                        <flux:subheading>Leave blank to use .env values (AWS_ACCESS_KEY_ID etc.).</flux:subheading>
                    </div>
                    <flux:input wire:model="ses_key" label="Access Key ID" />
                    <flux:input wire:model="ses_secret" label="Secret Access Key" type="password" viewable />
                    <flux:input wire:model="ses_region" label="Region" placeholder="{{ config('services.ses.region', 'us-east-1') }}" />

                @elseif ($configuringMailDriver === 'mailgun')
                    <div>
                        <flux:heading size="lg">Mailgun Settings</flux:heading>
                        <flux:subheading>Leave blank to use .env values.</flux:subheading>
                    </div>
                    <flux:input wire:model="mailgun_domain" label="Domain" placeholder="mg.yourdomain.com" />
                    <flux:input wire:model="mailgun_secret" label="API Key" type="password" viewable />

                @elseif ($configuringMailDriver === 'postmark')
                    <div>
                        <flux:heading size="lg">Postmark Settings</flux:heading>
                        <flux:subheading>Leave blank to use .env values.</flux:subheading>
                    </div>
                    <flux:input wire:model="postmark_token" label="Server API Token" type="password" viewable />

                @elseif ($configuringMailDriver === 'resend')
                    <div>
                        <flux:heading size="lg">Resend Settings</flux:heading>
                        <flux:subheading>Leave blank to use .env values.</flux:subheading>
                    </div>
                    <flux:input wire:model="resend_key" label="API Key" type="password" viewable />
                @endif

                <div class="flex gap-2 pt-1">
                    <flux:button type="submit" variant="primary" class="flex-1">Save changes</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>

    </div>
    @endif

    {{-- Integrations --}}
    @if ($section === 'integrations')
    @php
        $googleLoginConnected = (bool) ($google_client_id ?: config('services.google.client_id'));
        $mapsConnected = (bool) ($google_maps_api_key ?: config('services.google.maps_api_key'));
        $recaptchaConnected = (bool) ($recaptcha_site_key ?: config('services.recaptcha.site_key'));

        $sapConnected = (bool) (($sap_base_url ?: config('sap.base_url')) && ($sap_api_key ?: config('sap.api_key')));

        $integrationCards = [
            [
                'key'          => 'google_login',
                'name'         => 'Sign in with Google',
                'icon'         => 'globe-alt',
                'description'  => 'Allow customers to sign in using their Google account. OAuth credentials are managed via your environment file.',
                'toggleable'   => true,
                'enabled'      => $google_login_enabled,
                'configurable' => true,
                'connected'    => $googleLoginConnected,
            ],
            [
                'key'          => 'google_maps',
                'name'         => 'Google Maps',
                'icon'         => 'map-pin',
                'description'  => 'Use Google Maps for address lookup on checkout. Requires an API key. Falls back to OpenStreetMap when not configured.',
                'toggleable'   => false,
                'enabled'      => false,
                'configurable' => true,
                'connected'    => $mapsConnected,
            ],
            [
                'key'          => 'recaptcha',
                'name'         => 'reCAPTCHA',
                'icon'         => 'shield-check',
                'description'  => 'Protect forms from spam and abuse using Google reCAPTCHA. Requires a site key.',
                'toggleable'   => true,
                'enabled'      => $recaptcha_enabled,
                'configurable' => true,
                'connected'    => $recaptchaConnected,
            ],
            [
                'key'          => 'sap',
                'name'         => 'SAP Middleware',
                'icon'         => 'server',
                'description'  => 'Sync orders and product data with your SAP system. KRA receipts (CU number) are returned by SAP.',
                'toggleable'   => true,
                'enabled'      => $sap_enabled,
                'configurable' => true,
                'connected'    => $sapConnected,
            ],
        ];
    @endphp

    <flux:card class="overflow-hidden p-0">
        <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wide">Integrations</flux:heading>
        </div>

        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
            @foreach ($integrationCards as $card)
                <flux:card class="overflow-hidden p-0">
                    <div class="flex flex-col gap-3 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon :name="$card['icon']" class="size-5 text-zinc-600 dark:text-zinc-300" />
                                </div>
                                <flux:heading size="sm">{{ $card['name'] }}</flux:heading>
                            </div>
                            @if ($card['toggleable'])
                                <flux:switch wire:model.live="{{ $card['key'] }}_enabled" />
                            @endif
                        </div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $card['description'] }}</flux:text>
                    </div>

                    <div class="flex items-center justify-between border-t border-zinc-200 px-5 py-2 dark:border-zinc-700">
                        @if ($card['connected'])
                            <flux:badge color="green" size="sm">Connected</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Not connected</flux:badge>
                        @endif

                        @if ($card['configurable'])
                            <flux:button
                                icon="cog-6-tooth"
                                variant="ghost"
                                size="sm"
                                wire:click="configureIntegration('{{ $card['key'] }}')"
                                tooltip="Configure {{ $card['name'] }}"
                            />
                        @else
                            <flux:button
                                icon="cog-6-tooth"
                                variant="ghost"
                                size="sm"
                                disabled
                                tooltip="Credentials are managed via your .env file"
                            />
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
    </flux:card>

    {{-- Integration config modal --}}
    <flux:modal wire:model.self="showIntegrationModal" class="w-full max-w-md">
        @if ($configuringIntegration === 'google_login')
            <flux:heading>Sign in with Google</flux:heading>
            <div class="mt-5 space-y-4">
                <flux:input wire:model="google_client_id" label="Client ID" />
                <flux:input wire:model="google_client_secret" label="Client Secret" type="password" viewable />
                <flux:input wire:model="google_redirect_url" label="Redirect URL" type="url" placeholder="{{ url('/auth/google/callback') }}" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button wire:click="$set('showIntegrationModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveGoogleLoginConfig" variant="primary">Save</flux:button>
            </div>
        @elseif ($configuringIntegration === 'google_maps')
            <flux:heading>Google Maps</flux:heading>
            <div class="mt-5 space-y-4">
                <flux:select wire:model.live="map_provider" label="Map provider">
                    <flux:select.option value="leaflet">OpenStreetMap (Leaflet) — free, no key needed</flux:select.option>
                    <flux:select.option value="google">Google Maps — better local data for East Africa</flux:select.option>
                </flux:select>
                @if ($map_provider === 'google')
                    <flux:input wire:model="google_maps_api_key" label="API key" placeholder="AIza…" />
                @endif
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button wire:click="$set('showIntegrationModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveIntegrationConfig" variant="primary">Save</flux:button>
            </div>
        @elseif ($configuringIntegration === 'recaptcha')
            <flux:heading>reCAPTCHA</flux:heading>
            <div class="mt-5 space-y-4">
                <flux:input wire:model="recaptcha_site_key" label="Site key" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button wire:click="$set('showIntegrationModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveIntegrationConfig" variant="primary">Save</flux:button>
            </div>
        @elseif ($configuringIntegration === 'sap')
            <flux:heading>SAP Middleware</flux:heading>
            <flux:subheading>Leave blank to use .env values.</flux:subheading>
            <div class="mt-5 space-y-4">
                <flux:input wire:model="sap_base_url" label="Base URL" type="url" placeholder="{{ config('sap.base_url') ?: 'http://your-sap-server:85' }}" />
                <flux:input wire:model="sap_api_key" label="API Key" type="password" viewable />
                <flux:input wire:model="sap_webhook_secret" label="Webhook Secret" type="password" viewable />
                <flux:separator text="Sync Permissions" />
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:label>Auto-sync orders</flux:label>
                            <flux:text size="sm" class="text-xs text-zinc-500">Push orders to SAP on payment confirmation.</flux:text>
                        </div>
                        <flux:switch wire:model="sap_auto_sync_orders" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:label>Allow price updates</flux:label>
                            <flux:text size="sm" class="text-xs text-zinc-500">SAP can overwrite product sale prices.</flux:text>
                        </div>
                        <flux:switch wire:model="sap_sync_price" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:label>Allow stock updates</flux:label>
                            <flux:text size="sm" class="text-xs text-zinc-500">SAP can overwrite stock levels and status.</flux:text>
                        </div>
                        <flux:switch wire:model="sap_sync_quantity" />
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button wire:click="$set('showIntegrationModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveSapConfig" variant="primary">Save</flux:button>
            </div>
        @endif
    </flux:modal>
    @endif

    {{-- Security --}}
    @if ($section === 'security')
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Security</flux:heading>
            </div>

            <form wire:submit="saveSecurity" class="space-y-5 p-6">
                <flux:input wire:model="min_password_length" type="number" min="6" max="64"
                    label="Minimum password length" />
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <div>
                        <flux:label>Require two-factor authentication</flux:label>
                        <flux:text size="sm" class="text-xs">Force all staff to set up 2FA.</flux:text>
                    </div>
                    <flux:switch wire:model="require_two_factor" />
                </div>
                <flux:input wire:model="session_lifetime" type="number" min="5" max="43200"
                    label="Session lifetime (minutes)" />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Maintenance --}}
    @if ($section === 'maintenance')
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Maintenance</flux:heading>
            </div>

            <form wire:submit="saveMaintenance" class="space-y-5 p-6">
                <div
                    class="flex items-center justify-between rounded-md border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div>
                        <flux:label>Maintenance mode</flux:label>
                        <flux:text size="sm" class="text-xs">Show a maintenance message to visitors. Admin stays
                            accessible.</flux:text>
                        @if ($maintenance_mode)
                            <flux:badge color="yellow" size="sm" class="mt-2">Maintenance mode is ON</flux:badge>
                        @else
                            <flux:badge color="green" size="sm" class="mt-2">Store is live</flux:badge>
                        @endif
                    </div>
                    <flux:switch wire:model.live="maintenance_mode" />
                </div>
                <flux:textarea wire:model="maintenance_message" label="Maintenance message" rows="3" required />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

</x-admin.settings-shell>
