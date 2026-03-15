<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Quotation {{ $order->reference }}</title>
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

        /* ── Validity banner ── */
        .validity-banner {
            width: 100%;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .validity-banner td {
            padding: 8px 14px;
            font-size: 10px;
            color: #92400e;
        }

        .validity-banner .banner-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 1px;
        }

        /* ── Addresses ── */
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

        /* ── Quotation type strip ── */
        .type-strip {
            width: 100%;
            background: #1a3c2e;
            color: #ffffff;
            margin-bottom: 24px;
            border-radius: 2px;
        }

        .type-strip td {
            padding: 8px 14px;
            font-size: 10px;
            text-align: center;
        }

        .type-strip .strip-label {
            font-size: 8.5px;
            color: #aaccbb;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 2px;
        }

        .type-strip .strip-value {
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

        .quote-badge {
            font-size: 8px;
            background: #fef3c7;
            color: #92400e;
            padding: 1px 5px;
            border-radius: 2px;
            border: 1px solid #f59e0b;
        }

        /* ── Totals ── */
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

        .totals-table .total-row td {
            background: #1a3c2e;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 10px;
        }

        .totals-table .tbd-row td {
            color: #b45309;
            font-size: 9.5px;
            font-style: italic;
            padding-top: 4px;
            text-align: center;
        }

        /* ── How to accept ── */
        .accept-box {
            width: 100%;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .accept-box td {
            padding: 10px 14px;
            font-size: 10px;
            color: #166534;
            vertical-align: top;
        }

        .accept-box .accept-title {
            font-weight: bold;
            font-size: 10.5px;
            margin-bottom: 4px;
        }

        /* ── Notes ── */
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

        /* ── NOT A TAX INVOICE watermark strip ── */
        .not-invoice-strip {
            width: 100%;
            text-align: center;
            background: #fef3c7;
            border: 1px dashed #f59e0b;
            color: #92400e;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px;
            margin-bottom: 20px;
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

        {{-- NOT A TAX INVOICE disclaimer --}}
        <div class="not-invoice-strip">
            This is a quotation only &mdash; not a tax invoice. A tax invoice will be issued upon payment.
        </div>

        {{-- ================================================================== --}}
        {{-- DOCUMENT TITLE + META                                               --}}
        {{-- ================================================================== --}}
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="doc-title">Quotation</div>
                    <div style="margin-top: 6px; font-size: 10px; color: #555555;">
                        @if ($order->quotation_type === 'delivery')
                            Delivery cost quotation for out-of-zone address
                        @else
                            Product quotation — custom pricing applied
                        @endif
                    </div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <div class="doc-meta">
                        <strong>Quotation No:</strong> {{ $order->reference }}<br>
                        <strong>Date:</strong> {{ $order->quoted_at?->format('d M Y') ?? now()->format('d M Y') }}<br>
                        <strong>Valid Until:</strong>
                        @if ($order->expires_at)
                            {{ $order->expires_at->format('d M Y') }}
                            ({{ $order->expires_at->diffForHumans() }})
                        @else
                            Upon agreement
                        @endif
                        <br>
                        <strong>Prepared by:</strong> Sheffield Africa Sales Team
                    </div>
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- VALIDITY BANNER                                                     --}}
        {{-- ================================================================== --}}
        @if ($order->expires_at)
            <table class="validity-banner">
                <tr>
                    <td style="width: 50%;">
                        <span class="banner-label">Quotation valid until</span>
                        {{ $order->expires_at->format('l, d F Y') }}
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <span class="banner-label">Time remaining</span>
                        {{ $order->expires_at->isPast() ? 'Expired' : ucfirst($order->expires_at->diffForHumans()) }}
                    </td>
                </tr>
            </table>
        @endif

        {{-- ================================================================== --}}
        {{-- PREPARED FOR / DELIVERY ADDRESS                                     --}}
        {{-- ================================================================== --}}
        <table class="addresses-table">
            <tr>
                <td style="padding-right: 12px;">
                    <div class="address-box">
                        <div class="address-label">Prepared For</div>
                        <div class="address-name">{{ $order->user?->name }}</div>
                        <div class="address-detail">
                            {{ $order->user?->email }}<br>
                            {{ $order->shipping_address['phone_number'] ?? ($order->user?->phone_number ?? '') }}
                        </div>
                    </div>
                </td>
                <td style="padding-left: 12px;">
                    <div class="address-box">
                        <div class="address-label">Delivery Address</div>
                        <div class="address-name">
                            {{ $order->shipping_address['full_name'] ?? $order->user?->name }}
                        </div>
                        <div class="address-detail">
                            {{ $order->shipping_address['address'] ?? '' }}<br>
                            @if ($order->shipping_address['area'] ?? null)
                                {{ $order->shipping_address['area'] }},
                            @endif
                            {{ $order->shipping_address['county'] ?? '' }}<br>
                            Zone: {{ $order->shipping_address['zone'] ?? '—' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- QUOTATION TYPE STRIP                                                --}}
        {{-- ================================================================== --}}
        <table class="type-strip">
            <tr>
                <td>
                    <span class="strip-label">Quotation Type</span>
                    <span class="strip-value">
                        {{ $order->quotation_type === 'delivery' ? 'Delivery Quote' : 'Product Quote' }}
                    </span>
                </td>
                <td>
                    <span class="strip-label">Weight</span>
                    <span class="strip-value">
                        {{ $order->shipping_snapshot['weight_kg'] ?? '—' }} kg
                    </span>
                </td>
                <td>
                    <span class="strip-label">Items</span>
                    <span class="strip-value">{{ $order->items->sum('quantity') }}</span>
                </td>
                <td>
                    <span class="strip-label">Currency</span>
                    <span class="strip-value">{{ $order->currency }}</span>
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
                    <th style="width: 45%;">Description</th>
                    <th style="width: 10%;">SKU</th>
                    <th class="text-right" style="width: 8%;">Qty</th>
                    <th class="text-right" style="width: 16%;">Unit Price</th>
                    <th class="text-right" style="width: 16%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? '—';
                        $brand = $item->product_snapshot['brand'] ?? null;
                        $requiresQuote = $item->product_snapshot['requires_quotation'] ?? false;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $name }}</div>
                            @if ($brand)
                                <div class="item-sku">{{ $brand }}</div>
                            @endif
                            @if ($requiresQuote)
                                <span class="quote-badge">Custom price</span>
                            @endif
                        </td>
                        <td><span class="item-sku">{{ $sku }}</span></td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">KES {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                        <td class="text-right">KES {{ number_format($item->total_cents / 100, 2) }}</td>
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
                <td class="value">KES {{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if ($order->discount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value" style="color: #16a34a;">− KES {{ number_format($order->discount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Delivery</td>
                <td class="value">
                    @if ($order->shipping_cents === 0)
                        <span style="color: #b45309; font-style: italic;">See note</span>
                    @else
                        KES {{ number_format($order->shipping, 2) }}
                    @endif
                </td>
            </tr>
            <tr class="divider">
                <td></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>Quoted Total</td>
                <td style="text-align: right;">KES {{ number_format($order->total, 2) }}</td>
            </tr>
            @if ($order->shipping_cents === 0)
                <tr class="tbd-row">
                    <td colspan="2">* Delivery cost not yet included</td>
                </tr>
            @else
                <tr class="tbd-row">
                    <td colspan="2">* Prices inclusive of VAT at 16%</td>
                </tr>
            @endif
        </table>

        {{-- ================================================================== --}}
        {{-- HOW TO ACCEPT                                                       --}}
        {{-- ================================================================== --}}
        <table class="accept-box">
            <tr>
                <td>
                    <div class="accept-title">How to accept this quotation</div>
                    Log in to your account at <strong>www.sheffieldafrica.com</strong>,
                    navigate to <strong>My Quotations</strong>, find this quotation
                    (<strong>{{ $order->reference }}</strong>), and click <strong>Accept Quote</strong>
                    to proceed to payment.
                    @if ($order->expires_at)
                        This offer expires on <strong>{{ $order->expires_at->format('d M Y') }}</strong>.
                    @endif
                    For assistance, call <strong>+254 713 777 111</strong> or email
                    <strong>info@sheffieldafrica.com</strong>.
                </td>
            </tr>
        </table>

        {{-- ================================================================== --}}
        {{-- NOTES                                                               --}}
        {{-- ================================================================== --}}
        <div class="notes-box">
            <div class="notes-label">Terms & Conditions</div>
            1. This quotation is valid for the period stated above and is subject to stock availability.<br>
            2. Prices are inclusive of VAT at 16% unless otherwise stated.<br>
            3. Delivery charges are as quoted. Additional charges may apply for remote areas.<br>
            4. Payment is required in full before delivery is arranged.<br>
            5. Goods remain the property of SheffieldAfrica Steel Systems until full payment is received.<br>
            6. For warranty and returns policy, please visit www.sheffieldafrica.com/terms.
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
            This quotation was prepared by the Sheffield Africa Sales Team.
        </div>

    </div>
</body>

</html>
