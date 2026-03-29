<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class QuotationSettings extends Settings
{
    public bool $enabled;                    // Enable/disable quotation system
    public string $quote_id_prefix;          // Reference prefix (e.g., "QT-")
    public int $default_validity_days;       // Default quote validity period
    public int $min_validity_days;           // Minimum validity days admin can set
    public int $max_validity_days;           // Maximum validity days admin can set
    public bool $allow_guest_quotes;         // Allow non-logged-in users to request quotes
    public bool $require_phone;              // Require phone number for quote requests
    public bool $auto_expire_enabled;        // Auto-expire overdue quotes via scheduler
    public ?string $admin_notification_email; // Override email for quote notifications
    public ?string $quote_terms;             // Terms & conditions shown on quote PDF
    public ?string $quote_footer_note;       // Footer note on quote PDF

    public static function group(): string
    {
        return 'quotations';
    }
}
