<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ── Payments ───────────────────────────────────────────────────────
        $this->migrator->add('payments.mpesa_enabled', true);
        $this->migrator->add('payments.mpesa_shortcode', '');
        $this->migrator->add('payments.mpesa_type', 'paybill');
        $this->migrator->add('payments.card_enabled', false);
        $this->migrator->add('payments.card_provider', 'flutterwave');
        $this->migrator->add('payments.bank_transfer_enabled', false);
        $this->migrator->add('payments.bank_details', '');
        $this->migrator->add('payments.cash_on_delivery_enabled', false);

        // ── Tax ────────────────────────────────────────────────────────────
        $this->migrator->add('tax.tax_enabled', true);
        // The fallback tax class for products without one of their own. Populated
        // with the seeded "Standard rated" class by TaxClassSeeder.
        $this->migrator->add('tax.default_tax_class_id', null);
        $this->migrator->add('tax.prices_include_tax', true);
        $this->migrator->add('tax.price_display', 'including');

        // ── Currency & pricing ─────────────────────────────────────────────
        $this->migrator->add('currency.symbol', 'KSh');
        $this->migrator->add('currency.symbol_position', 'before');
        $this->migrator->add('currency.decimals', 0);
        $this->migrator->add('currency.thousand_separator', ',');
        $this->migrator->add('currency.decimal_separator', '.');

        // ── Invoicing ──────────────────────────────────────────────────────
        $this->migrator->add('invoicing.invoice_prefix', 'INV-');
        $this->migrator->add('invoicing.invoice_next_number', 1001);
        $this->migrator->add('invoicing.invoice_footer', 'Thank you for your business.');
        $this->migrator->add('invoicing.show_tax_pin', true);
    }
};
