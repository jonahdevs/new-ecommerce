<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Quotation Expiring Soon — {{ $quote->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        a { color: #18181b; }
        .wrapper { background: #f4f4f5; padding: 32px 16px; }
        .container { background: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background: #18181b; padding: 28px 32px; text-align: center; }
        .header .logo { color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; text-decoration: none; }
        .header .tagline { color: #a1a1aa; font-size: 12px; margin-top: 4px; }
        .urgency-bar { padding: 16px 32px; text-align: center; }
        .urgency-pill { display: inline-block; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; letter-spacing: 0.3px; }
        .hero { padding: 28px 32px; text-align: center; border-bottom: 1px solid #f4f4f5; }
        .hero .icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .hero h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
        .hero p { color: #71717a; font-size: 14px; line-height: 1.6; }
        .section { padding: 24px 32px; border-bottom: 1px solid #f4f4f5; }
        .section-title { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #a1a1aa; margin-bottom: 14px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #71717a; }
        .detail-value { font-weight: 600; color: #18181b; }
        .cta { padding: 28px 32px; text-align: center; }
        .btn { display: inline-block; background: #18181b; color: #ffffff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .footer { background: #fafafa; border-top: 1px solid #e4e4e7; padding: 20px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #a1a1aa; line-height: 1.6; }
        .footer a { color: #71717a; }
        @media (max-width: 600px) {
            .hero, .section, .cta, .urgency-bar { padding-left: 20px; padding-right: 20px; }
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

        {{-- Urgency pill --}}
        @php
            $isLastDay = $daysLeft <= 1;
            $pillBg    = $isLastDay ? '#fee2e2' : '#fef9c3';
            $pillColor = $isLastDay ? '#b91c1c' : '#854d0e';
            $iconBg    = $isLastDay ? '#fef2f2' : '#fefce8';
            $iconColor = $isLastDay ? '#dc2626' : '#ca8a04';
        @endphp

        <div class="urgency-bar" style="background: {{ $pillBg }}; border-bottom: 1px solid {{ $isLastDay ? '#fecaca' : '#fef08a' }};">
            <span class="urgency-pill" style="background: {{ $pillBg }}; color: {{ $pillColor }};">
                ⏰ {{ $isLastDay ? 'Expires Tomorrow!' : "Expires in {$daysLeft} days" }}
            </span>
        </div>

        {{-- Hero --}}
        <div class="hero">
            <div class="icon" style="background: {{ $iconBg }};">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="{{ $iconColor }}" stroke-width="2"/>
                    <polyline points="12 6 12 12 16 14" stroke="{{ $iconColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>Your Quotation {{ $isLastDay ? 'Expires Tomorrow' : "Expires in {$daysLeft} Days" }}</h1>
            <p>
                Hi {{ $customerName }}, this is a reminder that your quotation
                <strong style="color: #18181b;">{{ $quote->reference }}</strong> is about to expire.
                Please review and respond before it's too late.
            </p>
        </div>

        {{-- Quote details --}}
        <div class="section">
            <p class="section-title">Quotation Details</p>
            <div class="detail-row">
                <span class="detail-label">Reference</span>
                <span class="detail-value">{{ $quote->reference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total</span>
                <span class="detail-value">{{ format_currency($quote->total) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Items</span>
                <span class="detail-value">{{ $quote->items->count() }} item(s)</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Expires On</span>
                <span class="detail-value" style="color: {{ $pillColor }};">
                    {{ $quote->expires_at?->format('d M Y') }}
                </span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ $portalUrl }}" class="btn">View &amp; Respond to Quotation</a>
            <p style="margin-top: 14px; font-size: 12px; color: #a1a1aa;">
                Once expired, you'll need to submit a new quote request.<br />
                Questions? <a href="mailto:info@sheffieldafrica.com" style="color: #71717a;">Contact our sales team</a>
                or call <strong style="color: #3f3f46;">+254 713 777 111</strong>.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                Off Old Mombasa Road, Nairobi, Kenya<br /><br />
                <span style="color: #d4d4d8;">You're receiving this reminder because you have an active quotation at {{ config('app.name') }}.</span>
            </p>
        </div>

    </div>
</div>
</body>
</html>
