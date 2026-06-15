<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email template previews</title>
    <style>
        body { margin: 0; background: #f1f5f9; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; color: #0f172a; }
        .wrap { max-width: 640px; margin: 0 auto; padding: 48px 20px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        p.sub { margin: 0 0 28px; color: #64748b; font-size: 14px; }
        .row { display: flex; align-items: center; justify-content: space-between;
               background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 10px; }
        .name { font-weight: 600; font-size: 15px; }
        .actions a { text-decoration: none; font-weight: 600; font-size: 13px; border-radius: 8px; padding: 7px 14px; margin-left: 8px; display: inline-block; }
        .actions a.cur { color: #2f4196; border: 1px solid #c7d0ee; background: #eef1fb; }
        .actions a.pro { color: #fff; background: #c02434; }
        .actions a:hover { opacity: .88; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Email template previews</h1>
        <p class="sub"><strong>Invoice&nbsp;Pro</strong> is the live default · <strong>Classic</strong> = the previous navy/red design, kept for reference. Sample rows are rolled back — nothing is saved.</p>
        @foreach ($links as $link)
            <div class="row">
                <span class="name">{{ $link['label'] }}</span>
                <span class="actions">
                    <a class="pro" href="{{ route('dev.mail-preview.show', $link['key']) }}" target="_blank" rel="noopener">Default (Pro) →</a>
                    <a class="cur" href="{{ route('dev.mail-preview.show', $link['key']) }}?design=classic" target="_blank" rel="noopener">Classic →</a>
                </span>
            </div>
        @endforeach
    </div>
</body>
</html>
