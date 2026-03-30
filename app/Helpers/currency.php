<?php

use App\Settings\LocalizationSettings;
use Illuminate\Support\Number;

if (!function_exists('format_currency')) {
    /**
     * Format amount as currency using localization settings
     */
    function format_currency(float|int $amount, ?string $currency = null, ?string $locale = null): string
    {
        $settings = app(LocalizationSettings::class);
        
        $currency = $currency ?? $settings->currency ?? config('app.currency', 'KES');
        $locale = $locale ?? config('app.locale', 'en_KE');
        
        // Use custom formatting if settings specify non-standard format
        if ($settings->currency_position !== 'before' || $settings->decimal_places !== 2) {
            return format_currency_custom($amount, $currency, $settings);
        }
        
        return Number::currency($amount, $currency, $locale);
    }
}

if (!function_exists('format_currency_custom')) {
    /**
     * Custom currency formatting using localization settings
     */
    function format_currency_custom(float|int $amount, string $currency, LocalizationSettings $settings): string
    {
        $formatted = number_format(
            $amount,
            $settings->decimal_places,
            $settings->decimal_separator,
            $settings->thousands_separator
        );
        
        $symbol = $settings->currency_symbol ?: $currency;
        
        return match ($settings->currency_position) {
            'before' => $symbol . $formatted,
            'after' => $formatted . $symbol,
            'before_space' => $symbol . ' ' . $formatted,
            'after_space' => $formatted . ' ' . $symbol,
            default => $symbol . $formatted,
        };
    }
}

if (!function_exists('format_price')) {
    /**
     * Format price without decimals using localization settings
     */
    function format_price(float|int $amount, ?string $currency = null): string
    {
        $settings = app(LocalizationSettings::class);
        $currency = $currency ?? $settings->currency ?? config('app.currency', 'KES');
        
        return Number::currency(round($amount), $currency);
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * Get the currency symbol from settings
     */
    function get_currency_symbol(): string
    {
        $settings = app(LocalizationSettings::class);
        return $settings->currency_symbol ?: $settings->currency;
    }
}

if (!function_exists('get_currency_code')) {
    /**
     * Get the currency code from settings
     */
    function get_currency_code(): string
    {
        return app(LocalizationSettings::class)->currency;
    }
}
