<?php

use App\Settings\CurrencySettings;
use App\Support\Money;

it('formats cents using the default store currency settings', function () {
    // Seeded defaults: KES, symbol before, 0 decimals, comma thousands.
    expect(money(2400000))->toBe("KES\u{00A0}24,000")
        ->and(money(0))->toBe("KES\u{00A0}0")
        ->and(money(null))->toBe("KES\u{00A0}0");
});

it('places the symbol after the amount when configured', function () {
    $settings = app(CurrencySettings::class);
    $settings->symbol_position = 'after';

    expect((new Money($settings))->format(150000))->toBe("1,500\u{00A0}KES");
});

it('honours decimals and custom separators', function () {
    $settings = app(CurrencySettings::class);
    $settings->symbol = '$';
    $settings->decimals = 2;
    $settings->thousand_separator = '.';
    $settings->decimal_separator = ',';

    expect((new Money($settings))->format(123456))->toBe("\$\u{00A0}1.234,56");
});

it('rounds sub-unit cents to the configured decimals', function () {
    $settings = app(CurrencySettings::class);
    $settings->decimals = 0;

    // 3776 cents = 37.76 -> rounds to 38 at 0 decimals.
    expect((new Money($settings))->format(3776))->toBe("KES\u{00A0}38");
});
