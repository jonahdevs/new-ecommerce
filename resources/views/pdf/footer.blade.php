<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 9px;
            color: #52525b;
        }

        .footer {
            width: 100%;
            display: flex;
            align-items: flex-start;
            border-top: 1px solid #d4d4d8;
            padding: 6px 32px 0;
            gap: 24px;
        }

        .col { flex: 1; }

        .col-heading {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #71717a;
            margin-bottom: 3px;
        }

        .col-body { line-height: 1.6; }

        .hq-badge {
            display: inline-block;
            background: #18181b;
            color: #fff;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1px 4px;
            border-radius: 2px;
            vertical-align: middle;
            margin-left: 3px;
        }
    </style>
</head>
<body>
    <div class="footer">

        @foreach ($showrooms as $showroom)
            <div class="col">
                <div class="col-heading">
                    {{ $showroom->city }}
                    @if ($showroom->is_hq) <span class="hq-badge">HQ</span> @endif
                </div>
                <div class="col-body">
                    {{ $showroom->address }}<br>
                    @if ($showroom->pobox) P.O. Box {{ $showroom->pobox }}<br> @endif
                    @if (!empty($showroom->phones)) Tel: {{ collect($showroom->phones)->first() }}<br> @endif
                    @if ($showroom->email) {{ $showroom->email }} @endif
                </div>
            </div>
        @endforeach

        @if ($banking)
            <div class="col">
                <div class="col-heading">Banking Details</div>
                <div class="col-body" style="white-space: pre-line;">{{ $banking }}</div>
            </div>
        @else
            <div class="col">
                <div class="col-heading">Our Website</div>
                <div class="col-body" style="font-weight: 600; color: #18181b;">{{ $appUrl }}</div>
            </div>
        @endif

    </div>
</body>
</html>
