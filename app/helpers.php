<?php

use App\Settings\CurrencySettings;
use App\Support\Money;

if (! function_exists('money')) {
    /**
     * Format a cents amount for display using the store's
     * {@see CurrencySettings}. e.g. money(2400000) => "KES 24,000".
     * Null (e.g. an empty SUM aggregate) is treated as zero.
     */
    function money(int|float|null $cents): string
    {
        return app(Money::class)->format((int) $cents);
    }
}
