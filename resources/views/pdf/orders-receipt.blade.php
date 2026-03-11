<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
            color: #18181b;
            margin: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
        }

        .divider {
            border-top: 2px dashed #e4e4e7;
            margin: 16px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #a1a1aa;
            padding-bottom: 8px;
        }

        td {
            padding: 6px 0;
            font-size: 13px;
        }

        .total-row {
            font-weight: bold;
            font-size: 15px;
            border-top: 1px dashed #e4e4e7;
            padding-top: 8px;
        }

        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #71717a;
        }
    </style>
</head>

<body>

    <div class="header">
        <div>
            <div class="title">Receipt</div>
            <div style="color: #71717a">Order #{{ $order->reference }}</div>
        </div>
        <div style="text-align: right; font-size: 12px; color: #71717a">
            <strong style="color: #18181b">{{ config('site.site.name') }}</strong><br>
            {{ $order->created_at->format('M j, Y') }}
        </div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 55%">Item</th>
                <th style="width: 15%; text-align: center">Qty</th>
                <th style="width: 30%; text-align: right">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_snapshot['name'] ?? 'Product' }}</td>
                    <td style="text-align: center">{{ $item->quantity }}</td>
                    <td style="text-align: right">
                        {{ format_currency($item->total_cents / 100) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table style="width: 260px; margin-left: auto">
        <tr>
            <td style="color: #71717a">Subtotal</td>
            <td style="text-align: right">{{ format_currency($order->subtotal) }}</td>
        </tr>
        @if ($order->discount > 0)
            <tr>
                <td style="color: #16a34a">Discount</td>
                <td style="text-align: right; color: #16a34a">
                    − {{ format_currency($order->discount) }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="color: #71717a">Shipping</td>
            <td style="text-align: right">
                {{ $order->shipping == 0 ? 'FREE' : format_currency($order->shipping) }}
            </td>
        </tr>
        <tr class="total-row">
            <td>Total</td>
            <td style="text-align: right">{{ format_currency($order->total) }}</td>
        </tr>
    </table>

    <div class="footer">
        <div>
            <strong style="color: #18181b">Delivering To</strong><br>
            {{ $order->shipping_address['full_name'] }}<br>
            {{ $order->shipping_address['address'] }}
        </div>
        <div style="text-align: right">
            <strong style="color: #18181b">Payment</strong><br>
            {{ $order->payment?->gateway === 'stripe' ? 'Card' : 'M-Pesa' }}<br>
            Ref: {{ $order->payment?->transaction_id ?? '—' }}<br>
            Paid: {{ $order->payment?->paid_at?->format('M j, Y') }}
        </div>
    </div>

</body>

</html>
