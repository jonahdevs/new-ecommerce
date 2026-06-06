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

new #[Layout('layouts::app')] #[Title('System settings — Admin')] class extends Component {
    #[Url]
    public string $section = 'email';

    // ==================================================
    // EMAIL & SMS
    // ==================================================
    public string $from_name = '';

    public string $from_address = '';

    public string $mail_driver = 'smtp';

    public string $sms_provider = 'none';

    public string $sms_sender_id = '';

    // ==================================================
    // INTEGRATIONS
    // ==================================================
    public bool $google_login_enabled = false;

    public string $google_maps_api_key = '';

    public string $map_provider = 'leaflet';

    public string $recaptcha_site_key = '';

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

    public string $kra_business_pin = '';

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

    public function mount(EmailSettings $email, IntegrationSettings $integrations, SecuritySettings $security, MaintenanceSettings $maintenance): void
    {
        $this->from_name = $email->from_name;
        $this->from_address = $email->from_address;
        $this->mail_driver = $email->mail_driver;
        $this->sms_provider = $email->sms_provider;
        $this->sms_sender_id = $email->sms_sender_id;

        $this->google_login_enabled = $integrations->google_login_enabled;
        $this->google_maps_api_key = $integrations->google_maps_api_key;
        $this->map_provider = $integrations->map_provider;
        $this->recaptcha_site_key = $integrations->recaptcha_site_key;

        $this->sap_enabled = $integrations->sap_enabled;
        $this->sap_auto_sync_orders = $integrations->sap_auto_sync_orders;
        $this->sap_sync_price = $integrations->sap_sync_price;
        $this->sap_sync_quantity = $integrations->sap_sync_quantity;
        $this->sap_base_url = $integrations->sap_base_url ?? '';
        $this->sap_api_key = $integrations->sap_api_key ?? '';
        $this->sap_webhook_secret = $integrations->sap_webhook_secret ?? '';
        $this->kra_business_pin = $integrations->kra_business_pin ?? '';

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

        $settings
            ->fill([
                'from_name' => $this->from_name,
                'from_address' => $this->from_address,
                'mail_driver' => $this->mail_driver,
                'sms_provider' => $this->sms_provider,
                'sms_sender_id' => $this->sms_sender_id,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Email & SMS settings updated.', variant: 'success');
    }

    public function saveIntegrations(IntegrationSettings $settings): void
    {
        $this->validate([
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            'map_provider' => ['required', 'in:leaflet,google'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings
            ->fill([
                'google_login_enabled' => $this->google_login_enabled,
                'google_maps_api_key' => $this->google_maps_api_key,
                'map_provider' => $this->map_provider,
                'recaptcha_site_key' => $this->recaptcha_site_key,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'Integration settings updated.', variant: 'success');
    }

    public function saveSap(IntegrationSettings $settings): void
    {
        $this->validate([
            'sap_base_url' => ['nullable', 'url', 'max:255'],
            'sap_api_key' => ['nullable', 'string', 'max:255'],
            'sap_webhook_secret' => ['nullable', 'string', 'max:255'],
            'kra_business_pin' => ['nullable', 'string', 'max:50'],
        ]);

        $settings
            ->fill([
                'sap_enabled' => $this->sap_enabled,
                'sap_auto_sync_orders' => $this->sap_auto_sync_orders,
                'sap_sync_price' => $this->sap_sync_price,
                'sap_sync_quantity' => $this->sap_sync_quantity,
                'sap_base_url' => $this->sap_base_url ?: null,
                'sap_api_key' => $this->sap_api_key ?: null,
                'sap_webhook_secret' => $this->sap_webhook_secret ?: null,
                'kra_business_pin' => $this->kra_business_pin ?: null,
            ])
            ->save();

        Flux::toast(heading: 'Saved', text: 'SAP / KRA settings updated.', variant: 'success');
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
        <flux:card>
            <flux:heading>Email & SMS</flux:heading>
            <flux:subheading>How transactional messages are sent. Secrets live in your environment file.
            </flux:subheading>

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
                <flux:separator />

                <flux:text size="sm" class="font-medium text-zinc-500">Maps</flux:text>
                <flux:input wire:model.live="google_maps_api_key" label="Google Maps API key" placeholder="AIza…" />
                <flux:radio.group wire:model.live="map_provider" label="Address map provider"
                    description="Used on the storefront checkout and saved addresses. Google requires an API key above."
                    variant="cards" class="max-sm:flex-col">
                    <flux:radio value="leaflet" icon="map" label="OpenStreetMap (Leaflet)"
                        description="Free, no API key needed" />
                    <flux:radio value="google" icon="globe-alt" label="Google Maps"
                        description="Better local data for East Africa, requires API key"
                        :disabled="!$google_maps_api_key && !config('services.google.maps_api_key')" />
                </flux:radio.group>
                @if ($map_provider === 'google' && !$google_maps_api_key && !config('services.google.maps_api_key'))
                    <flux:error>Add a Google Maps API key above or set GOOGLE_MAPS_API_KEY in your .env to use Google
                        Maps.</flux:error>
                @endif


                <flux:separator />
                <flux:input wire:model="recaptcha_site_key" label="reCAPTCHA site key" />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- SAP / KRA --}}
    @if ($section === 'sap')
        <flux:card>
            <flux:heading>SAP / KRA integration</flux:heading>
            <flux:subheading>Controls what the SAP middleware is allowed to do. Connection credentials (base URL, API key, webhook secret) are set in your <code class="rounded bg-zinc-100 px-1 font-mono text-xs dark:bg-zinc-800">.env</code> file.</flux:subheading>

            <form wire:submit="saveSap" class="mt-6 space-y-4">

                <div class="flex items-center justify-between rounded-md border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div>
                        <flux:label>Enable SAP sync</flux:label>
                        <flux:text size="sm" class="text-xs">When off, no orders are sent to SAP and the product sync endpoint returns 503.</flux:text>
                        @if ($sap_enabled)
                            <flux:badge color="green" size="sm" class="mt-2">SAP sync is active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" class="mt-2">SAP sync is disabled</flux:badge>
                        @endif
                    </div>
                    <flux:switch wire:model.live="sap_enabled" />
                </div>

                @if ($sap_enabled)
                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <div>
                            <flux:label>Auto-sync orders on payment confirmation</flux:label>
                            <flux:text size="sm" class="text-xs">Disable to pause order sync without turning off the integration entirely. Use <code class="font-mono">php artisan sap:resync --failed</code> to sync manually.</flux:text>
                        </div>
                        <flux:switch wire:model="sap_auto_sync_orders" />
                    </div>

                    <flux:separator />
                    <flux:text size="sm" class="font-medium text-zinc-500">Product sync permissions</flux:text>

                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <div>
                            <flux:label>Allow SAP to update prices</flux:label>
                            <flux:text size="sm" class="text-xs">SAP can overwrite product sale prices via the sync endpoint.</flux:text>
                        </div>
                        <flux:switch wire:model="sap_sync_price" />
                    </div>

                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <div>
                            <flux:label>Allow SAP to update stock quantities</flux:label>
                            <flux:text size="sm" class="text-xs">SAP can overwrite stock levels and status via the sync endpoint.</flux:text>
                        </div>
                        <flux:switch wire:model="sap_sync_quantity" />
                    </div>

                    <flux:separator />
                    <flux:text size="sm" class="font-medium text-zinc-500">Connection credentials</flux:text>
                    <flux:callout icon="information-circle" color="blue">
                        Leave a field blank to use the value from your <code class="font-mono text-xs">.env</code> file. Values set here take priority.
                    </flux:callout>

                    <flux:input
                        wire:model="sap_base_url"
                        label="SAP base URL"
                        placeholder="{{ config('sap.base_url') ?: 'http://your-sap-server:85' }}"
                        description="Override SAP_BASE_URL from .env"
                    />

                    <flux:input
                        wire:model="sap_api_key"
                        type="password"
                        label="API key"
                        placeholder="{{ config('sap.api_key') ? '••••••••  (set in .env)' : 'Not set in .env' }}"
                        description="Override SAP_API_KEY from .env"
                        viewable
                    />

                    <flux:input
                        wire:model="sap_webhook_secret"
                        type="password"
                        label="Webhook secret"
                        placeholder="{{ config('sap.webhook_secret') ? '••••••••  (set in .env)' : 'Not set in .env' }}"
                        description="Override SAP_WEBHOOK_SECRET from .env"
                        viewable
                    />

                    <flux:input
                        wire:model="kra_business_pin"
                        label="KRA business PIN"
                        placeholder="{{ config('sap.business_pin') ?: 'e.g. P051234567X' }}"
                        description="Override KRA_BUSINESS_PIN from .env"
                    />
                @endif

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
        <flux:card>
            <flux:heading>Maintenance</flux:heading>
            <flux:subheading>Storefront availability.</flux:subheading>

            <form wire:submit="saveMaintenance" class="mt-6 space-y-5">
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
