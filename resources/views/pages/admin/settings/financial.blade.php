<?php

use App\Models\TaxClass;
use App\Settings\CurrencySettings;
use App\Settings\PaymentApiSettings;
use App\Settings\PaymentSettings;
use App\Settings\TaxSettings;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Financial settings — Admin')] class extends Component
{
    #[Url]
    public string $section = 'payments';

    // Payments
    public bool $mpesa_enabled = true;

    public string $mpesa_shortcode = '';

    public string $mpesa_type = 'paybill';

    public bool $airtel_money_enabled = false;

    public bool $card_enabled = true;

    public string $card_provider = 'paystack';

    public bool $paystack_enabled = true;

    public bool $bank_transfer_enabled = false;

    public string $bank_details = '';

    public bool $cash_on_delivery_enabled = false;

    public ?string $configuringPayment = null;

    public bool $showPaymentModal = false;

    // API credentials — null means "use .env value"
    public ?string $mpesa_env = null;

    public ?string $mpesa_consumer_key = null;

    public ?string $mpesa_consumer_secret = null;

    public ?string $mpesa_passkey = null;

    public ?string $mpesa_callback_url = null;

    public ?string $stripe_key = null;

    public ?string $stripe_secret = null;

    public ?string $stripe_webhook_secret = null;

    public ?string $paystack_public_key = null;

    public ?string $paystack_secret_key = null;

    // Tax
    public bool $tax_enabled = true;

    public ?int $default_tax_class_id = null;

    public bool $prices_include_tax = true;

    public bool $savedPricesIncludeTax = true;

    // Currency
    public string $symbol = 'KSh';

    public string $symbol_position = 'before';

    public int $decimals = 0;

    public string $thousand_separator = ',';

    public string $decimal_separator = '.';

    public function mount(PaymentSettings $payments, PaymentApiSettings $api, TaxSettings $tax, CurrencySettings $currency): void
    {
        $this->mpesa_enabled = $payments->mpesa_enabled;
        $this->mpesa_shortcode = $payments->mpesa_shortcode;
        $this->mpesa_type = $payments->mpesa_type;
        $this->airtel_money_enabled = $payments->airtel_money_enabled;
        $this->card_enabled = $payments->card_enabled;
        $this->paystack_enabled = $payments->paystack_enabled;
        $this->card_provider = $payments->card_provider;
        $this->bank_transfer_enabled = $payments->bank_transfer_enabled;
        $this->bank_details = $payments->bank_details;
        $this->cash_on_delivery_enabled = $payments->cash_on_delivery_enabled;

        $this->mpesa_env = $api->mpesa_env;
        $this->mpesa_consumer_key = $api->mpesa_consumer_key;
        $this->mpesa_consumer_secret = $api->mpesa_consumer_secret;
        $this->mpesa_passkey = $api->mpesa_passkey;
        $this->mpesa_callback_url = $api->mpesa_callback_url;
        $this->stripe_key = $api->stripe_key;
        $this->stripe_secret = $api->stripe_secret;
        $this->stripe_webhook_secret = $api->stripe_webhook_secret;
        $this->paystack_public_key = $api->paystack_public_key;
        $this->paystack_secret_key = $api->paystack_secret_key;

        $this->tax_enabled = $tax->tax_enabled;
        $this->default_tax_class_id = $tax->default_tax_class_id;
        $this->prices_include_tax = $tax->prices_include_tax;
        $this->savedPricesIncludeTax = $tax->prices_include_tax;

        $this->symbol = $currency->symbol;
        $this->symbol_position = $currency->symbol_position;
        $this->decimals = $currency->decimals;
        $this->thousand_separator = $currency->thousand_separator;
        $this->decimal_separator = $currency->decimal_separator;
    }

    public function savePayments(PaymentSettings $settings, PaymentApiSettings $api): void
    {
        $this->validate([
            'mpesa_shortcode'      => ['nullable', 'string', 'max:20'],
            'mpesa_type'           => ['required', 'in:paybill,till'],
            'mpesa_callback_url'   => ['nullable', 'url', 'max:500'],
            'card_provider'        => ['required', 'in:flutterwave,paystack,stripe'],
            'bank_details'         => ['nullable', 'string', 'max:1000'],
            'stripe_key'           => ['nullable', 'string', 'max:500'],
            'stripe_secret'        => ['nullable', 'string', 'max:500'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:500'],
            'paystack_public_key'  => ['nullable', 'string', 'max:500'],
            'paystack_secret_key'  => ['nullable', 'string', 'max:500'],
            'mpesa_consumer_key'   => ['nullable', 'string', 'max:500'],
            'mpesa_consumer_secret' => ['nullable', 'string', 'max:500'],
            'mpesa_passkey'        => ['nullable', 'string', 'max:500'],
        ]);

        $settings->fill([
            'mpesa_enabled'            => $this->mpesa_enabled,
            'mpesa_shortcode'          => $this->mpesa_shortcode,
            'mpesa_type'               => $this->mpesa_type,
            'airtel_money_enabled'     => $this->airtel_money_enabled,
            'card_enabled'             => $this->card_enabled,
            'card_provider'            => $this->card_provider,
            'paystack_enabled'         => $this->paystack_enabled,
            'bank_transfer_enabled'    => $this->bank_transfer_enabled,
            'bank_details'             => $this->bank_details,
            'cash_on_delivery_enabled' => $this->cash_on_delivery_enabled,
        ])->save();

        $api->fill([
            'mpesa_env'             => $this->mpesa_env ?: null,
            'mpesa_consumer_key'    => $this->mpesa_consumer_key ?: null,
            'mpesa_consumer_secret' => $this->mpesa_consumer_secret ?: null,
            'mpesa_passkey'         => $this->mpesa_passkey ?: null,
            'mpesa_callback_url'    => $this->mpesa_callback_url ?: null,
            'stripe_key'            => $this->stripe_key ?: null,
            'stripe_secret'         => $this->stripe_secret ?: null,
            'stripe_webhook_secret' => $this->stripe_webhook_secret ?: null,
            'paystack_public_key'   => $this->paystack_public_key ?: null,
            'paystack_secret_key'   => $this->paystack_secret_key ?: null,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Payment settings updated.', variant: 'success');
        $this->showPaymentModal = false;
    }

    public function updated(string $name): void
    {
        $toggles = ['paystack_enabled', 'cash_on_delivery_enabled'];
        if (in_array($name, $toggles)) {
            app(PaymentSettings::class)->fill([
                'paystack_enabled'         => $this->paystack_enabled,
                'cash_on_delivery_enabled' => $this->cash_on_delivery_enabled,
            ])->save();
        }
    }

    public function configurePayment(string $key): void
    {
        $this->configuringPayment = $key;
        $this->showPaymentModal = true;
    }

    public function saveTax(): void
    {
        $this->validate([
            'default_tax_class_id' => ['nullable', 'exists:tax_classes,id'],
        ]);

        if ($this->prices_include_tax !== $this->savedPricesIncludeTax) {
            Flux::modal('tax-inclusive-warning')->show();

            return;
        }

        $this->persistTax();
    }

    public function confirmSaveTax(): void
    {
        $this->persistTax();
        Flux::modal('tax-inclusive-warning')->close();
    }

    private function persistTax(): void
    {
        $settings = app(TaxSettings::class);

        $settings->fill([
            'tax_enabled' => $this->tax_enabled,
            'default_tax_class_id' => $this->default_tax_class_id ?: null,
            'prices_include_tax' => $this->prices_include_tax,
        ])->save();

        $this->savedPricesIncludeTax = $this->prices_include_tax;

        Flux::toast(heading: 'Saved', text: 'Tax settings updated.', variant: 'success');
    }

    /** Active tax classes offered in the default-tax-class dropdown. */
    #[Computed]
    public function taxClasses(): Collection
    {
        return TaxClass::where('is_active', true)->orderBy('name')->get(['id', 'name', 'rate']);
    }

    public function saveCurrency(CurrencySettings $settings): void
    {
        $this->validate([
            'symbol' => ['required', 'string', 'max:8'],
            'symbol_position' => ['required', 'in:before,after'],
            'decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'thousand_separator' => ['nullable', 'string', 'max:1'],
            'decimal_separator' => ['required', 'string', 'max:1'],
        ]);

        $settings->fill([
            'symbol' => $this->symbol,
            'symbol_position' => $this->symbol_position,
            'decimals' => (int) $this->decimals,
            'thousand_separator' => $this->thousand_separator,
            'decimal_separator' => $this->decimal_separator,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Currency settings updated.', variant: 'success');
    }

    /** Preview of how a price renders with the current currency settings. */
    public function getPricePreview(): string
    {
        $number = number_format(1234.5, (int) $this->decimals, $this->decimal_separator ?: '.', $this->thousand_separator);

        return $this->symbol_position === 'after'
            ? $number.' '.$this->symbol
            : $this->symbol.' '.$number;
    }
}; ?>

<x-admin.settings-shell tab="financial" :section="$section">

    {{-- Payments --}}
    @if ($section === 'payments')
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Payment Gateways</flux:heading>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.payments.index')" wire:navigate>
                    View transactions
                </flux:button>
            </div>

            @php
                $creds = app(\App\Services\PaymentCredentials::class);
                $paystackConnected = (bool) $creds->paystackSecretKey();
                $gateways = [
                    [
                        'key'          => 'paystack',
                        'name'         => 'Paystack',
                        'logo'         => 'paystack',
                        'description'  => 'Your payment gateway. Customers can pay with whatever channels you have enabled in your Paystack dashboard — cards, M-Pesa, Airtel Money, bank transfer and more.',
                        'enabled'      => $paystack_enabled,
                        'configurable' => true,
                        'connected'    => $paystackConnected,
                    ],
                    [
                        'key'          => 'cash_on_delivery',
                        'name'         => 'Cash on Delivery',
                        'logo'         => 'cash',
                        'description'  => 'Allow customers to pay in cash when their order is delivered.',
                        'enabled'      => $cash_on_delivery_enabled,
                        'configurable' => false,
                        'connected'    => true,
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                @foreach ($gateways as $gateway)
                    <div class="flex flex-col rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="p-5">
                            {{-- Top: logo + toggle --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    @if ($gateway['logo'] === 'paystack')
                                        <flux:icon.credit-card class="size-6 text-zinc-500" />
                                        <span class="text-sm font-semibold dark:text-white">{{ $gateway['name'] }}</span>
                                    @else
                                        <flux:icon.banknotes class="size-6 text-zinc-500" />
                                        <span class="text-sm font-semibold dark:text-white">{{ $gateway['name'] }}</span>
                                    @endif
                                </div>
                                <flux:switch wire:model.live="{{ $gateway['key'] }}_enabled" />
                            </div>

                            {{-- Description --}}
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $gateway['description'] }}</p>
                        </div>

                        {{-- Footer: badge + gear --}}
                        <div class="flex items-center justify-between border-t border-zinc-200 px-5 py-2 dark:border-zinc-700">
                            <flux:badge
                                :color="$gateway['connected'] ? 'green' : 'zinc'"
                                size="sm"
                                :icon="$gateway['connected'] ? 'check' : 'x-mark'">
                                {{ $gateway['connected'] ? 'Connected' : 'Not connected' }}
                            </flux:badge>
                            <flux:button size="sm" variant="ghost" icon="cog-6-tooth"
                                wire:click="configurePayment('{{ $gateway['key'] }}')"
                                :disabled="! $gateway['configurable']"
                                tooltip="{{ $gateway['configurable'] ? 'Configure' : 'No configuration needed' }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Settings modal --}}
        <flux:modal wire:model.self="showPaymentModal" class="w-full max-w-lg" :dismissible="true">
            <form wire:submit="savePayments" class="space-y-5">
                @if ($configuringPayment === 'paystack')
                    <div>
                        <flux:heading size="lg">Paystack Settings</flux:heading>
                        <flux:subheading>Enter your Paystack API keys. Which methods customers can pay with (cards, M-Pesa, Airtel Money, bank transfer…) is controlled by the channels you enable in your Paystack dashboard.</flux:subheading>
                    </div>
                    <flux:input wire:model="paystack_public_key" label="Public Key" placeholder="pk_live_…" />
                    <flux:input wire:model="paystack_secret_key" label="Secret Key" type="password" viewable placeholder="sk_live_…" />
                    <p class="text-xs text-zinc-500">In the Paystack dashboard, set your webhook URL to
                        <span class="font-mono break-all">{{ route('payments.paystack.webhook') }}</span>.</p>
                @endif

                <div class="flex gap-2 pt-1">
                    <flux:button type="submit" variant="primary" class="flex-1">Save changes</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Tax --}}
    @if ($section === 'tax')
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Tax</flux:heading>
            </div>

            <form wire:submit="saveTax" class="space-y-5 p-6">
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Enable tax</flux:label>
                    <flux:switch wire:model.live="tax_enabled" />
                </div>

                @if ($tax_enabled)
                    <flux:select wire:model="default_tax_class_id" label="Default tax class"
                        description="Applied to products that don't have a tax class of their own.">
                        <flux:select.option value="">No default (untaxed)</flux:select.option>
                        @foreach ($this->taxClasses as $taxClass)
                            <flux:select.option :value="$taxClass->id">{{ $taxClass->name }} ({{ rtrim(rtrim(number_format((float) $taxClass->rate, 2), '0'), '.') }}%)</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <div>
                            <flux:label>Prices include tax</flux:label>
                            <flux:text size="sm" class="text-xs text-zinc-400">Applies to both how prices are stored and how they are shown to customers.</flux:text>
                        </div>
                        <flux:switch wire:model="prices_include_tax" />
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>

            {{-- Confirmation modal shown when prices_include_tax is flipped --}}
            <flux:modal name="tax-inclusive-warning" class="max-w-md" :dismissible="false">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                        <flux:icon.exclamation-triangle class="size-5 text-amber-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">Review all product prices</flux:heading>
                        <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            You are changing whether product prices are stored inclusive or exclusive of tax.
                            <strong class="text-zinc-900 dark:text-white">This setting does not automatically recalculate any prices.</strong>
                        </flux:text>
                        <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                            After saving, every product price in your catalogue will carry a different tax meaning.
                            You must manually update all product prices to reflect the new setting, otherwise customers
                            will be charged the wrong amount.
                        </flux:text>
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            Only proceed if you are prepared to update all product prices immediately.
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="confirmSaveTax">
                        I understand, save anyway
                    </flux:button>
                </div>
            </flux:modal>

        </flux:card>
    @endif

    {{-- Currency & pricing --}}
    @if ($section === 'currency')
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Currency & pricing</flux:heading>
            </div>

            <form wire:submit="saveCurrency" class="space-y-5 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model.live="symbol" label="Currency symbol" placeholder="KSh" />
                    <flux:select wire:model.live="symbol_position" label="Symbol position">
                        <flux:select.option value="before">Before amount (KSh 1,000)</flux:select.option>
                        <flux:select.option value="after">After amount (1,000 KSh)</flux:select.option>
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model.live="decimals" type="number" min="0" max="4" label="Decimals" />
                    <flux:input wire:model.live="thousand_separator" label="Thousands separator" maxlength="1" placeholder="," />
                    <flux:input wire:model.live="decimal_separator" label="Decimal separator" maxlength="1" placeholder="." />
                </div>

                <div class="rounded-md bg-zinc-50 px-4 py-3 dark:bg-zinc-800">
                    <flux:text size="sm" class="text-zinc-500">Preview</flux:text>
                    <div class="mt-1 text-lg font-semibold tabular-nums dark:text-white">{{ $this->getPricePreview() }}</div>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

</x-admin.settings-shell>
