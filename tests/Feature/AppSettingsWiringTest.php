<?php

use App\Models\Order;
use App\Models\Quote;
use App\Settings\CheckoutSettings;
use App\Settings\QuotationSettings;

it('builds order numbers from the configured prefix', function () {
    app(CheckoutSettings::class)->fill(['order_prefix' => 'ORD-'])->save();

    expect(Order::generateNumber())->toStartWith('ORD-'.now()->year.'-');
});

it('builds quote numbers from the configured prefix', function () {
    app(QuotationSettings::class)->fill(['quote_prefix' => 'QTE-'])->save();

    expect(Quote::generateNumber())->toStartWith('QTE-'.now()->year.'-');
});

it('serves the quote request page when quotations are enabled', function () {
    $this->get(route('quote.request'))->assertOk();
});

it('404s the quote request page when quotations are disabled', function () {
    app(QuotationSettings::class)->fill(['quotes_enabled' => false])->save();

    $this->get(route('quote.request'))->assertNotFound();
});
