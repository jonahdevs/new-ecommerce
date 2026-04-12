<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Your Quotation is Ready — {{ $quote->reference }}</title>
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
        .hero .icon { width: 60px; height: 60px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .hero h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
        .hero p { color: #71717a; font-size: 14px; line-height: 1.6; }
        .expiry-banner { background: #fefce8; border-top: 1px solid #fef08a; border-bottom: 1px solid #fef08a; padding: 12px 32px; text-align: center; font-size: 13px; color: #854d0e; }
        .expiry-banner strong { color: #713f12; }
        .section { padding: 24px 32px; border-bottom: 1px solid #f4f4f5; }
        .section-title { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #a1a1aa; margin-bottom: 14px; }
        .item { padding: 12px 0; border-bottom: 1px solid #f4f4f5; display: flex; gap: 12px; align-items: flex-start; }
        .item:last-child { border-bottom: none; padding-bottom: 0; }
        .item-info { flex: 1; min-width: 0; }
        .item-name { font-size: 14px; font-weight: 500; color: #18181b; }
        .item-meta { font-size: 12px; color: #71717a; margin-top: 2px; }
        .item-price { font-size: 14px; font-weight: 600; color: #18181b; white-space: nowrap; flex-shrink: 0; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .totals-row .label { color: #71717a; }
        .totals-row .value { font-weight: 600; }
        .totals-row.total { font-size: 16px; font-weight: 700; border-top: 1px solid #e4e4e7; margin-top: 6px; padding-top: 12px; }
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
            .hero, .section, .cta { padding-left: 20px; padding-right: 20px; }
            .header, .expiry-banner { padding-left: 20px; padding-right: 20px; }
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
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="14 2 14 8 20 8" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="16" y1="13" x2="8" y2="13" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                    <line x1="16" y1="17" x2="8" y2="17" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>Your Quotation is Ready</h1>
            <p>
                Hi {{ $customerName }}, your quotation from Sheffield Africa has been priced and is ready for your review.
            </p>
        </div>

        {{-- Expiry banner --}}
        @if ($quote->expires_at)
            <div class="expiry-banner">
                This quotation is valid until <strong>{{ $quote->expires_at->format('M d, Y') }}</strong>.
                Please respond before it expires.
            </div>
        @endif

        {{-- Quote meta --}}
        <div class="section">
            <p class="section-title">Quotation Details</p>
            <div class="detail-row">
                <span class="detail-label">Reference</span>
                <span class="detail-value">{{ $quote->reference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date Issued</span>
                <span class="detail-value">{{ $quote->quoted_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
            </div>
            @if ($quote->expires_at)
                <div class="detail-row">
                    <span class="detail-label">Valid Until</span>
                    <span class="detail-value" style="color: #854d0e;">{{ $quote->expires_at->format('d M Y') }}</span>
                </div>
            @endif
        </div>

        {{-- Items --}}
        <div class="section">
            <p class="section-title">Quoted Items</p>
            @foreach ($quote->items as $item)
                @php
                    $unitPrice = $item->quoted_price_cents ?? $item->original_price_cents;
                    $lineTotal = $unitPrice * $item->quantity;
                @endphp
                <div class="item">
                    <div class="item-info">
                        <p class="item-name">{{ $item->product_snapshot['name'] ?? 'Product' }}</p>
                        <p class="item-meta">
                            Qty: {{ $item->quantity }}
                            &nbsp;·&nbsp;
                            {{ format_currency($unitPrice / 100) }} each
                            @if ($item->product_snapshot['sku'] ?? null)
                                &nbsp;·&nbsp; SKU: {{ $item->product_snapshot['sku'] }}
                            @endif
                        </p>
                    </div>
                    <span class="item-price">{{ format_currency($lineTotal / 100) }}</span>
                </div>
            @endforeach
        </div>

        {{-- Totals --}}
        <div class="section">
            <p class="section-title">Pricing Summary</p>
            <div class="totals-row">
                <span class="label">Subtotal</span>
                <span class="value">{{ format_currency($quote->subtotal) }}</span>
            </div>
            @if ($quote->discount > 0)
                <div class="totals-row" style="color: #16a34a;">
                    <span>Discount</span>
                    <span>− {{ format_currency($quote->discount) }}</span>
                </div>
            @endif
            <div class="totals-row">
                <span class="label">Delivery</span>
                <span class="value">{{ $quote->shipping_cents > 0 ? format_currency($quote->shipping) : 'Free' }}</span>
            </div>
            <div class="totals-row total">
                <span>Total</span>
                <span>{{ format_currency($quote->total) }}</span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ $portalUrl }}" class="btn">Review &amp; Respond to Quotation</a>
            <p style="margin-top: 14px; font-size: 12px; color: #a1a1aa;">
                You can accept or reject this quotation from your account portal.<br />
                Questions? <a href="mailto:info@sheffieldafrica.com" style="color: #71717a;">Reply to this email</a> or call <strong style="color: #3f3f46;">+254 713 777 111</strong>.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                Off Old Mombasa Road, Nairobi, Kenya<br /><br />
                <span style="color: #d4d4d8;">You're receiving this because you submitted a quote request at {{ config('app.name') }}.</span>
            </p>
        </div>

    </div>
</div>
</body>
</html>
