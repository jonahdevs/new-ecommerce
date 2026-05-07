<?php

use App\Livewire\Forms\Admin\Settings\PaymentSettingsForm;
use App\Settings\MpesaSettings;
use App\Settings\PaymentSettings;
use App\Settings\PaypalSettings;
use App\Settings\PesapalSettings;
use App\Settings\PesawiseSettings;
use App\Settings\StripeSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Payment Gateways')] class extends Component {
    public PaymentSettingsForm $form;

    // Gateway statuses for the overview cards
    public bool $mpesa_enabled = false;
    public string $mpesa_env = 'sandbox';
    public bool $stripe_enabled = false;
    public string $stripe_env = 'sandbox';
    public bool $paypal_enabled = false;
    public string $paypal_env = 'sandbox';
    public bool $pesapal_enabled = false;
    public string $pesapal_env = 'sandbox';
    public bool $pesawise_enabled = false;
    public string $pesawise_env = 'sandbox';

    public function mount(PaymentSettings $settings, MpesaSettings $mpesa, StripeSettings $stripe, PaypalSettings $paypal, PesapalSettings $pesapal, PesawiseSettings $pesawise): void
    {
        $this->form->fromSettings($settings);

        $this->mpesa_enabled = $mpesa->enabled;
        $this->mpesa_env = $mpesa->environment;
        $this->stripe_enabled = $stripe->enabled;
        $this->stripe_env = $stripe->environment;
        $this->paypal_enabled = $paypal->enabled;
        $this->paypal_env = $paypal->environment;
        $this->pesapal_enabled = $pesapal->enabled;
        $this->pesapal_env = $pesapal->environment;
        $this->pesawise_enabled = $pesawise->enabled;
        $this->pesawise_env = $pesawise->environment;
    }

    public function save(PaymentSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Payment settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save payment settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Payment gateways')" :subheading="__('Configure how your store accepts payments')">
        <form wire:submit="save" class="space-y-6">

            {{-- Mode switcher --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Payment mode') }}</flux:heading>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                        {{-- Individual --}}
                        <label @class([
                            'flex items-start gap-3 p-4 rounded-lg border-2 cursor-pointer transition-colors',
                            'border-[var(--primary)] bg-[var(--primary)]/5' =>
                                $form->gateway_mode === 'individual',
                            'border-zinc-200 dark:border-zinc-600 hover:border-zinc-300' =>
                                $form->gateway_mode !== 'individual',
                        ])>
                            <input type="radio" wire:model.live="form.gateway_mode" value="individual"
                                class="mt-0.5 accent-[var(--primary)]" />
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ __('Individual gateways') }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    {{ __('Configure M-Pesa, Stripe and PayPal separately. Multiple can be active at once.') }}
                                </p>
                            </div>
                        </label>

                        {{-- Aggregator --}}
                        <label @class([
                            'flex items-start gap-3 p-4 rounded-lg border-2 cursor-pointer transition-colors',
                            'border-[var(--primary)] bg-[var(--primary)]/5' =>
                                $form->gateway_mode === 'aggregator',
                            'border-zinc-200 dark:border-zinc-600 hover:border-zinc-300' =>
                                $form->gateway_mode !== 'aggregator',
                        ])>
                            <input type="radio" wire:model.live="form.gateway_mode" value="aggregator"
                                class="mt-0.5 accent-[var(--primary)]" />
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ __('Aggregator gateway') }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    {{ __('Use one provider (PesaPal or PesaWise) to handle all payment methods.') }}
                                </p>
                            </div>
                        </label>
                    </div>

                </div>
            </flux:card>

            {{-- Aggregator — choose provider --}}
            @if ($form->gateway_mode === 'aggregator')
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Choose provider') }}</flux:heading>
                        <flux:subheading class="text-xs">{{ __('Select which aggregator handles your payments') }}</flux:subheading>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">

                        {{-- PesaPal --}}
                        <label class="flex items-center gap-4 px-5 py-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <input type="radio" wire:model.live="form.active_aggregator" value="pesapal"
                                class="shrink-0 accent-[var(--primary)]" />
                            <div class="w-9 h-9 rounded-lg bg-orange-500 flex items-center justify-center text-white text-xs font-bold shrink-0">PP</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">PesaPal</p>
                                <p class="text-xs text-zinc-500">M-Pesa · Airtel · Visa/Mastercard · Bank</p>
                            </div>
                            <x-settings-gateway-badge :enabled="$pesapal_enabled" :environment="$pesapal_env" />
                            <flux:button size="sm" href="{{ route('settings.payments.pesapal') }}" wire:navigate
                                class="cursor-pointer">
                                {{ __('Configure') }}
                            </flux:button>
                        </label>

                        {{-- PesaWise --}}
                        <label class="flex items-center gap-4 px-5 py-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <input type="radio" wire:model.live="form.active_aggregator" value="pesawise"
                                class="shrink-0 accent-[var(--primary)]" />
                            <div class="w-9 h-9 rounded-lg bg-teal-600 flex items-center justify-center text-white text-xs font-bold shrink-0">PW</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">PesaWise</p>
                                <p class="text-xs text-zinc-500">M-Pesa · Airtel · Cards · Bank transfer</p>
                            </div>
                            <x-settings-gateway-badge :enabled="$pesawise_enabled" :environment="$pesawise_env" />
                            <flux:button size="sm" href="{{ route('settings.payments.pesawise') }}" wire:navigate
                                class="cursor-pointer">
                                {{ __('Configure') }}
                            </flux:button>
                        </label>

                    </div>
                </flux:card>
            @endif

            {{-- Individual gateway status cards --}}
            @if ($form->gateway_mode === 'individual')
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Gateway status') }}</flux:heading>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">

                        {{-- M-Pesa --}}
                        <div class="flex items-center gap-4 px-5 py-4">
                            <div
                                class="w-9 h-9 rounded-lg bg-green-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                M</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">M-Pesa (Daraja)</p>
                                <p class="text-xs text-zinc-500">STK Push ·
                                    {{ ucfirst($mpesa->shortcode_type ?? 'paybill') }}</p>
                            </div>
                            <x-settings-gateway-badge :enabled="$mpesa_enabled" :environment="$mpesa_env" />
                            <flux:button size="sm" href="{{ route('settings.payments.mpesa') }}" wire:navigate
                                class="cursor-pointer">
                                {{ __('Configure') }}
                            </flux:button>
                        </div>

                        {{-- Stripe --}}
                        <div class="flex items-center gap-4 px-5 py-4">
                            <div
                                class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                S</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Stripe</p>
                                <p class="text-xs text-zinc-500">Cards · Apple Pay · Google Pay</p>
                            </div>
                            <x-settings-gateway-badge :enabled="$stripe_enabled" :environment="$stripe_env" />
                            <flux:button size="sm" href="{{ route('settings.payments.stripe') }}" wire:navigate
                                class="cursor-pointer">
                                {{ __('Configure') }}
                            </flux:button>
                        </div>

                        {{-- PayPal --}}
                        <div class="flex items-center gap-4 px-5 py-4">
                            <div
                                class="w-9 h-9 rounded-lg bg-blue-800 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                P</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">PayPal</p>
                                <p class="text-xs text-zinc-500">PayPal wallet · Card payments</p>
                            </div>
                            <x-settings-gateway-badge :enabled="$paypal_enabled" :environment="$paypal_env" />
                            <flux:button size="sm" href="{{ route('settings.payments.paypal') }}" wire:navigate
                                class="cursor-pointer">
                                {{ __('Configure') }}
                            </flux:button>
                        </div>

                    </div>
                </flux:card>
            @endif

            {{-- General payment settings --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('General') }}</flux:heading>
                </div>
                <div class="p-5 space-y-5">
                    <flux:input label="{{ __('Payment currency') }}" wire:model="form.payment_currency"
                        placeholder="KES" description="{{ __('Currency used for payment processing') }}" />
                    <flux:textarea label="{{ __('Payment page instructions') }}" wire:model="form.payment_instructions"
                        rows="2"
                        placeholder="{{ __('Optional note shown at the top of the checkout payment step') }}" />
                </div>
            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
