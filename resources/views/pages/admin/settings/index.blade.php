<?php

use App\Settings\GeneralSettings;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Settings — Admin')] class extends Component {
    #[Url]
    public string $tab = 'general';

    public string $site_name = '';
    public string $site_tagline = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $address = '';
    public string $currency = '';
    public string $timezone = '';
    public bool $maintenance_mode = false;

    public function mount(GeneralSettings $settings): void
    {
        $this->site_name = $settings->site_name;
        $this->site_tagline = $settings->site_tagline;
        $this->contact_email = $settings->contact_email;
        $this->contact_phone = $settings->contact_phone;
        $this->address = $settings->address;
        $this->currency = $settings->currency;
        $this->timezone = $settings->timezone;
        $this->maintenance_mode = $settings->maintenance_mode;
    }

    public function saveGeneral(GeneralSettings $settings): void
    {
        $this->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $settings->site_name = $this->site_name;
        $settings->site_tagline = $this->site_tagline;
        $settings->contact_email = $this->contact_email;
        $settings->contact_phone = $this->contact_phone;
        $settings->address = $this->address;
        $settings->save();

        Flux::toast(heading: 'Settings saved', text: 'General settings have been updated.', variant: 'success');
    }

    public function saveLocalisation(GeneralSettings $settings): void
    {
        $this->validate([
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $settings->currency = strtoupper($this->currency);
        $settings->timezone = $this->timezone;
        $settings->save();

        Flux::toast(heading: 'Settings saved', text: 'Localisation settings have been updated.', variant: 'success');
    }

    public function toggleMaintenance(GeneralSettings $settings): void
    {
        $settings->maintenance_mode = ! $settings->maintenance_mode;
        $settings->save();

        $this->maintenance_mode = $settings->maintenance_mode;

        Flux::toast(
            heading: $this->maintenance_mode ? 'Maintenance mode on' : 'Maintenance mode off',
            text: $this->maintenance_mode
                ? 'The storefront is now in maintenance mode.'
                : 'The storefront is now live.',
            variant: $this->maintenance_mode ? 'warning' : 'success',
        );
    }

    #[Computed]
    public function timezones(): array
    {
        return \DateTimeZone::listIdentifiers();
    }
}; ?>

<div>
    <div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl" class="mt-2">Settings</flux:heading>
        <flux:subheading>Manage your store configuration.</flux:subheading>
    </div>

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Tab nav --}}
        <flux:navlist aria-label="Settings sections" class="lg:w-52 shrink-0">
            <flux:navlist.item
                wire:click="$set('tab', 'general')"
                :current="$tab === 'general'"
                icon="information-circle"
                class="cursor-pointer">
                General
            </flux:navlist.item>
            <flux:navlist.item
                wire:click="$set('tab', 'localisation')"
                :current="$tab === 'localisation'"
                icon="globe-alt"
                class="cursor-pointer">
                Localisation
            </flux:navlist.item>
            <flux:navlist.item
                wire:click="$set('tab', 'advanced')"
                :current="$tab === 'advanced'"
                icon="shield-exclamation"
                class="cursor-pointer">
                Advanced
            </flux:navlist.item>
        </flux:navlist>

        <flux:separator class="lg:hidden" />

        <div class="flex-1 min-w-0">

            {{-- General --}}
            @if ($tab === 'general')
                <flux:card class="max-w-2xl">
                    <flux:heading>General</flux:heading>
                    <flux:subheading>Basic information about your store.</flux:subheading>

                    <form wire:submit="saveGeneral" class="mt-6 space-y-5">
                        <flux:input
                            wire:model="site_name"
                            label="Store name"
                            placeholder="e.g. Sheffield Store"
                            required />

                        <flux:input
                            wire:model="site_tagline"
                            label="Tagline"
                            placeholder="e.g. Quality products, delivered fast" />

                        <flux:separator />

                        <flux:input
                            wire:model="contact_email"
                            label="Contact email"
                            type="email"
                            placeholder="hello@yourstore.com" />

                        <flux:input
                            wire:model="contact_phone"
                            label="Contact phone"
                            placeholder="+254 700 000 000" />

                        <flux:textarea
                            wire:model="address"
                            label="Address"
                            placeholder="123 Main St, Nairobi, Kenya"
                            rows="3" />

                        <div class="flex justify-end pt-2">
                            <flux:button type="submit" variant="primary">Save changes</flux:button>
                        </div>
                    </form>
                </flux:card>
            @endif

            {{-- Localisation --}}
            @if ($tab === 'localisation')
                <flux:card class="max-w-2xl">
                    <flux:heading>Localisation</flux:heading>
                    <flux:subheading>Currency and timezone for your store.</flux:subheading>

                    <form wire:submit="saveLocalisation" class="mt-6 space-y-5">
                        <flux:field>
                            <flux:label>Currency</flux:label>
                            <flux:description>ISO 4217 currency code used across the store.</flux:description>
                            <flux:select wire:model="currency" class="w-48">
                                <flux:select.option value="KES">KES — Kenyan Shilling</flux:select.option>
                                <flux:select.option value="USD">USD — US Dollar</flux:select.option>
                                <flux:select.option value="EUR">EUR — Euro</flux:select.option>
                                <flux:select.option value="GBP">GBP — British Pound</flux:select.option>
                                <flux:select.option value="UGX">UGX — Ugandan Shilling</flux:select.option>
                                <flux:select.option value="TZS">TZS — Tanzanian Shilling</flux:select.option>
                                <flux:select.option value="ZAR">ZAR — South African Rand</flux:select.option>
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Timezone</flux:label>
                            <flux:description>Used for order timestamps and reports.</flux:description>
                            <flux:select wire:model="timezone" searchable class="w-72">
                                @foreach ($this->timezones as $tz)
                                    <flux:select.option :value="$tz">{{ $tz }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <div class="flex justify-end pt-2">
                            <flux:button type="submit" variant="primary">Save changes</flux:button>
                        </div>
                    </form>
                </flux:card>
            @endif

            {{-- Advanced --}}
            @if ($tab === 'advanced')
                <flux:card class="max-w-2xl">
                    <flux:heading>Advanced</flux:heading>
                    <flux:subheading>Danger zone — changes here affect storefront availability.</flux:subheading>

                    <div class="mt-6 space-y-6">
                        <div class="flex items-start justify-between gap-6 rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                            <div>
                                <flux:heading size="sm">Maintenance mode</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    When enabled, the storefront will show a maintenance message to visitors. The admin panel remains accessible.
                                </flux:text>
                                @if ($maintenance_mode)
                                    <flux:badge color="yellow" size="sm" class="mt-3">Maintenance mode is ON</flux:badge>
                                @else
                                    <flux:badge color="green" size="sm" class="mt-3">Store is live</flux:badge>
                                @endif
                            </div>
                            <flux:button
                                wire:click="toggleMaintenance"
                                wire:confirm="{{ $maintenance_mode ? 'Disable maintenance mode and make the store live?' : 'Enable maintenance mode? Customers will not be able to access the store.' }}"
                                :variant="$maintenance_mode ? 'primary' : 'danger'"
                                class="shrink-0">
                                {{ $maintenance_mode ? 'Disable maintenance' : 'Enable maintenance' }}
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endif

        </div>
    </div>
</div>
