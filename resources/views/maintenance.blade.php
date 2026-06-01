@php
    $store = app(\App\Settings\BrandingSettings::class)->store_name ?: config('app.name', 'Sheffield');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex,nofollow" />
    <title>{{ $store }} — Down for maintenance</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f1d33;
            color: #e6ddc8;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            padding: 2rem;
        }

        .card {
            max-width: 30rem;
            text-align: center;
        }

        .store {
            font-size: 0.78rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #d8c79d;
        }

        h1 {
            margin: 0.75rem 0 1rem;
            font-size: 1.9rem;
            color: #f6ecd9;
        }

        p {
            margin: 0;
            line-height: 1.65;
            color: #c9bea4;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="store">{{ $store }}</div>
        <h1>We’ll be right back</h1>
        <p>{{ $message }}</p>
    </div>
</body>

</html>
