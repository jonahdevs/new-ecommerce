<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tax Invoice — {{ $order->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        a { color: #18181b; }
        .wrapper { background: #f4f4f5; padding: 32px 16px; }
        .container { background: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background: #18181b; padding: 28px 32px; text-align: center; }
        .header .logo { color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; text-decoration: none; }
        .header .tagline { color: #a1a1aa; font-size: 12px; margin-top: 4px; }
        .hero { padding: 36px 32px 28px; text-align: center; border-bottom: 1px solid #f4f4f5; }
        .hero .icon { width: 60px; height: 60px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .hero h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
        .hero p { color: #71717a; font-size: 14px; line-height: 1.6; }
        .section { padding: 24px 32px; border-bottom: 1px solid #f4f4f5; }
        .section-title { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #a1a1aa; margin-bottom: 14px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #71717a; }
        .detail-value { font-weight: 600; color: #18181b; }
        .kra-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 16px; }
        .kra-box p { font-size: 12px; color: #15803d; margin-bottom: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .kra-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; color: #166534; }
        .kra-row span:first-child { color: #15803d; }
        .kra-row span:last-child { font-weight: 600; }
        .attachment-note { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 6px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
        .attachment-note p { font-size: 13px; color: #3f3f46; line-height: 1.5; }
        .attachment-note p strong { color: #18181b; display: block; margin-bottom: 2px; }
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
        <div class="hero">
            <div class="icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="14 2 14 8 20 8" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="9 12 12 15 15 12" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="12" y1="8" x2="12" y2="15" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>Your Tax Invoice is Ready</h1>
            <p>
                Hi {{ $customerName }}, your KRA-validated tax invoice for order
                <strong style="color: #18181b;">{{ $order->reference }}</strong> is attached to this email.
            </p>
        </div>

        {{-- Order summary --}}
        <div class="section">
            <p class="section-title">Order Summary</p>
            <div class="detail-row">
                <span class="detail-label">Order Reference</span>
                <span class="detail-value">{{ $order->reference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Invoice Number</span>
                <span class="detail-value">{{ str_replace('SO-', 'INV-', $order->reference) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Total</span>
                <span class="detail-value">{{ format_currency($order->total) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Date</span>
                <span class="detail-value">{{ $order->payment?->paid_at?->format('d M Y') ?? '—' }}</span>
            </div>
        </div>

        {{-- KRA details --}}
        <div class="section">
            <p class="section-title">KRA eTIMS Compliance</p>
            <div class="kra-box">
                <p>Validated Details</p>
                @if ($order->kra_cu_number)
                    <div class="kra-row">
                        <span>CU Number</span>
                        <span>{{ $order->kra_cu_number }}</span>
                    </div>
                @endif
                @if ($order->kra_validated_at)
                    <div class="kra-row">
                        <span>Validated At</span>
                        <span>{{ $order->kra_validated_at->format('d M Y, H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Attachment note --}}
        <div class="section">
            <div class="attachment-note">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="flex-shrink: 0;">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" stroke="#a1a1aa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p>
                    <strong>Invoice-{{ $order->reference }}.pdf attached</strong>
                    Please keep this invoice for your records. It is a legally valid KRA tax invoice.
                </p>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ $orderUrl }}" class="btn">View Order Online</a>
            <p style="margin-top: 14px; font-size: 12px; color: #a1a1aa;">
                Questions about this invoice?
                <a href="mailto:info@sheffieldafrica.com" style="color: #71717a;">Contact us</a>.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                Off Old Mombasa Road, Nairobi, Kenya · PIN: P051234567X<br /><br />
                <span style="color: #d4d4d8;">You're receiving this because you placed an order at {{ config('app.name') }}.</span>
            </p>
        </div>

    </div>
</div>
</body>
</html>
