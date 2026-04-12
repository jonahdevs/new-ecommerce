<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Reset Your Password</title>
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
        .hero .icon { width: 60px; height: 60px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .hero h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
        .hero p { color: #71717a; font-size: 14px; line-height: 1.6; max-width: 400px; margin: 0 auto; }
        .section { padding: 28px 32px; border-bottom: 1px solid #f4f4f5; }
        .expiry-note { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #71717a; text-align: center; margin-top: 8px; }
        .security-note { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #991b1b; line-height: 1.5; }
        .cta { padding: 32px 32px; text-align: center; }
        .btn { display: inline-block; background: #18181b; color: #ffffff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .url-fallback { margin-top: 16px; font-size: 11px; color: #a1a1aa; word-break: break-all; }
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
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="#2563eb" stroke-width="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>Reset Your Password</h1>
            <p>
                We received a request to reset the password for your account.
                Click the button below to choose a new password.
            </p>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            <div class="expiry-note">
                This link expires in {{ config('auth.passwords.users.expire', 60) }} minutes.
            </div>
            <div class="url-fallback">
                If the button doesn't work, copy and paste this URL into your browser:<br />
                <a href="{{ $resetUrl }}" style="color: #71717a;">{{ $resetUrl }}</a>
            </div>
        </div>

        {{-- Security note --}}
        <div class="section">
            <div class="security-note">
                <strong>Didn't request this?</strong> If you didn't request a password reset, no action is needed —
                your account is safe. You can safely ignore this email.
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                Off Old Mombasa Road, Nairobi, Kenya<br /><br />
                <span style="color: #d4d4d8;">You're receiving this because a password reset was requested for your account.</span>
            </p>
        </div>

    </div>
</div>
</body>
</html>
