<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // PAYMENTS
        // ==================================================
        $this->migrator->add('payments.mpesa_enabled', true);
        $this->migrator->add('payments.mpesa_shortcode', '');
        $this->migrator->add('payments.mpesa_type', 'paybill');
        $this->migrator->add('payments.airtel_money_enabled', false);
        $this->migrator->add('payments.card_enabled', true);
        // Paystack is the active gateway — it fronts cards, M-Pesa, Airtel Money,
        // and bank transfers through a single integration.
        $this->migrator->add('payments.card_provider', 'paystack');
        $this->migrator->add('payments.paystack_enabled', true);
        $this->migrator->add('payments.bank_transfer_enabled', false);
        $this->migrator->addEncrypted('payments.bank_details', '');
        $this->migrator->add('payments.cash_on_delivery_enabled', false);

        // ==================================================
        // TAX
        // ==================================================
        $this->migrator->add('tax.tax_enabled', true);
        // The fallback tax class for products without one of their own. Populated
        // with the seeded "Standard rated" class by TaxClassSeeder.
        $this->migrator->add('tax.default_tax_class_id', null);
        $this->migrator->add('tax.prices_include_tax', true);

        // ==================================================
        // CURRENCY & PRICING
        // ==================================================
        $this->migrator->add('currency.symbol', 'KES');
        $this->migrator->add('currency.symbol_position', 'before');
        $this->migrator->add('currency.decimals', 0);
        $this->migrator->add('currency.thousand_separator', ',');
        $this->migrator->add('currency.decimal_separator', '.');
    }
};
