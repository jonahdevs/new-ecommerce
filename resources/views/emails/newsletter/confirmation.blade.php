<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm your subscription</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 15px; color: #3f3f46; }
        .wrap { max-width: 560px; margin: 40px auto; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .header { background: #1e2d4e; padding: 32px 40px; text-align: center; }
        .header img { height: 36px; width: auto; }
        .header-title { color: #f6ecd9; font-size: 13px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; margin-top: 12px; }
        .body { padding: 40px; }
        .body h1 { font-size: 22px; font-weight: 700; color: #18181b; margin-bottom: 12px; }
        .body p { color: #52525b; line-height: 1.6; margin-bottom: 16px; }
        .btn { display: inline-block; background: #e67e22; color: #ffffff !important; font-weight: 600; font-size: 15px; padding: 14px 32px; border-radius: 8px; text-decoration: none; margin: 8px 0 24px; }
        .interests { margin: 0 0 24px; padding: 16px; background: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; }
        .interests p { margin: 0 0 8px; font-size: 13px; font-weight: 600; color: #18181b; }
        .tag { display: inline-block; background: #e4e4e7; color: #3f3f46; font-size: 12px; font-weight: 500; padding: 3px 10px; border-radius: 99px; margin: 2px; }
        .footer { padding: 24px 40px; border-top: 1px solid #f4f4f5; text-align: center; }
        .footer p { font-size: 12px; color: #a1a1aa; line-height: 1.6; }
        .footer a { color: #a1a1aa; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">
                <div class="header-title">The Sheffield Quarterly</div>
            </div>

            <div class="body">
                <h1>One click to confirm.</h1>
                <p>
                    Thanks for signing up. Click the button below to confirm your email address and
                    join <strong>{{ number_format(4800) }}+ trade subscribers</strong> who receive
                    our quarterly catalog drops, project stories, and trade-only offers.
                </p>

                <a href="{{ $subscriber->confirmationUrl() }}" class="btn">
                    Confirm my subscription →
                </a>

                @if (! empty($subscriber->interests))
                    <div class="interests">
                        <p>You'll receive updates on:</p>
                        @php
                            $labels = [
                                'new-products'      => 'New products',
                                'seasonal-catalogs' => 'Catalogs',
                                'trade-pricing'     => 'Trade offers',
                                'projects'          => 'Projects',
                            ];
                        @endphp
                        @foreach ($subscriber->interests as $interest)
                            <span class="tag">{{ $labels[$interest] ?? $interest }}</span>
                        @endforeach
                    </div>
                @endif

                <p style="font-size:13px; color:#71717a;">
                    If you didn't sign up for this newsletter, you can safely ignore this email —
                    your address won't be added to any list.
                </p>
            </div>

            <div class="footer">
                <p>
                    This link expires after first use.<br>
                    <a href="{{ $subscriber->unsubscribeUrl() }}">Unsubscribe</a> &bull;
                    {{ config('app.name') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>
