<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
        }

        .page {
            padding: 32px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 16px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .header p {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        /* KRA stamp block */
        .kra-block {
            background: #f4f9f4;
            border: 1px solid #2a7a2a;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .kra-block h2 {
            font-size: 12px;
            font-weight: 700;
            color: #1a5c1a;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .kra-row {
            display: flex;
            gap: 32px;
        }

        .kra-field label {
            font-size: 9px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kra-field p {
            font-size: 12px;
            font-weight: 700;
            color: #1a1a1a;
            margin-top: 2px;
        }

        /* Order meta */
        .meta-grid {
            display: flex;
            gap: 40px;
            margin-bottom: 20px;
        }

        .meta-col label {
            font-size: 9px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-col p {
            font-size: 11px;
            margin-top: 2px;
        }

        /* Line items table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        thead th {
            background: #1a1a1a;
            color: #fff;
            padding: 7px 10px;
            font-size: 10px;
            text-align: left;
        }

        thead th.right {
            text-align: right;
        }

        tbody tr:nth-child(even) {
            background: #f7f7f7;
        }

        tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #e8e8e8;
            vertical-align: top;
        }

        tbody td.right {
            text-align: right;
        }

        /* Totals */
        .totals {
            width: 260px;
            margin-left: auto;
            margin-bottom: 20px;
        }

        .totals table {
            margin-bottom: 0;
        }

        .totals td {
            padding: 4px 8px;
            font-size: 11px;
        }

        .totals .total-row td {
            font-weight: 700;
            font-size: 13px;
            border-top: 1.5px solid #1a1a1a;
            padding-top: 6px;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 12px;
            margin-top: 8px;
            font-size: 9px;
            color: #777;
            text-align: center;
        }

        .footer strong {
            color: #1a1a1a;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- Business header --}}
        <div class="header">
            <div>
                <h1>{{ config('app.name') }}</h1>
                <p>PIN: <strong>{{ $businessPin }}</strong></p>
                <p>Nairobi, Kenya</p>
            </div>
            <div style="text-align:right">
                <p style="font-size:14px; font-weight:700;">KRA TAX RECEIPT</p>
                <p style="color:#555">{{ $order->reference }}</p>
                <p style="color:#555">{{ $order->kra_validated_at?->format('d M Y, H:i') }}</p>
            </div>
        </div>

        {{-- KRA validation block — the most important section --}}
        <div class="kra-block">
            <h2>KRA VALIDATION — eTIMS CERTIFIED</h2>
            <div class="kra-row">
                <div class="kra-field">
                    <label>CU Number</label>
                    <p>{{ $order->kra_cu_number }}</p>
                </div>
                <div class="kra-field">
                    <label>KRA Invoice Number</label>
                    <p>{{ $order->kra_invoice_number }}</p>
                </div>
                <div class="kra-field">
                    <label>Validated at</label>
                    <p>{{ $order->kra_validated_at?->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Order & customer meta --}}
        <div class="meta-grid">
            <div class="meta-col">
                <label>Order reference</label>
                <p>{{ $order->reference }}</p>
            </div>
            <div class="meta-col">
                <label>Customer</label>
                <p>{{ $order->customerName() }}</p>
                <p style="color:#555">{{ $order->customerEmail() }}</p>
            </div>
            <div class="meta-col">
                <label>SAP Invoice</label>
                <p>{{ $order->sap_invoice_number }}</p>
            </div>
            <div class="meta-col">
                <label>Currency</label>
                <p>{{ $order->currency }}</p>
            </div>
        </div>

        {{-- Line items --}}
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit price</th>
                    <th class="right">VAT</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lineItems as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td style="color:#555">{{ $item['sku'] }}</td>
                        <td class="right">{{ $item['quantity'] }}</td>
                        <td class="right">{{ number_format($item['unit_price'], 2) }}</td>
                        <td class="right">{{ number_format($item['tax'], 2) }}</td>
                        <td class="right">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td class="right">{{ number_format($order->subtotal_cents / 100, 2) }}</td>
                </tr>
                @if ($order->discount_cents > 0)
                    <tr>
                        <td>Discount</td>
                        <td class="right">- {{ number_format($order->discount_cents / 100, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Shipping</td>
                    <td class="right">{{ number_format($order->shipping_cents / 100, 2) }}</td>
                </tr>
                <tr>
                    <td>VAT ({{ $vatBreakdown['vat_rate'] }})</td>
                    <td class="right">{{ number_format($vatBreakdown['vat_amount'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total ({{ $order->currency }})</td>
                    <td class="right">{{ number_format($order->total_cents / 100, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>This is a KRA-certified tax receipt. CU Number: <strong>{{ $order->kra_cu_number }}</strong></p>
            <p style="margin-top:4px">Generated by {{ config('app.name') }} · Business PIN:
                <strong>{{ $businessPin }}</strong>
            </p>
        </div>

    </div>
</body>

</html>
