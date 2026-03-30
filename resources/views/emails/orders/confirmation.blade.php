<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Confirmed – {{ $order->reference }}</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .header { background: #c02434; padding: 28px 32px; text-align: center; }
        .header img { height: 40px; }
        .header h1 { color: #fff; margin: 12px 0 0; font-size: 20px; font-weight: 600; }
        .body { padding: 32px; }
        .greeting { font-size: 16px; margin-bottom: 8px; }
        .intro { color: #52525b; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
        .ref-box { background: #f4f4f5; border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .ref-box .label { font-size: 12px; color: #71717a; text-transform: uppercase; letter-spacing: .05em; }
        .ref-box .value { font-size: 18px; font-weight: 700; color: #18181b; }
        .section-title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #71717a; margin: 0 0 12px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items th { font-size: 12px; color: #71717a; text-align: left; padding: 0 0 8px; border-bottom: 1px solid #e4e4e7; }
        table.items th:last-child { text-align: right; }
        table.items td { padding: 12px 0; border-bottom: 1px solid #f4f4f5; font-size: 14px; vertical-align: top; }
        table.items td:last-child { text-align: right; font-weight: 600; white-space: nowrap; }
        .item-name { font-weight: 500; }
        .item-meta { font-size: 12px; color: #71717a; margin-top: 2px; }
        .totals { margin-bottom: 24px; }
        .totals .row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; color: #52525b; }
        .totals .row.total { font-size: 16px; font-weight: 700; color: #18181b; border-top: 1px solid #e4e4e7; padding-top: 12px; margin-top: 8px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .info-card { background: #f9f9f9; border-radius: 6px; padding: 14px 16px; }
        .info-card .label { font-size: 11px; color: #71717a; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
        .info-card .value { font-size: 13px; font-weight: 500; line-height: 1.5; }
        .cta { text-align: center; margin: 28px 0; }
        .cta a { background: #c02434; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-block; }
        .footer { background: #f4f4f5; padding: 20px 32px; text-align: center; font-size: 12px; color: #71717a; }
        .footer a { color: #71717a; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <h1>Order Confirmed ✓</h1>
    </div>

    <div class="body">

        <p class="greeting">Hi {{ $customerName }},</p>
        <p class="intro">
            Thank you for your order! We've received your payment and your order is now confirmed.
            We'll keep you updated as it progresses.
        </p>

        {{-- Order reference --}}
        <div class="ref-box">
            <div>
                <div class="label">Order Reference</div>
                <div class="value">{{ $order->reference }}</div>
            </div>
            <div style="text-align:right">
                <div class="label">Date</div>
                <div style="font-size:14px;font-weight:500;">{{ $order->created_at->format('M j, Y') }}</div>
            </div>
        </div>

        {{-- Order items --}}
        <p class="section-title">Items Ordered</p>
        <table class="items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align:center">Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->product_snapshot['name'] ?? $item->product?->name }}</div>
                            @if (!empty($item->variant_snapshot['attributes']))
                                <div class="item-meta">
                                    {{ collect($item->variant_snapshot['attributes'])->map(fn($v, $k) => "$k: $v")->implode(', ') }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align:center;color:#52525b;">{{ $item->quantity }}</td>
                        <td>{{ format_currency($item->total_cents / 100) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>{{ format_currency($order->subtotal) }}</span>
            </div>
            @if ($order->discount > 0)
                <div class="row" style="color:#16a34a;">
                    <span>Discount</span>
                    <span>− {{ format_currency($order->discount) }}</span>
                </div>
            @endif
            <div class="row">
                <span>Shipping</span>
                <span>{{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}</span>
            </div>
            <div class="row total">
                <span>Total Paid</span>
                <span>{{ format_currency($order->total) }}</span>
            </div>
        </div>

        {{-- Info grid --}}
        <div class="info-grid">
            <div class="info-card">
                <div class="label">Payment Method</div>
                <div class="value">{{ $paymentLabel }}</div>
            </div>
            @if ($deliveryWindow)
                <div class="info-card">
                    <div class="label">Estimated Delivery</div>
                    <div class="value">{{ $deliveryWindow }}</div>
                </div>
            @endif
            @if ($order->shipping_snapshot['method_name'] ?? null)
                <div class="info-card">
                    <div class="label">Shipping Method</div>
                    <div class="value">{{ $order->shipping_snapshot['method_name'] }}</div>
                </div>
            @endif
            @if ($order->shipping_snapshot['station_name'] ?? null)
                <div class="info-card">
                    <div class="label">Pickup Station</div>
                    <div class="value">{{ $order->shipping_snapshot['station_name'] }}</div>
                </div>
            @endif
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ route('customer.orders.show', $order) }}">View Order Details</a>
        </div>

        <p style="font-size:13px;color:#71717a;text-align:center;margin:0;">
            Questions? Reply to this email or contact us at
            <a href="mailto:info@sheffieldafrica.com" style="color:#c02434;">info@sheffieldafrica.com</a>
        </p>

    </div>

    <div class="footer">
        © {{ date('Y') }} Sheffield Africa. All rights reserved.<br>
        <a href="{{ config('app.url') }}">sheffieldafrica.com</a>
    </div>

</div>
</body>
</html>
