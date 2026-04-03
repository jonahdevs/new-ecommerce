<?php

// ============================================================
//  app/helpers.php
//  Autoloaded globally — available anywhere in the app.
//  Register in composer.json (see below).
// ============================================================

if (! function_exists('normalize_phone')) {
    /**
     * Normalize a Kenyan phone number to the 254XXXXXXXXX format.
     *
     * Handles all common input formats:
     *   "0712 345 678"   → "254712345678"
     *   "712345678"      → "254712345678"
     *   "254712345678"   → "254712345678"
     *   "+254 712345678" → "254712345678"
     *
     * Usage:
     *   normalize_phone($this->phone_number)
     */
    function normalize_phone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Strip everything that isn't a digit
        $digits = preg_replace('/\D/', '', $phone);

        // Remove leading country code or 0
        $digits = preg_replace('/^(254|0)/', '', $digits);

        return '254'.$digits;
    }
}

if (! function_exists('strip_phone_prefix')) {
    /**
     * Strip the 254 country code for display in form inputs.
     *
     * "254712345678" → "712345678"
     *
     * Usage:
     *   strip_phone_prefix($address->phone_number)
     */
    function strip_phone_prefix(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        return preg_replace('/^254/', '', $phone);
    }
}

if (! function_exists('format_phone')) {
    /**
     * Format a stored phone number for display.
     *
     * "254712345678" → "+254 712 345 678"
     *
     * Usage:
     *   format_phone($address->phone_number)
     */
    function format_phone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        $local = preg_replace('/^254/', '', $digits);

        // Format as XXX XXX XXX
        if (strlen($local) === 9) {
            return '+254 '.substr($local, 0, 3).' '.substr($local, 3, 3).' '.substr($local, 6);
        }

        return '+254 '.$local;
    }
}
