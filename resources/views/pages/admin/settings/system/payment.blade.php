<?php

use App\Settings\PaymentSettings;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Payment Settings')] class extends Component {
    public string $active_gateway = 'custom';

    // Pesawise
    public bool $pesawise_mode_production = false;
    public string $pesawise_api_key = '';
    public string $pesawise_api_secret = '';
    public string $pesawise_account_number = '';
    public string $pesawise_webhook_secret = '';

    // Pesapal
    public bool $pesapal_mode_production = false;
    public string $pesapal_consumer_key = '';
    public string $pesapal_consumer_secret = '';
    public string $pesapal_webhook_secret = '';
    public string $pesapal_ipn_id = '';

    // PayPal
    public bool $paypal_mode_production = false;
    public string $paypal_client_id = '';
    public string $paypal_client_secret = '';
    public string $paypal_webhook_id = '';

    // Custom
    // Stripe
    public bool $stripe_mode_production = false;
    public ?string $stripe_public_key;
    public ?string $stripe_secret_key;
    public ?string $stripe_webhook_secret;

    // M-Pesa Daraja
    public bool $mpesa_mode_production = false;
    public ?string $mpesa_consumer_key;
    public ?string $mpesa_consumer_secret;
    public ?string $mpesa_shortcode;
    public ?string $mpesa_passkey;
    public ?string $mpesa_callback_url;

    public function mount(PaymentSettings $settings): void
    {
        $this->active_gateway = $settings->active_gateway;

        $this->pesawise_mode_production = $settings->pesawise_mode_production;
        $this->pesawise_api_key = $settings->pesawise_api_key ?? '';
        $this->pesawise_api_secret = $settings->pesawise_api_secret ?? '';
        $this->pesawise_account_number = $settings->pesawise_account_number ?? '';
        $this->pesawise_webhook_secret = $settings->pesawise_webhook_secret ?? '';

        $this->pesapal_mode_production = $settings->pesapal_mode_production;
        $this->pesapal_consumer_key = $settings->pesapal_consumer_key ?? '';
        $this->pesapal_consumer_secret = $settings->pesapal_consumer_secret ?? '';
        $this->pesapal_webhook_secret = $settings->pesapal_webhook_secret ?? '';
        $this->pesapal_ipn_id = $settings->pesapal_ipn_id ?? '';

        $this->paypal_mode_production = $settings->paypal_mode_production;
        $this->paypal_client_id = $settings->paypal_client_id ?? '';
        $this->paypal_client_secret = $settings->paypal_client_secret ?? '';
        $this->paypal_webhook_id = $settings->paypal_webhook_id ?? '';

        $this->stripe_mode_production = $settings->stripe_mode_production;
        $this->stripe_public_key = $settings->stripe_public_key ?? '';
        $this->stripe_secret_key = $settings->stripe_secret_key ?? '';
        $this->stripe_webhook_secret = $settings->stripe_webhook_secret ?? '';

        $this->mpesa_mode_production = $settings->mpesa_mode_production;
        $this->mpesa_consumer_key = $settings->mpesa_consumer_key ?? '';
        $this->mpesa_consumer_secret = $settings->mpesa_consumer_secret ?? '';
        $this->mpesa_shortcode = $settings->mpesa_shortcode ?? '';
        $this->mpesa_passkey = $settings->mpesa_passkey ?? '';
        $this->mpesa_callback_url = $settings->mpesa_callback_url ?? '';
    }

    // Activate a gateway — deactivates all others
    public function activate(string $gateway, PaymentSettings $settings): void
    {
        $settings->active_gateway = $gateway;
        $settings->save();
        $this->active_gateway = $gateway;
        $this->dispatch('notify', variant: 'success', message: ucfirst($gateway) . ' is now the active payment gateway.');
    }

    // Save credentials per gateway
    public function savePesawise(PaymentSettings $settings): void
    {
        $this->validateOnly('pesawise_*', [
            'pesawise_api_key' => ['required', 'string', 'max:255'],
            'pesawise_api_secret' => ['required', 'string', 'max:255'],
            'pesawise_account_number' => ['required', 'string', 'max:50'],
            'pesawise_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $settings->pesawise_mode_production = $this->pesawise_mode_production;
            $settings->pesawise_api_key = $this->pesawise_api_key;
            $settings->pesawise_api_secret = $this->pesawise_api_secret;
            $settings->pesawise_account_number = $this->pesawise_account_number;
            $settings->pesawise_webhook_secret = $this->pesawise_webhook_secret ?: null;
            $settings->save();

            $this->modal('pesawise-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'Pesawise credentials saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save Pesawise credentials.');
        }
    }

    public function savePesapal(PaymentSettings $settings): void
    {
        $this->validateOnly('pesapal_*', [
            'pesapal_consumer_key' => ['required', 'string', 'max:255'],
            'pesapal_consumer_secret' => ['required', 'string', 'max:255'],
            'pesapal_webhook_secret' => ['nullable', 'string', 'max:255'],
            'pesapal_ipn_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $settings->pesapal_mode_production = $this->pesapal_mode_production;
            $settings->pesapal_consumer_key = $this->pesapal_consumer_key;
            $settings->pesapal_consumer_secret = $this->pesapal_consumer_secret;
            $settings->pesapal_webhook_secret = $this->pesapal_webhook_secret ?: null;
            $settings->pesapal_ipn_id = $this->pesapal_ipn_id ?: null;
            $settings->save();

            $this->modal('pesapal-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'Pesapal credentials saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save Pesapal credentials.');
        }
    }

    public function savePaypal(PaymentSettings $settings): void
    {
        $this->validateOnly('paypal_*', [
            'paypal_client_id' => ['required', 'string', 'max:255'],
            'paypal_client_secret' => ['required', 'string', 'max:255'],
            'paypal_webhook_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $settings->paypal_mode_production = $this->paypal_mode_production;
            $settings->paypal_client_id = $this->paypal_client_id;
            $settings->paypal_client_secret = $this->paypal_client_secret;
            $settings->paypal_webhook_id = $this->paypal_webhook_id ?: null;
            $settings->save();

            $this->modal('paypal-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'PayPal credentials saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save PayPal credentials.');
        }
    }

    public function saveCustom(PaymentSettings $settings): void
    {
        $this->validate([
            'stripe_public_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'mpesa_consumer_key' => ['nullable', 'string', 'max:255'],
            'mpesa_consumer_secret' => ['nullable', 'string', 'max:255'],
            'mpesa_shortcode' => ['nullable', 'string', 'max:20'],
            'mpesa_passkey' => ['nullable', 'string', 'max:255'],
            'mpesa_callback_url' => ['nullable', 'url', 'max:255'],
        ]);

        try {
            $settings->stripe_mode_production = $this->stripe_mode_production;
            $settings->stripe_public_key = $this->stripe_public_key ?: null;
            $settings->stripe_secret_key = $this->stripe_secret_key ?: null;
            $settings->stripe_webhook_secret = $this->stripe_webhook_secret ?: null;

            $settings->mpesa_mode_production = $this->mpesa_mode_production;
            $settings->mpesa_consumer_key = $this->mpesa_consumer_key ?: null;
            $settings->mpesa_consumer_secret = $this->mpesa_consumer_secret ?: null;
            $settings->mpesa_shortcode = $this->mpesa_shortcode ?: null;
            $settings->mpesa_passkey = $this->mpesa_passkey ?: null;
            $settings->mpesa_callback_url = $this->mpesa_callback_url ?: null;

            $settings->save();

            $this->modal('custom-config')->close();
            $this->dispatch('notify', variant: 'success', message: 'Custom gateway credentials saved.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save credentials.');
        }
    }
}; ?>

<x-pages::admin.settings.layout :heading="__('Payment Settings')" :subheading="__('Configure and activate your payment gateway')">

    {{-- Gateway Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- ── Pesawise ── --}}
        <flux:card class="p-0">
            <div class="flex items-start justify-between gap-3 p-5">
                <div>
                    <flux:heading size="lg">Pesawise</flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        Accept M-Pesa, cards and more via Pesawise.
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" :variant="$pesawise_mode_production ? 'solid' : 'ghost'"
                        :color="$pesawise_mode_production ? 'green' : 'zinc'">
                        {{ $pesawise_mode_production ? 'Live' : 'Sandbox' }}
                    </flux:badge>
                    {{-- Toggle active --}}
                    <flux:switch wire:key="switch-pesawise-{{ $active_gateway }}"
                        :checked="$active_gateway === 'pesawise'" wire:click="activate('pesawise')"
                        wire:confirm="{{ $active_gateway == 'pesawise' ? '' : 'Set Pesawise as the active payment gateway?' }}" />
                </div>
            </div>

            <flux:separator />

            <div class="flex items-center justify-between px-5 py-2">
                @if ($pesawise_api_key)
                    <flux:badge size="sm" color="green" variant="soft" icon="check-circle">
                        Configured
                    </flux:badge>
                @else
                    <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">
                        Not configured
                    </flux:badge>
                @endif

                <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                    x-on:click="$flux.modal('pesawise-config').show()" tooltip="Configure Pesawise"
                    class="cursor-pointer" />
            </div>
        </flux:card>

        {{-- ── Pesapal ── --}}
        <flux:card class="p-0">
            <div class="flex items-start justify-between gap-3 p-5">
                <div>
                    <flux:heading size="lg">Pesapal</flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        Accept M-Pesa, cards and more via Pesapal.
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" :variant="$pesapal_mode_production ? 'solid' : 'ghost'"
                        :color="$pesapal_mode_production ? 'green' : 'zinc'">
                        {{ $pesapal_mode_production ? 'Live' : 'Sandbox' }}
                    </flux:badge>
                    <flux:switch wire:key="switch-pesapal-{{ $active_gateway }}"
                        :checked="$active_gateway === 'pesapal'" wire:click="activate('pesapal')"
                        wire:confirm="{{ $active_gateway == 'pesapal' ? '' : 'Set Pesapal as the active payment gateway?' }}" />
                </div>
            </div>

            <flux:separator />

            <div class="flex items-center justify-between px-5 py-2">
                @if ($pesapal_consumer_key)
                    <flux:badge size="sm" color="green" variant="soft" icon="check-circle">
                        Configured
                    </flux:badge>
                @else
                    <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">
                        Not configured
                    </flux:badge>
                @endif

                <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                    x-on:click="$flux.modal('pesapal-config').show()" tooltip="Configure Pesapal"
                    class="cursor-pointer" />
            </div>
        </flux:card>

        {{-- ── PayPal ── --}}
        <flux:card class="p-0">
            <div class="flex items-start justify-between gap-3 p-5">
                <div>
                    <flux:heading size="lg">PayPal</flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        Accept international payments via PayPal.
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" :variant="$paypal_mode_production ? 'solid' : 'ghost'"
                        :color="$paypal_mode_production ? 'green' : 'zinc'">
                        {{ $paypal_mode_production ? 'Live' : 'Sandbox' }}
                    </flux:badge>
                    <flux:switch wire:key="switch-paypal-{{ $active_gateway }}"
                        :checked="$active_gateway === 'paypal'" wire:click="activate('paypal')"
                        wire:confirm="{{ $active_gateway == 'paypal' ? '' : 'Set PayPal as the active payment gateway?' }}" />
                </div>
            </div>

            <flux:separator />

            <div class="flex items-center justify-between px-5 py-2">
                @if ($paypal_client_id)
                    <flux:badge size="sm" color="green" variant="soft" icon="check-circle">
                        Configured
                    </flux:badge>
                @else
                    <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">
                        Not configured
                    </flux:badge>
                @endif

                <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                    x-on:click="$flux.modal('paypal-config').show()" tooltip="Configure PayPal"
                    class="cursor-pointer" />
            </div>
        </flux:card>

        {{-- ── Custom ── --}}
        <flux:card class="p-0">
            <div class="flex items-start justify-between gap-3 p-5">
                <div>
                    <flux:heading size="lg">Custom</flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        Stripe for card payments + M-Pesa Daraja for mobile payments.
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    @if ($stripe_public_key && $mpesa_consumer_key)
                        <flux:badge size="sm" color="green" variant="soft" icon="check-circle">Configured
                        </flux:badge>
                    @elseif ($stripe_public_key || $mpesa_consumer_key)
                        <flux:badge size="sm" color="blue" variant="soft" icon="information-circle">Partially
                            configured</flux:badge>
                    @else
                        <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">Not
                            configured</flux:badge>
                    @endif
                    <flux:switch wire:key="switch-custom-{{ $active_gateway }}"
                        :checked="$active_gateway === 'custom'" wire:click="activate('custom')"
                        wire:confirm="{{ $active_gateway == 'customer' ? '' : 'Set Custom as the active payment gateway?' }}" />
                </div>
            </div>

            <flux:separator />

            <div class="flex items-center justify-between px-5 py-2">
                @if ($stripe_public_key && $mpesa_consumer_key)
                    <flux:badge size="sm" color="green" variant="soft" icon="check-circle">Configured
                    </flux:badge>
                @elseif ($stripe_public_key || $mpesa_consumer_key)
                    <flux:badge size="sm" color="blue" variant="soft" icon="information-circle">Partially
                        configured</flux:badge>
                @else
                    <flux:badge size="sm" color="yellow" variant="soft" icon="exclamation-triangle">Not
                        configured
                    </flux:badge>
                @endif

                <flux:button icon="cog-6-tooth" variant="ghost" size="sm" icon-variant="outline"
                    x-on:click="$flux.modal('custom-config').show()" tooltip="Configure Custom Gateway"
                    class="cursor-pointer" />
            </div>
        </flux:card>
    </div>

    {{-- ── Modals ── --}}

    {{-- Pesawise Config --}}
    <flux:modal name="pesawise-config" class="md:w-120">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Pesawise Configuration</flux:heading>
                <flux:subheading>Enter your Pesawise API credentials</flux:subheading>
            </div>

            <form wire:submit="savePesawise" class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div>
                        <flux:text class="text-sm font-medium">Production Mode</flux:text>
                        <flux:text class="text-xs text-zinc-400">Enable for live transactions</flux:text>
                    </div>
                    <flux:switch wire:model="pesawise_mode_production" />
                </div>

                <flux:input label="API Key" wire:model="pesawise_api_key" placeholder="pk_..." />

                <flux:input label="API Secret" wire:model="pesawise_api_secret" type="password"
                    placeholder="sk_..." />

                <flux:input label="Account Number" wire:model="pesawise_account_number"
                    placeholder="e.g. 1234567890" />

                <flux:input label="Webhook Secret (optional)" wire:model="pesawise_webhook_secret" type="password"
                    placeholder="whsec_..." />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('pesawise-config').close()"
                        class="cursor-pointer">Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Pesapal Config --}}
    <flux:modal name="pesapal-config" class="md:w-120">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Pesapal Configuration</flux:heading>
                <flux:subheading>Enter your Pesapal API credentials</flux:subheading>
            </div>

            <form wire:submit="savePesapal" class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div>
                        <flux:text class="text-sm font-medium">Production Mode</flux:text>
                        <flux:text class="text-xs text-zinc-400">Enable for live transactions</flux:text>
                    </div>
                    <flux:switch wire:model="pesapal_mode_production" />
                </div>

                <flux:input label="Consumer Key" wire:model="pesapal_consumer_key"
                    placeholder="Consumer key from Pesapal dashboard" />

                <flux:input label="Consumer Secret" wire:model="pesapal_consumer_secret" type="password"
                    placeholder="Consumer secret" />

                <flux:input label="IPN ID (optional)" wire:model="pesapal_ipn_id"
                    placeholder="IPN ID from Pesapal dashboard"
                    description:trailing="Instant Payment Notification ID for callbacks." />

                <flux:input label="Webhook Secret (optional)" wire:model="pesapal_webhook_secret" type="password" />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('pesapal-config').close()"
                        class="cursor-pointer">Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- PayPal Config --}}
    <flux:modal name="paypal-config" class="md:w-120">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">PayPal Configuration</flux:heading>
                <flux:subheading>Enter your PayPal API credentials</flux:subheading>
            </div>

            <form wire:submit="savePaypal" class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                    <div>
                        <flux:text class="text-sm font-medium">Production Mode</flux:text>
                        <flux:text class="text-xs text-zinc-400">Enable for live transactions</flux:text>
                    </div>

                    <flux:switch wire:model="paypal_mode_production" />
                </div>

                <flux:input label="Client ID" wire:model="paypal_client_id"
                    placeholder="Client ID from PayPal dashboard" />

                <flux:input label="Client Secret" wire:model="paypal_client_secret" type="password"
                    placeholder="Client Secret" />

                <flux:input label="Webhook ID (optional)" wire:model="paypal_webhook_id"
                    placeholder="Webhook ID from PayPal dashboard" />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('paypal-config').close()"
                        class="cursor-pointer">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary" class="cursor-pointer">Save Credentials
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Custom Config --}}
    <flux:modal name="custom-config" class="w-full max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Custom Gateway Configuration</flux:heading>
                <flux:subheading>
                    Configure Stripe for card payments and M-Pesa Daraja for mobile payments
                </flux:subheading>
            </div>

            <form wire:submit="saveCustom" class="space-y-6">

                {{-- ── Stripe ── --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:subheading class="font-medium">Stripe — Card Payments</flux:subheading>
                        <flux:badge size="sm" :color="$stripe_mode_production ? 'green' : 'zinc'"
                            variant="soft">
                            {{ $stripe_mode_production ? 'Live' : 'Sandbox' }}
                        </flux:badge>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div>
                            <flux:text class="text-sm font-medium">Production Mode</flux:text>
                            <flux:text class="text-xs text-zinc-400">Enable for live card transactions</flux:text>
                        </div>
                        <flux:switch wire:model="stripe_mode_production" />
                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:input label="Publishable Key" wire:model="stripe_public_key"
                            placeholder="pk_live_..." />

                        <flux:input label="Secret Key" wire:model="stripe_secret_key" type="password"
                            placeholder="sk_live_..." />
                    </div>

                    <flux:input label="Webhook Secret (optional)" wire:model="stripe_webhook_secret" type="password"
                        placeholder="whsec_..." />
                </div>

                <flux:separator />

                {{-- ── M-Pesa ── --}}
                <div class="space-y-4 ">
                    <div class="flex items-center justify-between">
                        <flux:subheading class="font-medium">M-Pesa — Mobile Payments</flux:subheading>
                        <flux:badge size="sm" :color="$mpesa_mode_production ? 'green' : 'zinc'" variant="soft">
                            {{ $mpesa_mode_production ? 'Live' : 'Sandbox' }}
                        </flux:badge>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div>
                            <flux:text class="text-sm font-medium">Production Mode</flux:text>
                            <flux:text class="text-xs text-zinc-400">Enable for live M-Pesa transactions</flux:text>
                        </div>
                        <flux:switch wire:model="mpesa_mode_production" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:input label="Consumer Key" wire:model="mpesa_consumer_key"
                            placeholder="Consumer key from Daraja portal" />

                        <flux:input label="Consumer Secret" wire:model="mpesa_consumer_secret" type="password"
                            placeholder="Consumer secret" />

                        <flux:input label="Shortcode / Till Number" wire:model="mpesa_shortcode"
                            placeholder="e.g. 174379" />

                        <flux:input label="Passkey" wire:model="mpesa_passkey" type="password"
                            placeholder="Lipa Na M-Pesa passkey" />

                        <flux:field>
                            <flux:label>
                                Callback URL
                                <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                            </flux:label>
                            <flux:input wire:model="mpesa_callback_url"
                                placeholder="https://yourdomain.com/mpesa/callback" />
                            <flux:description>Safaricom will send payment confirmations to this URL.</flux:description>
                            <flux:error name="mpesa_callback_url" />
                        </flux:field>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('custom-config').close()"
                        class="cursor-pointer">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        Save Credentials
                    </flux:button>
                </div>

            </form>
        </div>
    </flux:modal>

</x-pages::admin.settings.layout>
