<?php

use App\Settings\PaymentSettings;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Payment Settings')] class extends Component {
    public string $active_gateway = 'custom';
    public string $mode = 'sandbox';

    // Pesawise
    public string $pesawise_api_key = '';
    public string $pesawise_api_secret = '';
    public string $pesawise_account_number = '';
    public string $pesawise_webhook_secret = '';

    // Paystack
    public string $paystack_public_key = '';
    public string $paystack_secret_key = '';
    public string $paystack_webhook_secret = '';

    // Stripe
    public string $stripe_public_key = '';
    public string $stripe_secret_key = '';
    public string $stripe_webhook_secret = '';

    // PayPal
    public string $paypal_client_id = '';
    public string $paypal_client_secret = '';
    public string $paypal_webhook_id = '';

    // Custom
    public string $custom_name = '';
    public string $custom_instructions = '';

    public function mount(PaymentSettings $settings): void
    {
        $this->active_gateway = $settings->active_gateway;
        $this->mode = $settings->mode;

        $this->pesawise_api_key = $settings->pesawise_api_key ?? '';
        $this->pesawise_api_secret = $settings->pesawise_api_secret ?? '';
        $this->pesawise_account_number = $settings->pesawise_account_number ?? '';
        $this->pesawise_webhook_secret = $settings->pesawise_webhook_secret ?? '';

        $this->paystack_public_key = $settings->paystack_public_key ?? '';
        $this->paystack_secret_key = $settings->paystack_secret_key ?? '';
        $this->paystack_webhook_secret = $settings->paystack_webhook_secret ?? '';

        $this->stripe_public_key = $settings->stripe_public_key ?? '';
        $this->stripe_secret_key = $settings->stripe_secret_key ?? '';
        $this->stripe_webhook_secret = $settings->stripe_webhook_secret ?? '';

        $this->paypal_client_id = $settings->paypal_client_id ?? '';
        $this->paypal_client_secret = $settings->paypal_client_secret ?? '';
        $this->paypal_webhook_id = $settings->paypal_webhook_id ?? '';

        $this->custom_name = $settings->custom_name ?? '';
        $this->custom_instructions = $settings->custom_instructions ?? '';
    }

    public function rules(): array
    {
        return [
            'active_gateway' => ['required', 'in:pesawise,paystack,stripe,paypal,custom'],
            'mode' => ['required', 'in:sandbox,production'],

            // Only validate the active gateway fields
            'pesawise_api_key' => [$this->active_gateway === 'pesawise' ? 'required' : 'nullable', 'string', 'max:255'],
            'pesawise_api_secret' => [$this->active_gateway === 'pesawise' ? 'required' : 'nullable', 'string', 'max:255'],
            'pesawise_account_number' => [$this->active_gateway === 'pesawise' ? 'required' : 'nullable', 'string', 'max:50'],
            'pesawise_webhook_secret' => ['nullable', 'string', 'max:255'],

            'paystack_public_key' => [$this->active_gateway === 'paystack' ? 'required' : 'nullable', 'string', 'max:255'],
            'paystack_secret_key' => [$this->active_gateway === 'paystack' ? 'required' : 'nullable', 'string', 'max:255'],
            'paystack_webhook_secret' => ['nullable', 'string', 'max:255'],

            'stripe_public_key' => [$this->active_gateway === 'stripe' ? 'required' : 'nullable', 'string', 'max:255'],
            'stripe_secret_key' => [$this->active_gateway === 'stripe' ? 'required' : 'nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],

            'paypal_client_id' => [$this->active_gateway === 'paypal' ? 'required' : 'nullable', 'string', 'max:255'],
            'paypal_client_secret' => [$this->active_gateway === 'paypal' ? 'required' : 'nullable', 'string', 'max:255'],
            'paypal_webhook_id' => ['nullable', 'string', 'max:255'],

            'custom_name' => [$this->active_gateway === 'custom' ? 'required' : 'nullable', 'string', 'max:100'],
            'custom_instructions' => [$this->active_gateway === 'custom' ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    public function save(PaymentSettings $settings): void
    {
        $this->validate();

        try {
            $settings->active_gateway = $this->active_gateway;
            $settings->mode = $this->mode;

            $settings->pesawise_api_key = $this->pesawise_api_key ?: null;
            $settings->pesawise_api_secret = $this->pesawise_api_secret ?: null;
            $settings->pesawise_account_number = $this->pesawise_account_number ?: null;
            $settings->pesawise_webhook_secret = $this->pesawise_webhook_secret ?: null;

            $settings->paystack_public_key = $this->paystack_public_key ?: null;
            $settings->paystack_secret_key = $this->paystack_secret_key ?: null;
            $settings->paystack_webhook_secret = $this->paystack_webhook_secret ?: null;

            $settings->stripe_public_key = $this->stripe_public_key ?: null;
            $settings->stripe_secret_key = $this->stripe_secret_key ?: null;
            $settings->stripe_webhook_secret = $this->stripe_webhook_secret ?: null;

            $settings->paypal_client_id = $this->paypal_client_id ?: null;
            $settings->paypal_client_secret = $this->paypal_client_secret ?: null;
            $settings->paypal_webhook_id = $this->paypal_webhook_id ?: null;

            $settings->custom_name = $this->custom_name ?: null;
            $settings->custom_instructions = $this->custom_instructions ?: null;

            $settings->save();

            $this->dispatch('notify', variant: 'success', message: 'Payment settings saved.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save payment settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    @include('partials.settings-heading')
    <x-pages::admin.settings.layout :heading="__('Payment Settings')" :subheading="__('Configure your store payment gateway')">
        <form wire:submit="save" class="space-y-6">

            {{-- Gateway Selector & Mode --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Active Gateway</flux:subheading>

                <flux:field>
                    <flux:label>Payment Gateway</flux:label>
                    <flux:select wire:model.live="active_gateway">
                        <flux:select.option value="pesawise">Pesawise</flux:select.option>
                        <flux:select.option value="paystack">Paystack</flux:select.option>
                        <flux:select.option value="stripe">Stripe</flux:select.option>
                        <flux:select.option value="paypal">PayPal</flux:select.option>
                        <flux:select.option value="custom">Custom / Manual</flux:select.option>
                    </flux:select>
                    <flux:description>Only the active gateway will be used at checkout.</flux:description>
                    <flux:error name="active_gateway" />
                </flux:field>

                <flux:field>
                    <flux:label>Mode</flux:label>
                    <flux:select wire:model="mode">
                        <flux:select.option value="sandbox">Sandbox (Testing)</flux:select.option>
                        <flux:select.option value="production">Production (Live)</flux:select.option>
                    </flux:select>
                    <flux:error name="mode" />
                </flux:field>

                {{-- Mode Warning --}}
                @if ($mode === 'production')
                    <div
                        class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-800 p-4">
                        <flux:icon name="exclamation-triangle"
                            class="size-5 text-yellow-600 dark:text-yellow-400 mt-0.5 shrink-0" />
                        <flux:text class="text-sm text-yellow-700 dark:text-yellow-400">
                            You are in <strong>Production mode</strong>. Real transactions will be processed.
                            Make sure your credentials are correct before saving.
                        </flux:text>
                    </div>
                @endif
            </div>

            <flux:separator />

            {{-- ── Pesawise ── --}}
            @if ($active_gateway === 'pesawise')
                <div class="space-y-4">
                    <flux:subheading class="font-medium">Pesawise Credentials</flux:subheading>

                    <flux:field>
                        <flux:label>API Key</flux:label>
                        <flux:input wire:model="pesawise_api_key" placeholder="pk_..." />
                        <flux:error name="pesawise_api_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>API Secret</flux:label>
                        <flux:input wire:model="pesawise_api_secret" type="password" placeholder="sk_..." />
                        <flux:error name="pesawise_api_secret" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Account Number</flux:label>
                        <flux:input wire:model="pesawise_account_number" placeholder="e.g. 1234567890" />
                        <flux:error name="pesawise_account_number" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Webhook Secret
                            <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                        </flux:label>
                        <flux:input wire:model="pesawise_webhook_secret" type="password" placeholder="whsec_..." />
                        <flux:description>Used to verify incoming webhook payloads from Pesawise.</flux:description>
                        <flux:error name="pesawise_webhook_secret" />
                    </flux:field>
                </div>
            @endif

            {{-- ── Paystack ── --}}
            @if ($active_gateway === 'paystack')
                <div class="space-y-4">
                    <flux:subheading class="font-medium">Paystack Credentials</flux:subheading>

                    <flux:field>
                        <flux:label>Public Key</flux:label>
                        <flux:input wire:model="paystack_public_key" placeholder="pk_test_..." />
                        <flux:error name="paystack_public_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Secret Key</flux:label>
                        <flux:input wire:model="paystack_secret_key" type="password" placeholder="sk_test_..." />
                        <flux:error name="paystack_secret_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Webhook Secret
                            <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                        </flux:label>
                        <flux:input wire:model="paystack_webhook_secret" type="password" />
                        <flux:description>Used to verify incoming webhook payloads from Paystack.</flux:description>
                        <flux:error name="paystack_webhook_secret" />
                    </flux:field>
                </div>
            @endif

            {{-- ── Stripe ── --}}
            @if ($active_gateway === 'stripe')
                <div class="space-y-4">
                    <flux:subheading class="font-medium">Stripe Credentials</flux:subheading>

                    <flux:field>
                        <flux:label>Publishable Key</flux:label>
                        <flux:input wire:model="stripe_public_key" placeholder="pk_test_..." />
                        <flux:error name="stripe_public_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Secret Key</flux:label>
                        <flux:input wire:model="stripe_secret_key" type="password" placeholder="sk_test_..." />
                        <flux:error name="stripe_secret_key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Webhook Secret
                            <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                        </flux:label>
                        <flux:input wire:model="stripe_webhook_secret" type="password" placeholder="whsec_..." />
                        <flux:description>Used to verify incoming webhook payloads from Stripe.</flux:description>
                        <flux:error name="stripe_webhook_secret" />
                    </flux:field>
                </div>
            @endif

            {{-- ── PayPal ── --}}
            @if ($active_gateway === 'paypal')
                <div class="space-y-4">
                    <flux:subheading class="font-medium">PayPal Credentials</flux:subheading>

                    <flux:field>
                        <flux:label>Client ID</flux:label>
                        <flux:input wire:model="paypal_client_id" placeholder="Client ID from PayPal dashboard" />
                        <flux:error name="paypal_client_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Client Secret</flux:label>
                        <flux:input wire:model="paypal_client_secret" type="password"
                            placeholder="Client Secret from PayPal dashboard" />
                        <flux:error name="paypal_client_secret" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Webhook ID
                            <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                        </flux:label>
                        <flux:input wire:model="paypal_webhook_id" placeholder="Webhook ID from PayPal dashboard" />
                        <flux:description>Used to verify incoming webhook payloads from PayPal.</flux:description>
                        <flux:error name="paypal_webhook_id" />
                    </flux:field>
                </div>
            @endif

            {{-- ── Custom / Manual ── --}}
            @if ($active_gateway === 'custom')
                <div class="space-y-4">
                    <div>
                        <flux:subheading class="font-medium">Custom / Manual Payment</flux:subheading>
                        <flux:text class="text-xs text-zinc-400 mt-1">
                            Customers will see these instructions at checkout and in their order confirmation email.
                        </flux:text>
                    </div>

                    <flux:field>
                        <flux:label>Payment Method Name</flux:label>
                        <flux:input wire:model="custom_name" placeholder="e.g. Bank Transfer, M-Pesa Till" />
                        <flux:description>Shown to customers as the payment method label.</flux:description>
                        <flux:error name="custom_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Payment Instructions</flux:label>
                        <flux:textarea wire:model="custom_instructions" rows="5"
                            placeholder="e.g. Transfer to Equity Bank, Account: 1234567890, Name: Sheffield Africa Ltd. Send proof of payment to hello@sheffield.com" />
                        <flux:description>Clear instructions on how customers should complete the payment.
                        </flux:description>
                        <flux:error name="custom_instructions" />
                    </flux:field>
                </div>
            @endif

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
