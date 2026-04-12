<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{ $subject }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        a { color: #18181b; }
        img { display: block; max-width: 100%; }
        .wrapper { background: #f4f4f5; padding: 32px 16px; }
        .container { background: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background: #18181b; padding: 28px 32px; text-align: center; }
        .header .logo { color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; text-decoration: none; }
        .header .tagline { color: #a1a1aa; font-size: 12px; margin-top: 4px; }
        .hero { padding: 36px 32px 28px; text-align: center; border-bottom: 1px solid #f4f4f5; }
        .hero .icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .hero h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
        .hero p { color: #71717a; font-size: 14px; line-height: 1.6; }
        .section { padding: 24px 32px; border-bottom: 1px solid #f4f4f5; }
        .section-title { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #a1a1aa; margin-bottom: 14px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #71717a; }
        .detail-value { font-weight: 600; color: #18181b; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; letter-spacing: 0.3px; }
        .tracking-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 14px 16px; margin-top: 4px; }
        .tracking-box p { font-size: 13px; color: #15803d; }
        .tracking-box strong { font-size: 15px; display: block; margin-top: 3px; color: #14532d; letter-spacing: 0.5px; }
        .cta { padding: 28px 32px; text-align: center; }
        .btn { display: inline-block; background: #18181b; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .footer { background: #fafafa; border-top: 1px solid #e4e4e7; padding: 20px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #a1a1aa; line-height: 1.6; }
        .footer a { color: #71717a; }
        @media (max-width: 600px) {
            .hero, .section, .cta { padding-left: 20px; padding-right: 20px; }
            .header { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        {{-- Header --}}
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">{{ config('app.name') }}</a>
            <p class="tagline">Sheffield Africa Steel Systems</p>
        </div>

        {{-- Hero --}}
        @php
            $heroConfig = match ($newStatus) {
                \App\Enums\OrderStatus::CONFIRMED  => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'heading' => 'Order Confirmed!',          'body' => "Your order has been confirmed and we're getting things ready."],
                \App\Enums\OrderStatus::PROCESSING => ['bg' => '#eff6ff', 'color' => '#2563eb', 'heading' => 'Order Being Prepared',       'body' => "We're preparing your items for shipment."],
                \App\Enums\OrderStatus::SHIPPED    => ['bg' => '#fefce8', 'color' => '#ca8a04', 'heading' => 'Your Order is On Its Way!',  'body' => "Great news — your order has been shipped."],
                \App\Enums\OrderStatus::DELIVERED  => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'heading' => 'Order Delivered!',           'body' => "Your order has been delivered. We hope you love it!"],
                \App\Enums\OrderStatus::CANCELLED  => ['bg' => '#fef2f2', 'color' => '#dc2626', 'heading' => 'Order Cancelled',            'body' => "Your order has been cancelled. Contact us if you have questions."],
                default                            => ['bg' => '#f4f4f5', 'color' => '#71717a', 'heading' => 'Order Update',               'body' => "Your order status has been updated."],
            };

            $badgeConfig = match ($newStatus) {
                \App\Enums\OrderStatus::CONFIRMED  => ['bg' => '#dcfce7', 'color' => '#15803d'],
                \App\Enums\OrderStatus::PROCESSING => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                \App\Enums\OrderStatus::SHIPPED    => ['bg' => '#fef9c3', 'color' => '#854d0e'],
                \App\Enums\OrderStatus::DELIVERED  => ['bg' => '#dcfce7', 'color' => '#15803d'],
                \App\Enums\OrderStatus::CANCELLED  => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
                default                            => ['bg' => '#f4f4f5', 'color' => '#71717a'],
            };
        @endphp

        <div class="hero">
            <div class="icon" style="background: {{ $heroConfig['bg'] }};">
                @if ($newStatus === \App\Enums\OrderStatus::SHIPPED)
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" stroke="{{ $heroConfig['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="5.5" cy="18.5" r="2.5" stroke="{{ $heroConfig['color'] }}" stroke-width="2"/>
                        <circle cx="18.5" cy="18.5" r="2.5" stroke="{{ $heroConfig['color'] }}" stroke-width="2"/>
                    </svg>
                @elseif ($newStatus === \App\Enums\OrderStatus::CANCELLED)
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="{{ $heroConfig['color'] }}" stroke-width="2"/>
                        <line x1="15" y1="9" x2="9" y2="15" stroke="{{ $heroConfig['color'] }}" stroke-width="2" stroke-linecap="round"/>
                        <line x1="9" y1="9" x2="15" y2="15" stroke="{{ $heroConfig['color'] }}" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                @else
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="{{ $heroConfig['color'] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @endif
            </div>
            <h1>{{ $heroConfig['heading'] }}</h1>
            <p>Hi {{ $customerName }}, {{ $heroConfig['body'] }}</p>
        </div>

        {{-- Order details --}}
        <div class="section">
            <p class="section-title">Order Details</p>
            <div class="detail-row">
                <span class="detail-label">Order Reference</span>
                <span class="detail-value">{{ $order->reference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="status-badge" style="background: {{ $badgeConfig['bg'] }}; color: {{ $badgeConfig['color'] }};">
                    {{ $newStatus->label() }}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Total</span>
                <span class="detail-value">{{ format_currency($order->total) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Items</span>
                <span class="detail-value">{{ $order->items->count() }} item(s)</span>
            </div>
        </div>

        {{-- Tracking number (shipped only) --}}
        @if ($newStatus === \App\Enums\OrderStatus::SHIPPED && $order->tracking_number)
            <div class="section">
                <p class="section-title">Tracking Information</p>
                <div class="tracking-box">
                    <p>Your tracking number</p>
                    <strong>{{ $order->tracking_number }}</strong>
                </div>
            </div>
        @endif

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ $orderUrl }}" class="btn">View Order Details</a>
            <p style="margin-top: 14px; font-size: 12px; color: #a1a1aa;">
                Questions? <a href="mailto:info@sheffieldafrica.com" style="color: #71717a;">Contact our support team</a>.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                Off Old Mombasa Road, Nairobi, Kenya<br /><br />
                <span style="color: #d4d4d8;">You're receiving this because you placed an order at {{ config('app.name') }}.</span>
            </p>
        </div>

    </div>
</div>
</body>
</html>
