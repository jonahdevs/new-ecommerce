<?php

use App\Models\TaxClass;
use App\Settings\CurrencySettings;
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

    // ─── Payments ──────────────────────────────────────────────────────────────
    public bool $mpesa_enabled = true;

    public string $mpesa_shortcode = '';

    public string $mpesa_type = 'paybill';

    public bool $card_enabled = true;

    public string $card_provider = 'stripe';

    public bool $bank_transfer_enabled = false;

    public string $bank_details = '';

    public bool $cash_on_delivery_enabled = false;

    // ─── Tax ───────────────────────────────────────────────────────────────────
    public bool $tax_enabled = true;

    public ?int $default_tax_class_id = null;

    public bool $prices_include_tax = true;

    public string $price_display = 'including';

    // ─── Currency ──────────────────────────────────────────────────────────────
    public string $symbol = 'KSh';

    public string $symbol_position = 'before';

    public int $decimals = 0;

    public string $thousand_separator = ',';

    public string $decimal_separator = '.';

    public function mount(PaymentSettings $payments, TaxSettings $tax, CurrencySettings $currency): void
    {
        $this->mpesa_enabled = $payments->mpesa_enabled;
        $this->mpesa_shortcode = $payments->mpesa_shortcode;
        $this->mpesa_type = $payments->mpesa_type;
        $this->card_enabled = $payments->card_enabled;
        $this->card_provider = $payments->card_provider;
        $this->bank_transfer_enabled = $payments->bank_transfer_enabled;
        $this->bank_details = $payments->bank_details;
        $this->cash_on_delivery_enabled = $payments->cash_on_delivery_enabled;

        $this->tax_enabled = $tax->tax_enabled;
        $this->default_tax_class_id = $tax->default_tax_class_id;
        $this->prices_include_tax = $tax->prices_include_tax;
        $this->price_display = $tax->price_display;

        $this->symbol = $currency->symbol;
        $this->symbol_position = $currency->symbol_position;
        $this->decimals = $currency->decimals;
        $this->thousand_separator = $currency->thousand_separator;
        $this->decimal_separator = $currency->decimal_separator;
    }

    public function savePayments(PaymentSettings $settings): void
    {
        $this->validate([
            'mpesa_shortcode' => ['nullable', 'string', 'max:20'],
            'mpesa_type' => ['required', 'in:paybill,till'],
            'card_provider' => ['required', 'in:flutterwave,paystack,stripe'],
            'bank_details' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings->fill([
            'mpesa_enabled' => $this->mpesa_enabled,
            'mpesa_shortcode' => $this->mpesa_shortcode,
            'mpesa_type' => $this->mpesa_type,
            'card_enabled' => $this->card_enabled,
            'card_provider' => $this->card_provider,
            'bank_transfer_enabled' => $this->bank_transfer_enabled,
            'bank_details' => $this->bank_details,
            'cash_on_delivery_enabled' => $this->cash_on_delivery_enabled,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Payment settings updated.', variant: 'success');
    }

    public function saveTax(TaxSettings $settings): void
    {
        $this->validate([
            'default_tax_class_id' => ['nullable', 'exists:tax_classes,id'],
            'price_display' => ['required', 'in:including,excluding'],
        ]);

        $settings->fill([
            'tax_enabled' => $this->tax_enabled,
            'default_tax_class_id' => $this->default_tax_class_id ?: null,
            'prices_include_tax' => $this->prices_include_tax,
            'price_display' => $this->price_display,
        ])->save();

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
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Payments</flux:heading>
                    <flux:subheading>Enable the methods customers can pay with.</flux:subheading>
                </div>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('admin.payments.index')" wire:navigate>
                    View transactions
                </flux:button>
            </div>

            <form wire:submit="savePayments" class="mt-6 space-y-5">
                {{-- M-Pesa --}}
                <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">M-Pesa</flux:heading>
                        <flux:switch wire:model.live="mpesa_enabled" />
                    </div>
                    @if ($mpesa_enabled)
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="mpesa_shortcode" label="Shortcode" placeholder="e.g. 174379" />
                            <flux:select wire:model="mpesa_type" label="Type">
                                <flux:select.option value="paybill">Paybill</flux:select.option>
                                <flux:select.option value="till">Till (Buy Goods)</flux:select.option>
                            </flux:select>
                        </div>
                        <flux:text size="sm" class="mt-2 text-xs text-zinc-400">API keys & secrets are configured in your environment file.</flux:text>
                    @endif
                </div>

                {{-- Card --}}
                <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Card payments</flux:heading>
                        <flux:switch wire:model.live="card_enabled" />
                    </div>
                    @if ($card_enabled)
                        <flux:select wire:model="card_provider" label="Provider" class="mt-4">
                            <flux:select.option value="flutterwave">Flutterwave</flux:select.option>
                            <flux:select.option value="paystack">Paystack</flux:select.option>
                            <flux:select.option value="stripe">Stripe</flux:select.option>
                        </flux:select>
                    @endif
                </div>

                {{-- Bank transfer --}}
                <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Bank transfer</flux:heading>
                        <flux:switch wire:model.live="bank_transfer_enabled" />
                    </div>
                    @if ($bank_transfer_enabled)
                        <flux:textarea wire:model="bank_details" label="Bank details" rows="3" class="mt-4"
                            placeholder="Account name, bank, account number, branch…" />
                    @endif
                </div>

                {{-- COD --}}
                <div class="flex items-center justify-between rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="sm">Cash on delivery</flux:heading>
                    <flux:switch wire:model="cash_on_delivery_enabled" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Tax --}}
    @if ($section === 'tax')
        <flux:card>
            <flux:heading>Tax</flux:heading>
            <flux:subheading>How tax is calculated and displayed.</flux:subheading>

            <form wire:submit="saveTax" class="mt-6 space-y-5">
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
                        <flux:label>Product prices are entered tax-inclusive</flux:label>
                        <flux:switch wire:model="prices_include_tax" />
                    </div>

                    <flux:select wire:model="price_display" label="Display prices in the store">
                        <flux:select.option value="including">Including tax</flux:select.option>
                        <flux:select.option value="excluding">Excluding tax</flux:select.option>
                    </flux:select>
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Currency & pricing --}}
    @if ($section === 'currency')
        <flux:card>
            <flux:heading>Currency & pricing</flux:heading>
            <flux:subheading>How monetary values are formatted. Currency code lives under General → Localization.</flux:subheading>

            <form wire:submit="saveCurrency" class="mt-6 space-y-5">
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
