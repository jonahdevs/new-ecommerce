<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Verify Your Email Address</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        a { color: #18181b; }
        .wrapper { background: #f4f4f5; padding: 32px 16px; }
        .container { background: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background: #18181b; padding: 28px 32px; text-align: center; }
        .header .logo { color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; text-decoration: none; }
        .header .tagline { color: #a1a1aa; font-size: 12px; margin-top: 4px; }
        .hero { padding: 40px 32px 32px; text-align: center; border-bottom: 1px solid #f4f4f5; }
        .hero .icon { width: 60px; height: 60px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .hero h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
        .hero p { color: #71717a; font-size: 14px; line-height: 1.6; max-width: 400px; margin: 0 auto; }
        .cta { padding: 32px 32px; text-align: center; }
        .btn { display: inline-block; background: #18181b; color: #ffffff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .expiry-note { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #71717a; text-align: center; margin-top: 8px; }
        .url-fallback { margin-top: 16px; font-size: 11px; color: #a1a1aa; word-break: break-all; }
        .section { padding: 24px 32px; border-bottom: 1px solid #f4f4f5; }
        .benefit { display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; }
        .benefit-dot { width: 7px; height: 7px; background: #16a34a; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
        .benefit p { font-size: 13px; color: #3f3f46; line-height: 1.5; }
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
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="22,6 12,13 2,6" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>Verify Your Email Address</h1>
            <p>
                Hi {{ $user->name }}, thanks for registering! Please verify your email address
                to activate your account and start shopping.
            </p>
        </div>

        {{-- What you unlock --}}
        <div class="section">
            <p style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #a1a1aa; margin-bottom: 14px;">
                After verifying you can
            </p>
            <div class="benefit">
                <div class="benefit-dot"></div>
                <p>Browse and order from our full catalog of steel and construction products</p>
            </div>
            <div class="benefit">
                <div class="benefit-dot"></div>
                <p>Request custom quotations for bulk orders with competitive pricing</p>
            </div>
            <div class="benefit">
                <div class="benefit-dot"></div>
                <p>Track your orders and download KRA-validated tax invoices</p>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ $verificationUrl }}" class="btn">Verify Email Address</a>
            <div class="expiry-note">
                This link expires in 60 minutes.
            </div>
            <div class="url-fallback">
                If the button doesn't work, copy and paste this URL into your browser:<br />
                <a href="{{ $verificationUrl }}" style="color: #71717a;">{{ $verificationUrl }}</a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                Off Old Mombasa Road, Nairobi, Kenya<br /><br />
                <span style="color: #d4d4d8;">You're receiving this because you created an account at {{ config('app.name') }}.</span>
            </p>
        </div>

    </div>
</div>
</body>
</html>
