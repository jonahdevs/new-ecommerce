<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings xmlns:o="urn:schemas-microsoft-com:office:office">
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style>
        td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
    </style>
    <![endif]-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" media="screen">
    <style>
        /* ── Reset ─────────────────────────────────────── */
        * { box-sizing: border-box; }

        /* ── Mobile overrides ───────────────────────────── */
        @media (max-width: 600px) {
            .outer-pad    { padding-left: 12px !important; padding-right: 12px !important; }
            .card         { padding: 20px 16px !important; }
            .logo         { width: 90px !important; }

            /* Stepper */
            .step-icon-on  { height: 28px !important; width: 28px !important; line-height: 28px !important; padding: 5px !important; }
            .step-icon-off { height: 28px !important; width: 28px !important; padding: 5px !important; }
            .step-label    { font-size: 8px !important; letter-spacing: 0 !important; }

            /* Intro */
            .intro         { font-size: 13px !important; line-height: 20px !important; }

            /* Items table */
            .col-item      { width: 55% !important; }
            .col-qty       { width: 15% !important; }
            .col-price     { width: 30% !important; }
            .item-img      { width: 44px !important; height: 44px !important; }
            .item-img-wrap { width: 44px !important; }
            .item-name     { font-size: 12px !important; }
            .item-sku      { font-size: 10px !important; }
            .item-pad      { padding: 8px !important; }
            .item-qty      { font-size: 12px !important; padding: 8px !important; }
            .item-price    { font-size: 12px !important; padding: 8px !important; }

            /* Totals */
            .tot-label     { font-size: 12px !important; }
            .tot-val       { font-size: 12px !important; }
            .tot-row-first { padding-top: 10px !important; padding-bottom: 4px !important; }
            .tot-row       { padding-bottom: 4px !important; }
            .grand-label   { font-size: 13px !important; padding-top: 8px !important; }
            .grand-val     { font-size: 14px !important; padding-top: 8px !important; }

            /* Footer */
            .closing       { font-size: 12px !important; line-height: 20px !important; }
            .footer-pad    { padding: 16px !important; }
            .footer-text   { font-size: 11px !important; }
        }
    </style>
</head>

<body style="margin: 0; width: 100%; background-color: #f1f5f9; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word;">

    {{-- Preview text --}}
    <div style="display: none; max-height: 0; overflow: hidden;">
        Thank you! We've received your order {{ $order->reference }} and are getting it ready for you.
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
    </div>

    <div role="article" aria-roledescription="email" aria-label lang="en">
        <div class="outer-pad" style="background-color: #f1f5f9; font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; padding-left: 16px; padding-right: 16px;">

            <table align="center" style="margin: 0 auto; width: 100%; max-width: 560px;" cellpadding="0" cellspacing="0" role="none">
                <tr>
                    <td style="padding: 24px 0;">

                        {{-- ── Card ────────────────────────────────── --}}
                        <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
                            <tr>
                                <td class="card" style="border-radius: 8px; background-color: #ffffff; padding: 28px 32px; border: 1px solid #c02434;">

                                    {{-- Logo --}}
                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <a href="https://demo.ecommerce.sheffieldafrica.com">
                                            <img class="logo" src="{{ asset('images/mails/logo.png') }}" width="110" height="auto" alt="Sheffield Africa" style="max-width: 100%; vertical-align: middle;">
                                        </a>
                                    </div>

                                    {{-- Divider --}}
                                    <div style="height: 1px; background-color: #c02434; margin-bottom: 20px;"></div>

                                    {{-- Greeting --}}
                                    <p class="intro" style="margin: 0 0 6px; font-size: 13px; line-height: 20px; color: #475569;">Hi {{ $customerName }},</p>
                                    <p class="intro" style="margin: 0 0 4px; font-size: 15px; line-height: 22px; font-weight: 700; color: #1e293b;">
                                        Thank you for your order!
                                    </p>
                                    <p class="intro" style="margin: 0 0 20px; font-size: 13px; line-height: 20px; color: #475569;">
                                        We've received your order <strong style="color: #1e293b;">{{ $order->reference }}</strong> and our team is now reviewing it. We'll send you another update as soon as it's on its way.
                                    </p>

                                    {{-- ── Stepper ──────────────────────────────── --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin-bottom: 20px;">
                                        <tr>
                                            {{-- Received (active) --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="step-icon-on" style="line-height: 32px; margin: 0 auto; height: 32px; width: 32px; border-radius: 50%; background-color: #c02434; padding: 6px; text-align: center; color: #fff;">
                                                    <svg fill="currentColor" viewBox="0 0 100 125" style="width:100%;height:100%;">
                                                        <path d="M68.31,88.13H28.58a8.85,8.85,0,0,1-8.71-7.52L13.78,54.28H12.32a6.06,6.06,0,0,1,0-12.11h7.4a3.12,3.12,0,0,1,.15-.39,8.41,8.41,0,0,1,6.07-5l9-22a4.7,4.7,0,1,1,8.69,3.58l-9,22a8.56,8.56,0,0,1,.92,1.84h25a16.43,16.43,0,0,1,.8-4L53.21,18.36a4.7,4.7,0,1,1,8.69,3.58L68,29.55a16.35,16.35,0,0,1,9.14-2.76A16.6,16.6,0,0,1,82,59.27L77,80.69A8.84,8.84,0,0,1,68.31,88.13Z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><div style="height: 3px; background-color: #e2e8f0;"></div></td>
                                            {{-- Processing (inactive) --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="step-icon-off" style="margin: 0 auto; display: flex; height: 32px; width: 32px; align-items: center; justify-content: center; border-radius: 50%; background-color: #f1f5f9; padding: 6px; border: 1px solid #e2e8f0;">
                                                    <svg fill="currentColor" viewBox="0 0 512 640" style="width:100%;height:100%;color:#94a3b8;enable-background:new 0 0 512 512;">
                                                        <path d="M368.929,123.071l-20-20c-3.905-3.905-3.905-10.237,0-14.143c3.905-3.905,10.237-3.905,14.143,0L376,101.858l32.929-32.929c3.905-3.905,10.237-3.905,14.143,0c3.905,3.905,3.905,10.237,0,14.143l-40,40C379.167,126.976,372.834,126.977,368.929,123.071z"/>
                                                        <path d="M486,456H26c-5.523,0-10-4.478-10-10v-50c0-5.523,4.477-10,10-10h460c5.523,0,10,4.477,10,10v50C496,451.522,491.523,456,486,456z M36,436h440v-30H36V436z"/>
                                                        <path d="M106,496H56c-5.523,0-10-4.478-10-10v-40c0-5.523,4.477-10,10-10h70c3.466,0,6.685,1.795,8.506,4.743c1.822,2.947,1.988,6.629,0.438,9.728l-20,40C113.251,493.86,109.788,496,106,496z M66,476h33.82l10-20H66V476z"/>
                                                        <path d="M456,496h-50c-3.788,0-7.25-2.14-8.944-5.528l-20-40c-1.55-3.1-1.384-6.781,0.438-9.728c1.822-2.948,5.041-4.743,8.506-4.743h70c5.523,0,10,4.477,10,10v40C466,491.522,461.523,496,456,496z M412.18,476H446v-20h-43.82L412.18,476z"/>
                                                        <path d="M256,406H96c-5.523,0-10-4.478-10-10V236c0-5.523,4.477-10,10-10h160c5.523,0,10,4.477,10,10v160C266,401.522,261.523,406,256,406z M106,386h140V246H106V386z"/>
                                                        <path d="M416,406H256c-5.523,0-10-4.478-10-10V236c0-5.523,4.477-10,10-10h160c5.523,0,10,4.477,10,10v160C426,401.522,421.523,406,416,406z M266,386h140V246H266V386z"/>
                                                        <path d="M336,246H176c-5.523,0-10-4.477-10-10V76c0-5.523,4.477-10,10-10h142.91c5.523,0,10,4.477,10,10c0,5.523-4.477,10-10,10H186v140h140v-81.01c0-5.523,4.477-10,10-10c5.523,0,10,4.477,10,10V236C346,241.523,341.523,246,336,246z"/>
                                                        <path d="M386,176c-44.112,0-80-35.888-80-80s35.888-80,80-80s80,35.888,80,80S430.112,176,386,176z M386,36c-33.084,0-60,26.916-60,60c0,33.084,26.916,60,60,60s60-26.916,60-60C446,62.916,419.084,36,386,36z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><div style="height: 3px; background-color: #e2e8f0;"></div></td>
                                            {{-- Shipped (inactive) --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="step-icon-off" style="margin: 0 auto; display: flex; height: 32px; width: 32px; align-items: center; justify-content: center; border-radius: 50%; background-color: #f1f5f9; padding: 6px; border: 1px solid #e2e8f0;">
                                                    <svg fill="currentColor" viewBox="0 0 100 125" style="width:100%;height:100%;color:#94a3b8;">
                                                        <path d="M41.3,78.9c0.1,0.5,0.5,0.8,1.1,0.8H45c0-0.1,0-0.3,0-0.5c0-3.2,2.5-5.7,5.7-5.7c3.2,0,5.7,2.5,5.7,5.7c0,0.2,0,0.3,0,0.5h18c0-0.1,0-0.3,0-0.5c0-3.2,2.5-5.7,5.7-5.7c3.2,0,5.7,2.5,5.7,5.7c0,0.2,0,0.3,0,0.5h3.2c0.9,0,1.7-0.8,1.5-1.7l-0.6-4.2c-0.1-0.3-0.1-0.6-0.3-0.9c-1.1-1.9-2.8-3.4-4.9-4.3l-5.3-2.3c-0.2-0.1-0.5-0.2-0.7-0.4l-7.3-5.2c-0.8-0.6-1.9-0.9-2.9-0.9h-17c-0.1,0-0.1,0-0.1,0h-5.4c-0.6,0-1.1,0.6-0.8,1.1l0.5,1.5c-0.6,0.6-1.1,1.5-1.3,2.3l-2.9,7.6C40.9,74.2,40.9,76.5,41.3,78.9z"/>
                                                        <path d="M92.6,83.6H81.1c1.9-0.5,3.3-2.3,3.3-4.3c0-2.5-2-4.5-4.5-4.5s-4.5,2-4.5,4.5c0,2.1,1.4,3.8,3.3,4.3H52c1.9-0.5,3.3-2.3,3.3-4.3c0-2.5-2-4.5-4.5-4.5s-4.5,2-4.5,4.5c0,2.1,1.4,3.8,3.3,4.3H8.1v3h84.5V83.6z"/>
                                                        <polygon points="66.2,26.4 66.2,13.4 35.4,13.4 35.4,47.8 6.6,47.8 6.6,77.3 9.6,77.3 9.6,50.8 35.4,50.8 35.4,76.1 38.4,76.1 38.4,16.4 63.2,16.4 63.2,56.3 66.2,56.3 66.2,29.4 90.4,29.3 90.4,65.7 93.4,65.7 93.4,26.3"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><div style="height: 3px; background-color: #e2e8f0;"></div></td>
                                            {{-- Delivered (inactive) --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="step-icon-off" style="margin: 0 auto; display: flex; height: 32px; width: 32px; align-items: center; justify-content: center; border-radius: 50%; background-color: #f1f5f9; padding: 6px; border: 1px solid #e2e8f0;">
                                                    <svg fill="currentColor" viewBox="-5.0 -10.0 110.0 135.0" style="width:100%;height:100%;color:#94a3b8;">
                                                        <path d="m74.34 65.082c-2.5039 0.90234-5.0078 1.8047-7.5117 2.707-1.6758 0.60156-3.3477 1.207-5.0195 1.8086 0.039062-0.26953 0.058594-0.53906 0.058594-0.81641 0-3.0586-2.4922-5.5508-5.5547-5.5508h-8.5273c-0.62891 0-1.1484-0.11328-1.7266-0.37891-10.355-4.7461-14.098-4.6602-22.289-4.4727-0.32422 0.007813-0.65625 0.015625-0.99609 0.023438v-2.5859c0-0.60547-0.48828-1.0938-1.0938-1.0938h-10.582c-0.60547 0-1.0938 0.48828-1.0938 1.0938v28.652c0 0.60547 0.48828 1.0938 1.0938 1.0938h10.582c0.60547 0 1.0938-0.48828 1.0938-1.0938v-3.1055c4.6914 0.078125 5.5664 0.52344 10.84 3.1953 0.73828 0.375 1.5625 0.79297 2.4883 1.2539 2.6328 1.4062 5.3711 2.1094 8.2695 2.1094 2.3281 0 4.7578-0.45312 7.3125-1.3594l26.656-10.344c3.0586-1.1094 4.6484-4.5039 3.5469-7.5703-1.1055-3.0664-4.5039-4.6641-7.5742-3.5586z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #c02434;">Received</p>
                                            </td>
                                            <td></td>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8;">Processing</p>
                                            </td>
                                            <td></td>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8;">Shipped</p>
                                            </td>
                                            <td></td>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8;">Delivered</p>
                                            </td>
                                        </tr>
                                    </table>

                                    {{-- ── Items table ──────────────────────────── --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin-bottom: 0; border-radius: 4px; overflow: hidden;">
                                        <thead>
                                            <tr style="background-color: #c02434;">
                                                <th class="col-item item-pad" style="width: 60%; padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fff;">Item</th>
                                                <th class="col-qty" style="width: 12%; padding: 8px 6px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fff; white-space: nowrap;">Qty</th>
                                                <th class="col-price" style="width: 28%; padding: 8px 10px; text-align: right; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fff; white-space: nowrap;">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($order->items as $item)
                                                @php
                                                    $imagePath   = $item->product_snapshot['image_path'] ?? ($item->product?->image_path ?? null);
                                                    $imageUrl    = $imagePath ? asset('storage/' . $imagePath) : null;
                                                    $productName = $item->product_snapshot['name'] ?? ($item->product?->name ?? 'Product');
                                                    $productSku  = $item->product_snapshot['sku']  ?? ($item->product?->sku  ?? '');
                                                    $productSlug = $item->product_snapshot['slug'] ?? ($item->product?->slug ?? null);
                                                    $productUrl  = $productSlug ? route('products.show', $productSlug) : null;
                                                @endphp
                                                <tr>
                                                    <td class="col-item item-pad" style="width: 60%; padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                                        <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%;">
                                                            <tr>
                                                                <td class="item-img-wrap" style="width: 52px; vertical-align: top;">
                                                                    @if ($imageUrl)
                                                                        <img class="item-img" src="{{ $imageUrl }}" alt="{{ $productName }}" width="52" height="52"
                                                                            style="width: 52px; height: 52px; object-fit: cover; border-radius: 4px; display: block; vertical-align: middle;">
                                                                    @else
                                                                        <div class="item-img" style="width: 52px; height: 52px; background-color: #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                                            <span style="color: #94a3b8; font-size: 9px; text-align: center;">No image</span>
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td style="padding-left: 10px; vertical-align: top;">
                                                                    <p class="item-name" style="margin: 0 0 3px; font-size: 12px; font-weight: 600; line-height: 1.4; color: #1e293b;">
                                                                        @if ($productUrl)
                                                                            <a href="{{ $productUrl }}" style="color: #1e293b; text-decoration: none;">{{ $productName }}</a>
                                                                        @else
                                                                            {{ $productName }}
                                                                        @endif
                                                                    </p>
                                                                    @if ($productSku)
                                                                        <p class="item-sku" style="margin: 0; font-size: 11px; color: #94a3b8;">{{ $productSku }}</p>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td class="item-qty" style="width: 12%; padding: 10px 6px; border-bottom: 1px solid #f1f5f9; text-align: center; font-size: 13px; color: #475569; vertical-align: top; white-space: nowrap;">
                                                        {{ $item->quantity }}
                                                    </td>
                                                    <td class="item-price" style="width: 28%; padding: 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-size: 13px; font-weight: 600; color: #1e293b; vertical-align: top; white-space: nowrap;">
                                                        KES {{ number_format($item->unit_price, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    {{-- ── Totals ───────────────────────────────── --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin-top: 4px; margin-bottom: 24px; border-top: 1px solid #e2e8f0;">
                                        <tr>
                                            <td class="tot-label tot-row-first" style="padding-top: 10px; padding-bottom: 5px; font-size: 12px; color: #64748b;">Subtotal</td>
                                            <td class="tot-val tot-row-first" style="padding-top: 10px; padding-bottom: 5px; text-align: right; font-size: 12px; font-weight: 600; color: #1e293b; white-space: nowrap;">KES {{ number_format($order->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tot-label tot-row" style="padding-bottom: 5px; font-size: 12px; color: #64748b;">Delivery Fee</td>
                                            <td class="tot-val tot-row" style="padding-bottom: 5px; text-align: right; font-size: 12px; font-weight: 600; color: #1e293b; white-space: nowrap;">KES {{ number_format($order->shipping, 2) }}</td>
                                        </tr>
                                        @if ($taxEnabled && !$taxInclusive && $order->tax > 0)
                                        <tr>
                                            <td class="tot-label tot-row" style="padding-bottom: 5px; font-size: 12px; color: #64748b;">{{ $taxLabel }}</td>
                                            <td class="tot-val tot-row" style="padding-bottom: 5px; text-align: right; font-size: 12px; font-weight: 600; color: #1e293b; white-space: nowrap;">KES {{ number_format($order->tax, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td class="grand-label" style="border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 13px; font-weight: 700; color: #1e293b;">
                                                Total <span style="font-size: 10px; font-weight: 400; color: #94a3b8; margin-left: 4px;">via {{ $paymentLabel }}</span>
                                            </td>
                                            <td class="grand-val" style="border-top: 1px solid #e2e8f0; padding-top: 10px; text-align: right; font-size: 15px; font-weight: 700; color: #c02434; white-space: nowrap;">KES {{ number_format($order->total, 2) }}</td>
                                        </tr>
                                    </table>

                                    {{-- ── Closing ──────────────────────────────── --}}
                                    <p class="closing" style="margin: 0 0 10px; font-size: 13px; line-height: 20px; color: #64748b;">
                                        You can track your order at any time from your
                                        <a href="{{ $orderUrl }}" style="color: #c02434; text-decoration: none; font-weight: 600;">account</a>.
                                        Questions? Our support team is always happy to help.
                                    </p>
                                    <p class="closing" style="margin: 0; font-size: 13px; line-height: 20px; color: #475569;">
                                        Thank you for choosing Sheffield Africa.<br>
                                        <strong style="color: #1e293b;">Sheffield Africa Team</strong>
                                    </p>

                                </td>
                            </tr>
                        </table>

                        {{-- ── Footer ───────────────────────────────── --}}
                        <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
                            <tr>
                                <td class="footer-pad" style="padding: 16px 8px;">
                                    <p class="footer-text" style="margin: 0; font-size: 11px; color: #94a3b8; text-align: center;">
                                        &copy; {{ date('Y') }} Sheffield Africa. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
