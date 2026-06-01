<?php

namespace App\Support;

use App\Settings\CurrencySettings;

/**
 * Single source of truth for rendering money amounts on the storefront and
 * admin. Stored values are integer cents; this honours the store-wide
 * {@see CurrencySettings} (symbol, placement, decimals and separators).
 */
class Money
{
    /** Non-breaking space keeping the symbol glued to the amount. */
    private const SPACE = "\u{00A0}";

    public function __construct(private CurrencySettings $settings) {}

    /**
     * Format an integer cents amount into a display string, e.g. "KES 24,000".
     */
    public function format(int $cents): string
    {
        $amount = number_format(
            $cents / 100,
            $this->settings->decimals,
            $this->settings->decimal_separator,
            $this->settings->thousand_separator,
        );

        return $this->settings->symbol_position === 'after'
            ? $amount.self::SPACE.$this->settings->symbol
            : $this->settings->symbol.self::SPACE.$amount;
    }
}
