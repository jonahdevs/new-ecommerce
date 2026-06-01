<?php

use App\Settings\EmailSettings;
use App\Settings\IntegrationSettings;
use App\Settings\MaintenanceSettings;
use App\Settings\SecuritySettings;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('System settings — Admin')] class extends Component
{
    #[Url]
    public string $section = 'email';

    // ─── Email & SMS ───────────────────────────────────────────────────────────
    public string $from_name = '';

    public string $from_address = '';

    public string $mail_driver = 'smtp';

    public string $sms_provider = 'none';

    public string $sms_sender_id = '';

    // ─── Integrations ──────────────────────────────────────────────────────────
    public bool $google_login_enabled = false;

    public bool $facebook_login_enabled = false;

    public string $google_maps_api_key = '';

    public string $recaptcha_site_key = '';

    // ─── Security ──────────────────────────────────────────────────────────────
    public int $min_password_length = 8;

    public bool $require_two_factor = false;

    public int $session_lifetime = 120;

    // ─── Maintenance ───────────────────────────────────────────────────────────
    public bool $maintenance_mode = false;

    public string $maintenance_message = '';

    public function mount(EmailSettings $email, IntegrationSettings $integrations, SecuritySettings $security, MaintenanceSettings $maintenance): void
    {
        $this->from_name = $email->from_name;
        $this->from_address = $email->from_address;
        $this->mail_driver = $email->mail_driver;
        $this->sms_provider = $email->sms_provider;
        $this->sms_sender_id = $email->sms_sender_id;

        $this->google_login_enabled = $integrations->google_login_enabled;
        $this->facebook_login_enabled = $integrations->facebook_login_enabled;
        $this->google_maps_api_key = $integrations->google_maps_api_key;
        $this->recaptcha_site_key = $integrations->recaptcha_site_key;

        $this->min_password_length = $security->min_password_length;
        $this->require_two_factor = $security->require_two_factor;
        $this->session_lifetime = $security->session_lifetime;

        $this->maintenance_mode = $maintenance->maintenance_mode;
        $this->maintenance_message = $maintenance->maintenance_message;
    }

    public function saveEmail(EmailSettings $settings): void
    {
        $this->validate([
            'from_name' => ['required', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'mail_driver' => ['required', 'in:smtp,ses,mailgun,postmark,log'],
            'sms_provider' => ['required', 'in:none,africastalking,twilio'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
        ]);

        $settings->fill([
            'from_name' => $this->from_name,
            'from_address' => $this->from_address,
            'mail_driver' => $this->mail_driver,
            'sms_provider' => $this->sms_provider,
            'sms_sender_id' => $this->sms_sender_id,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Email & SMS settings updated.', variant: 'success');
    }

    public function saveIntegrations(IntegrationSettings $settings): void
    {
        $this->validate([
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings->fill([
            'google_login_enabled' => $this->google_login_enabled,
            'facebook_login_enabled' => $this->facebook_login_enabled,
            'google_maps_api_key' => $this->google_maps_api_key,
            'recaptcha_site_key' => $this->recaptcha_site_key,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Integration settings updated.', variant: 'success');
    }

    public function saveSecurity(SecuritySettings $settings): void
    {
        $this->validate([
            'min_password_length' => ['required', 'integer', 'min:6', 'max:64'],
            'session_lifetime' => ['required', 'integer', 'min:5', 'max:43200'],
        ]);

        $settings->fill([
            'min_password_length' => (int) $this->min_password_length,
            'require_two_factor' => $this->require_two_factor,
            'session_lifetime' => (int) $this->session_lifetime,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Security settings updated.', variant: 'success');
    }

    public function saveMaintenance(MaintenanceSettings $settings): void
    {
        $this->validate([
            'maintenance_message' => ['required', 'string', 'max:500'],
        ]);

        $settings->fill([
            'maintenance_mode' => $this->maintenance_mode,
            'maintenance_message' => $this->maintenance_message,
        ])->save();

        Flux::toast(
            heading: $this->maintenance_mode ? 'Maintenance mode on' : 'Maintenance mode off',
            text: $this->maintenance_mode ? 'The storefront now shows a maintenance message.' : 'The storefront is live.',
            variant: $this->maintenance_mode ? 'warning' : 'success',
        );
    }

}; ?>

<x-admin.settings-shell tab="system" :section="$section">

    {{-- Email & SMS --}}
    @if ($section === 'email')
        <flux:card>
            <flux:heading>Email & SMS</flux:heading>
            <flux:subheading>How transactional messages are sent. Secrets live in your environment file.</flux:subheading>

            <form wire:submit="saveEmail" class="mt-6 space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="from_name" label="From name" required />
                    <flux:input wire:model="from_address" type="email" label="From address" required />
                </div>
                <flux:select wire:model="mail_driver" label="Mail driver">
                    <flux:select.option value="smtp">SMTP</flux:select.option>
                    <flux:select.option value="ses">Amazon SES</flux:select.option>
                    <flux:select.option value="mailgun">Mailgun</flux:select.option>
                    <flux:select.option value="postmark">Postmark</flux:select.option>
                    <flux:select.option value="log">Log (dev)</flux:select.option>
                </flux:select>

                <flux:separator />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="sms_provider" label="SMS provider">
                        <flux:select.option value="none">None</flux:select.option>
                        <flux:select.option value="africastalking">Africa's Talking</flux:select.option>
                        <flux:select.option value="twilio">Twilio</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="sms_sender_id" label="SMS sender ID" placeholder="e.g. SHEFFIELD" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Integrations --}}
    @if ($section === 'integrations')
        <flux:card>
            <flux:heading>Integrations</flux:heading>
            <flux:subheading>Third-party services. Provider secrets stay in your environment file.</flux:subheading>

            <form wire:submit="saveIntegrations" class="mt-6 space-y-5">
                <flux:text size="sm" class="font-medium text-zinc-500">Social login</flux:text>
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Sign in with Google</flux:label>
                    <flux:switch wire:model="google_login_enabled" />
                </div>
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Sign in with Facebook</flux:label>
                    <flux:switch wire:model="facebook_login_enabled" />
                </div>

                <flux:separator />
                <flux:input wire:model="google_maps_api_key" label="Google Maps API key" placeholder="AIza…" />
                <flux:input wire:model="recaptcha_site_key" label="reCAPTCHA site key" />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Security --}}
    @if ($section === 'security')
        <flux:card>
            <flux:heading>Security</flux:heading>
            <flux:subheading>Authentication and session policy.</flux:subheading>

            <form wire:submit="saveSecurity" class="mt-6 space-y-5">
                <flux:input wire:model="min_password_length" type="number" min="6" max="64" label="Minimum password length" />
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <div>
                        <flux:label>Require two-factor authentication</flux:label>
                        <flux:text size="sm" class="text-xs">Force all staff to set up 2FA.</flux:text>
                    </div>
                    <flux:switch wire:model="require_two_factor" />
                </div>
                <flux:input wire:model="session_lifetime" type="number" min="5" max="43200" label="Session lifetime (minutes)" />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Maintenance --}}
    @if ($section === 'maintenance')
        <flux:card>
            <flux:heading>Maintenance</flux:heading>
            <flux:subheading>Storefront availability.</flux:subheading>

            <form wire:submit="saveMaintenance" class="mt-6 space-y-5">
                <div class="flex items-center justify-between rounded-md border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div>
                        <flux:label>Maintenance mode</flux:label>
                        <flux:text size="sm" class="text-xs">Show a maintenance message to visitors. Admin stays accessible.</flux:text>
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
