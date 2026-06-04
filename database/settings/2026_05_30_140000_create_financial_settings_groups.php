<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Payments
        $this->migrator->add('payments.mpesa_enabled', true);
        $this->migrator->add('payments.mpesa_shortcode', '');
        $this->migrator->add('payments.mpesa_type', 'paybill');
        $this->migrator->add('payments.card_enabled', true);
        $this->migrator->add('payments.card_provider', 'stripe');
        $this->migrator->add('payments.bank_transfer_enabled', false);
        $this->migrator->add('payments.bank_details', '');
        $this->migrator->add('payments.cash_on_delivery_enabled', false);

        // Tax
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
