<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Tax Invoice {{ $order->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ── Page layout ── */
        .page {
            padding: 36px 48px;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a3c2e;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            width: 160px;
            height: auto;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #1a3c2e;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 9.5px;
            color: #555555;
            line-height: 1.6;
        }

        /* ── Document title block ── */
        .doc-title-block {
            width: 100%;
            margin-bottom: 24px;
        }

        .doc-title-block td {
            vertical-align: top;
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1a3c2e;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .doc-meta {
            font-size: 10px;
            color: #333333;
            line-height: 1.8;
        }

        .doc-meta strong {
            color: #1a1a1a;
        }

        /* ── Bill to / ship to ── */
        .addresses-table {
            width: 100%;
            margin-bottom: 24px;
        }

        .addresses-table td {
            vertical-align: top;
            width: 50%;
        }

        .address-box {
            background: #f5f7f5;
            border-left: 3px solid #1a3c2e;
            padding: 10px 14px;
            border-radius: 2px;
        }

        .address-label {
            font-size: 9px;
            font-weight: bold;
            color: #1a3c2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .address-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .address-detail {
            font-size: 10px;
            color: #444444;
            line-height: 1.6;
        }

        /* ── Payment info strip ── */
        .payment-strip {
            width: 100%;
            background: #1a3c2e;
            color: #ffffff;
            margin-bottom: 24px;
            border-radius: 2px;
        }

        .payment-strip td {
            padding: 8px 14px;
            font-size: 10px;
            text-align: center;
        }

        .payment-strip .strip-label {
            font-size: 8.5px;
            color: #aaccbb;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 2px;
        }

        .payment-strip .strip-value {
            font-weight: bold;
            font-size: 11px;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .items-table thead tr {
            background: #1a3c2e;
            color: #ffffff;
        }

        .items-table thead th {
            padding: 8px 10px;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            border: none;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e8ece8;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9faf9;
        }

        .items-table tbody td {
            padding: 8px 10px;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table tbody td.text-right {
            text-align: right;
        }

        .item-name {
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 1px;
        }

        .item-sku {
            font-size: 9px;
            color: #888888;
        }

        /*  Totals  */
        .totals-table {
            width: 260px;
            margin-left: auto;
            margin-bottom: 32px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 10px;
            font-size: 10.5px;
        }

        .totals-table .label {
            color: #555555;
        }

        .totals-table .value {
            text-align: right;
            font-weight: bold;
            color: #1a1a1a;
        }

        .totals-table .divider td {
            border-top: 1px solid #cccccc;
            padding-top: 6px;
        }

        .totals-table .total-row {
            background: #1a3c2e;
            border-radius: 2px;
        }

        .totals-table .total-row td {
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 10px;
        }

        .totals-table .vat-row td {
            color: #555555;
            font-size: 9.5px;
            font-style: italic;
        }

        /* Notes */
        .notes-box {
            background: #f5f7f5;
            border: 1px solid #d4ddd4;
            padding: 10px 14px;
            margin-bottom: 24px;
            border-radius: 2px;
            font-size: 10px;
            color: #444444;
            line-height: 1.6;
        }

        .notes-label {
            font-weight: bold;
            color: #1a3c2e;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #cccccc;
            padding-top: 12px;
            text-align: center;
            font-size: 9px;
            color: #888888;
            line-height: 1.7;
        }

        .footer strong {
            color: #1a3c2e;
        }

        /* ── Paid stamp ── */
        .paid-stamp {
            border: 3px solid #16a34a;
            color: #16a34a;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            padding: 4px 12px;
            display: inline-block;
            transform: rotate(-15deg);
            opacity: 0.8;
            border-radius: 2px;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ================================================================== --}}
        {{-- HEADER                                                              --}}
        {{-- ================================================================== --}}
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <img class="logo" src="{{ public_path('logo.png') }}" alt="Sheffield Africa Steel Systems" />
                </td>
                <td style="width: 45%; text-align: right;">
                    <div class="company-name">SheffieldAfrica Steel Systems</div>
                    <div class="company-details">
                        Off Old Mombasa Road before the Nairobi SGR Terminus<br>
                        Nairobi, Kenya<br>
                        Tel: +254 713 777 111<br>
                        Email: info@sheffieldafrica.com<br>
                        Web: www.sheffieldafrica.com<br>
                        PIN: P051234567X
                    </div>
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- DOCUMENT TITLE + META                                               --}}
        {{-- ================================================================== --}}
        <table class="doc-title-block">
            <tr>
                <td style="width: 50%;">
                    <div class="doc-title">Tax Invoice</div>
                    @if ($order->payment?->paid_at)
                        <div style="margin-top: 8px;">
                            <span class="paid-stamp">Paid</span>
                        </div>
                    @endif
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="doc-meta">
                        <strong>Invoice No:</strong> {{ str_replace('SO-', 'INV-', $order->reference) }}<br>
                        <strong>Order Ref:</strong> {{ $order->reference }}<br>
                        <strong>Invoice Date:</strong> {{ now()->format('d M Y') }}<br>
                        <strong>Payment Date:</strong>
                        {{ $order->payment?->paid_at?->format('d M Y') ?? now()->format('d M Y') }}<br>
                        @if ($order->wasConvertedFromQuote() && $order->quote)
                            <strong>Quotation Ref:</strong> {{ $order->quote->reference }}<br>
                        @endif
                        @if ($order->kra_cu_number)
                            <strong>KRA CU No:</strong> {{ $order->kra_cu_number }}<br>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- BILL TO / SHIP TO                                                   --}}
        {{-- ================================================================== --}}
        <table class="addresses-table">
            <tr>
                <td style="padding-right: 12px;">
                    <div class="address-box">
                        <div class="address-label">Bill To</div>
                        <div class="address-name">{{ $order->user?->name }}</div>
                        <div class="address-detail">
                            {{ $order->billing_address['address'] ?? '' }}<br>
                            @if ($order->billing_address['area'] ?? null)
                                {{ $order->billing_address['area'] }},
                            @endif
                            {{ $order->billing_address['county'] ?? '' }}<br>
                            {{ $order->user?->email }}<br>
                            {{ $order->billing_address['phone_number'] ?? ($order->user?->phone_number ?? '') }}
                        </div>
                    </div>
                </td>
                <td style="padding-left: 12px;">
                    <div class="address-box">
                        <div class="address-label">Ship To</div>
                        <div class="address-name">{{ $order->shipping_address['full_name'] ?? $order->user?->name }}
                        </div>
                        <div class="address-detail">
                            {{ $order->shipping_address['address'] ?? '' }}<br>
                            @if ($order->shipping_address['area'] ?? null)
                                {{ $order->shipping_address['area'] }},
                            @endif
                            {{ $order->shipping_address['county'] ?? '' }}<br>
                            {{ $order->shipping_address['phone_number'] ?? '' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- PAYMENT STRIP                                                        --}}
        {{-- ================================================================== --}}
        <table class="payment-strip">
            <tr>
                <td>
                    <span class="strip-label">Payment Method</span>
                    <span class="strip-value">{{ strtoupper($order->payment?->gateway ?? 'N/A') }}</span>
                </td>
                <td>
                    <span class="strip-label">Transaction ID</span>
                    <span class="strip-value">{{ $order->payment?->transaction_id ?? '—' }}</span>
                </td>
                <td>
                    <span class="strip-label">Currency</span>
                    <span class="strip-value">{{ $order->currency }}</span>
                </td>
                <td>
                    <span class="strip-label">Payment Status</span>
                    <span class="strip-value">{{ strtoupper($order->payment?->status?->label() ?? 'Paid') }}</span>
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- ITEMS TABLE                                                          --}}
        {{-- ================================================================== --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 42%;">Description</th>
                    <th style="width: 10%;">SKU</th>
                    <th class="text-right" style="width: 10%;">Qty</th>
                    <th class="text-right" style="width: 15%;">Unit Price</th>
                    <th class="text-right" style="width: 12%;">Discount</th>
                    <th class="text-right" style="width: 12%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? '—';
                        $brand = $item->product_snapshot['brand'] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $name }}</div>
                            @if ($brand)
                                <div class="item-sku">{{ $brand }}</div>
                            @endif
                        </td>
                        <td><span class="item-sku">{{ $sku }}</span></td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price_cents / 100, 2) }}</td>
                        <td class="text-right">
                            {{ $item->discount_cents > 0 ? number_format($item->discount_cents / 100, 2) : '—' }}
                        </td>
                        <td class="text-right">{{ number_format($item->total_cents / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ================================================================== --}}
        {{-- TOTALS                                                              --}}
        {{-- ================================================================== --}}
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ format_currency($order->subtotal) }}</td>
            </tr>
            @if ($order->discount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value" style="color: #16a34a;">− {{ format_currency($order->discount) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Shipping</td>
                <td class="value">
                    @if ($order->shipping == 0)
                        Free
                    @else
                        {{ format_currency($order->shipping) }}
                    @endif
                </td>
            </tr>

            {{-- VAT breakdown (16% inclusive) --}}
            @php
                // VAT is inclusive — extract from total
                // VAT amount = total × (16/116)
                $vatAmount = ($order->total * 16) / 116;
                $exclVat = $order->total - $vatAmount;
            @endphp
            <tr class="divider">
                <td class="label" style="border-top: 1px solid #cccccc; padding-top: 6px;">
                    Excl. VAT (16%)
                </td>
                <td class="value" style="border-top: 1px solid #cccccc; padding-top: 6px;">
                    {{ format_currency($exclVat) }}
                </td>
            </tr>
            <tr>
                <td class="label">VAT @ 16%</td>
                <td class="value">{{ format_currency($vatAmount) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total (Incl. VAT)</td>
                <td style="text-align: right;">{{ format_currency($order->total) }}</td>
            </tr>
            <tr class="vat-row">
                <td colspan="2" style="padding-top: 4px; text-align: center;">
                    All amounts in {{ get_currency_code() }}. VAT inclusive at 16%.
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- ETIMS / KRA COMPLIANCE SECTION                                      --}}
        {{-- ================================================================== --}}
        @if ($order->kra_cu_number)
            <table style="width: 100%; margin-bottom: 24px; border: 1px solid #d4ddd4; border-radius: 2px;">
                <tr>
                    <td style="background: #f5f7f5; padding: 8px 14px; border-bottom: 1px solid #d4ddd4;">
                        <span style="font-size: 9.5px; font-weight: bold; color: #1a3c2e; text-transform: uppercase; letter-spacing: 0.5px;">
                            KRA eTIMS Compliance
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 14px;">
                        <table style="width: 100%; font-size: 10px; color: #444444;">
                            <tr>
                                <td style="width: 30%; padding: 3px 0;"><strong>CU Number:</strong></td>
                                <td style="padding: 3px 0;">{{ $order->kra_cu_number }}</td>
                            </tr>
                            @if ($order->kra_validated_at)
                                <tr>
                                    <td style="padding: 3px 0;"><strong>Validated:</strong></td>
                                    <td style="padding: 3px 0;">{{ $order->kra_validated_at->format('d M Y H:i:s') }}</td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        @endif

        {{-- ================================================================== --}}
        {{-- NOTES                                                               --}}
        {{-- ================================================================== --}}
        <div class="notes-box">
            <div class="notes-label">Notes & Terms</div>
            This invoice is computer generated and does not require a physical signature.
            For any queries regarding this invoice, please contact us at info@sheffieldafrica.com
            or call +254 713 777 111 quoting your order reference <strong>{{ $order->reference }}</strong>.
            Goods remain the property of SheffieldAfrica Steel Systems until full payment is received.
        </div>

        {{-- ================================================================== --}}
        {{-- FOOTER                                                              --}}
        {{-- ================================================================== --}}
        <div class="footer">
            <strong>SheffieldAfrica Steel Systems</strong> &bull;
            Off Old Mombasa Road, Nairobi &bull;
            +254 713 777 111 &bull;
            info@sheffieldafrica.com &bull;
            www.sheffieldafrica.com<br>
            PIN: P051234567X &bull;
            Thank you for your business!
        </div>

    </div>
</body>

</html>
