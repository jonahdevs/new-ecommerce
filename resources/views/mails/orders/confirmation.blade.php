<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Order Confirmed – {{ $order->reference }}</title>
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f4f4f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #18181b;
        }

        a {
            color: #18181b;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* Layout */
        .wrapper {
            background: #f4f4f5;
            padding: 32px 16px;
        }

        .container {
            background: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e4e4e7;
        }

        /* Header */
        .header {
            background: #18181b;
            padding: 28px 32px;
            text-align: center;
        }

        .header .logo {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .header .tagline {
            color: #a1a1aa;
            font-size: 12px;
            margin-top: 4px;
        }

        /* Hero */
        .hero {
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid #f4f4f5;
        }

        .hero .icon {
            width: 56px;
            height: 56px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .hero h1 {
            font-size: 22px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 8px;
        }

        .hero p {
            color: #71717a;
            font-size: 14px;
            line-height: 1.6;
        }

        .hero .reference {
            display: inline-block;
            background: #f4f4f5;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #18181b;
            margin-top: 12px;
        }

        /* Section */
        .section {
            padding: 24px 32px;
            border-bottom: 1px solid #f4f4f5;
        }

        .section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #a1a1aa;
            margin-bottom: 16px;
        }

        /* Items */
        .item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f4f4f5;
        }

        .item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-image {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            object-fit: cover;
            flex-shrink: 0;
        }

        .item-image-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            flex-shrink: 0;
        }

        .item-name {
            font-size: 14px;
            font-weight: 500;
            color: #18181b;
        }

        .item-meta {
            font-size: 12px;
            color: #71717a;
            margin-top: 3px;
        }

        .item-price {
            font-size: 14px;
            font-weight: 600;
            color: #18181b;
            margin-left: auto;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Totals */
        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
        }

        .totals-row.discount {
            color: #16a34a;
        }

        .totals-row.total {
            font-weight: 700;
            font-size: 16px;
            border-top: 1px solid #e4e4e7;
            padding-top: 12px;
            margin-top: 6px;
        }

        /* Two col */
        .two-col {
            display: flex;
            gap: 16px;
        }

        .two-col .col {
            flex: 1;
            background: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 16px;
        }

        .col-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #a1a1aa;
            margin-bottom: 8px;
        }

        .col-value {
            font-size: 13px;
            color: #3f3f46;
            line-height: 1.6;
        }

        .col-value strong {
            color: #18181b;
            font-weight: 600;
        }

        /* Next steps */
        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 0;
        }

        .step-num {
            width: 22px;
            height: 22px;
            background: #18181b;
            color: #ffffff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .step-text {
            font-size: 13px;
            color: #3f3f46;
            line-height: 1.5;
            padding-top: 2px;
        }

        /* CTA */
        .cta {
            padding: 28px 32px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            background: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        /* Footer */
        .footer {
            background: #fafafa;
            border-top: 1px solid #e4e4e7;
            padding: 20px 32px;
            text-align: center;
        }

        .footer p {
            font-size: 12px;
            color: #a1a1aa;
            line-height: 1.6;
        }

        .footer a {
            color: #71717a;
        }

        /* Mobile */
        @media (max-width: 600px) {
            .section {
                padding: 20px;
            }

            .hero {
                padding: 24px 20px;
            }

            .cta {
                padding: 20px;
            }

            .two-col {
                flex-direction: column;
            }

            .header {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">

            {{-- Header --}}
            <div class="header">
                <a href="{{ config('app.url') }}" class="logo">
                    {{ config('app.name') }}
                </a>
                <p class="tagline">Commercial Kitchen Equipment</p>
            </div>

            {{-- Hero --}}
            <div class="hero">
                <div class="icon">
                    {{-- Checkmark SVG --}}
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <h1>Your order is confirmed!</h1>
                <p>
                    Hi {{ $customerName }}, thank you for your purchase.<br />
                    We've received your order and will get it ready for you shortly.
                </p>
                <span class="reference">{{ $order->reference }}</span>
            </div>

            {{-- What happens next --}}
            <div class="section">
                <p class="section-title">What happens next</p>
                <div class="step">
                    <div class="step-num">1</div>
                    <p class="step-text">We're preparing your order for dispatch.</p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <p class="step-text">You'll receive a notification when your order is on its way.</p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <p class="step-text">
                        @if ($deliveryWindow)
                            Estimated delivery: <strong>{{ $deliveryWindow }}</strong>.
                        @else
                            Delivery time will be communicated shortly.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Order items --}}
            <div class="section">
                <p class="section-title">Order Items</p>

                @foreach ($order->items as $item)
                    <div class="item">
                        @if ($item->getProductImagePath())
                            <img class="item-image"
                                src="{{ $item->product_image_url ?? asset('storage/' . $item->getProductImagePath()) }}"
                                alt="{{ $item->getProductName() }}" />
                        @else
                            <div class="item-image-placeholder"></div>
                        @endif

                        <div style="flex: 1; min-width: 0;">
                            <p class="item-name">{{ $item->getProductName() }}</p>
                            <p class="item-meta">
                                Qty: {{ $item->quantity }}
                                · {{ format_currency($item->unit_price_cents / 100) }} each
                                @if ($item->getProductSku())
                                    · SKU: {{ $item->getProductSku() }}
                                @endif
                            </p>
                        </div>

                        <span class="item-price">
                            {{ format_currency($item->total_cents / 100) }}
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Totals --}}
            <div class="section">
                <p class="section-title">Order Summary</p>

                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>{{ format_currency($order->subtotal) }}</span>
                </div>

                @if ($order->discount > 0)
                    <div class="totals-row discount">
                        <span>Discount</span>
                        <span>− {{ format_currency($order->discount) }}</span>
                    </div>
                @endif

                <div class="totals-row">
                    <span>Shipping</span>
                    <span>{{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}</span>
                </div>

                <div class="totals-row total">
                    <span>Total Paid</span>
                    <span>{{ format_currency($order->total) }}</span>
                </div>
            </div>

            {{-- Address + Shipping --}}
            <div class="section">
                <div class="two-col">
                    <div class="col">
                        <p class="col-title">Delivering to</p>
                        <div class="col-value">
                            <strong>{{ $order->shipping_address['full_name'] ?? '' }}</strong><br />
                            {{ $order->shipping_address['address'] ?? '' }}<br />
                            {{ implode(
                                ', ',
                                array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                            ) }}<br />
                            {{ format_phone($order->shipping_address['phone_number'] ?? '') }}
                        </div>
                    </div>

                    <div class="col">
                        <p class="col-title">Shipping & Payment</p>
                        <div class="col-value">
                            <strong>{{ $order->deliveryOrder?->shippingMethod?->name ?? '—' }}</strong><br />
                            @if ($deliveryWindow)
                                Est. {{ $deliveryWindow }}<br />
                            @endif
                            @if ($order->deliveryOrder?->pickupStation)
                                Pickup: {{ $order->deliveryOrder->pickupStation->name }}<br />
                            @endif
                            <br />
                            <strong>{{ $paymentLabel }}</strong><br />
                            <span style="color: #16a34a; font-weight: 600;">✓ Paid</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="cta">
                <a href="{{ route('orders.confirmation', $order) }}" class="btn">
                    View Order Details
                </a>
                <p style="margin-top: 14px; font-size: 12px; color: #a1a1aa;">
                    Or visit
                    <a href="{{ route('customer.orders.index') }}" style="color: #71717a;">
                        your orders page
                    </a>
                    to track all your orders.
                </p>
            </div>

            {{-- Footer --}}
            <div class="footer">
                <p>
                    {{ config('app.name') }} · Commercial Kitchen Equipment<br />
                    Kenya · Uganda · Rwanda<br />
                    <br />
                    Questions? Reply to this email or
                    <a href="{{ config('app.url') }}/contact">contact our support team</a>.<br />
                    <br />
                    <span style="color: #d4d4d8;">
                        You're receiving this because you placed an order at {{ config('app.name') }}.
                    </span>
                </p>
            </div>

        </div>
    </div>
</body>

</html>
