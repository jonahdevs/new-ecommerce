<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
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
            padding: 40px 32px 32px;
            text-align: center;
            border-bottom: 1px solid #f4f4f5;
        }

        .hero .icon {
            width: 64px;
            height: 64px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .hero h1 {
            font-size: 24px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 10px;
        }

        .hero p {
            color: #71717a;
            font-size: 14px;
            line-height: 1.7;
            max-width: 420px;
            margin: 0 auto;
        }

        /* Section */
        .section {
            padding: 28px 32px;
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

        /* Feature list */
        .feature {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #f4f4f5;
        }

        .feature:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            background: #f4f4f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-text h3 {
            font-size: 13px;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 2px;
        }

        .feature-text p {
            font-size: 12px;
            color: #71717a;
            line-height: 1.5;
        }

        /* CTA */
        .cta {
            padding: 32px 32px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            background: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 32px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .btn-secondary {
            display: inline-block;
            background: transparent;
            color: #18181b !important;
            text-decoration: none;
            padding: 11px 28px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #e4e4e7;
            margin-top: 12px;
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

        @media (max-width: 600px) {
            .hero {
                padding: 28px 20px;
            }

            .section {
                padding: 20px;
            }

            .cta {
                padding: 24px 20px;
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
                <p class="tagline">Sheffield Africa Steel Systems</p>
            </div>

            {{-- Hero --}}
            <div class="hero">
                <div class="icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14l-4-4 1.41-1.41L11 13.17l6.59-6.59L19 8l-8 8z"
                            fill="#16a34a" />
                    </svg>
                </div>
                <h1>Welcome, {{ $user->name }}!</h1>
                <p>
                    Your account has been created successfully.<br />
                    You now have access to our full range of steel and construction products.
                </p>
            </div>

            {{-- What you can do --}}
            <div class="section">
                <p class="section-title">What you can do</p>

                <div class="feature">
                    <div class="feature-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="#18181b" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <line x1="3" y1="6" x2="21" y2="6" stroke="#18181b"
                                stroke-width="1.8" stroke-linecap="round" />
                            <path d="M16 10a4 4 0 01-8 0" stroke="#18181b" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h3>Browse & Order</h3>
                        <p>Shop our full catalog of steel systems, roofing, and construction materials with fast
                            delivery across Kenya.</p>
                    </div>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="#18181b"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <polyline points="14 2 14 8 20 8" stroke="#18181b" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <line x1="16" y1="13" x2="8" y2="13" stroke="#18181b"
                                stroke-width="1.8" stroke-linecap="round" />
                            <line x1="16" y1="17" x2="8" y2="17" stroke="#18181b"
                                stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h3>Request a Quote</h3>
                        <p>Need a custom order or bulk pricing? Submit a quote request and our team will get back to you
                            promptly.</p>
                    </div>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="#18181b" stroke-width="1.8" />
                            <polyline points="12 6 12 12 16 14" stroke="#18181b" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h3>Track Your Orders</h3>
                        <p>View order history, download tax invoices, and track delivery status from your account
                            dashboard.</p>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="cta">
                <a href="{{ $shopUrl }}" class="btn">Start Shopping</a>
                <br />
                <a href="{{ $accountUrl }}" class="btn-secondary">Go to My Account</a>
                <p style="margin-top: 16px; font-size: 12px; color: #a1a1aa;">
                    Questions? Contact us at
                    <a href="mailto:info@sheffieldafrica.com" style="color: #71717a;">info@sheffieldafrica.com</a>
                    or call <strong style="color: #3f3f46;">+254 713 777 111</strong>.
                </p>
            </div>

            {{-- Footer --}}
            <div class="footer">
                <p>
                    {{ config('app.name') }} · Sheffield Africa Steel Systems<br />
                    Off Old Mombasa Road, Nairobi, Kenya<br />
                    <br />
                    <span style="color: #d4d4d8;">
                        You're receiving this because you created an account at {{ config('app.name') }}.
                    </span>
                </p>
            </div>

        </div>
    </div>
</body>

</html>
