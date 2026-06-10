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
        * { box-sizing: border-box; }

        @media (max-width: 600px) {
            .outer-pad    { padding-left: 12px !important; padding-right: 12px !important; }
            .card         { padding: 20px 16px !important; }
            .logo         { width: 90px !important; }
            .step-icon-on  { height: 28px !important; width: 28px !important; line-height: 28px !important; padding: 5px !important; }
            .step-icon-off { height: 28px !important; width: 28px !important; padding: 5px !important; }
            .step-label    { font-size: 8px !important; letter-spacing: 0 !important; }
            .intro         { font-size: 13px !important; line-height: 20px !important; }
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
            .closing       { font-size: 12px !important; line-height: 20px !important; }
            .footer-pad    { padding: 16px !important; }
            .footer-text   { font-size: 11px !important; }
        }
    </style>
</head>

<body style="margin: 0; width: 100%; background-color: #f1f5f9; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word;">

    {{-- Preview text --}}
    <div style="display: none; max-height: 0; overflow: hidden;">
        Your order {{ $order->order_number }} has been updated to {{ $newStatus->label() }}.
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
                                        <a href="{{ config('app.url') }}">
                                            <img class="logo" src="{{ asset('images/mails/logo.png') }}" width="110" height="auto" alt="{{ config('app.name') }}" style="max-width: 100%; vertical-align: middle;">
                                        </a>
                                    </div>

                                    {{-- Divider --}}
                                    <div style="height: 1px; background-color: #c02434; margin-bottom: 20px;"></div>

                                    {{-- Greeting --}}
                                    <p class="intro" style="margin: 0 0 6px; font-size: 13px; line-height: 20px; color: #475569;">Hi {{ $customerName }},</p>
                                    <p class="intro" style="margin: 0 0 20px; font-size: 13px; line-height: 20px; color: #475569;">
                                        Your order <strong style="color: #1e293b;">{{ $order->order_number }}</strong> has been updated to
                                        <strong style="color: #c02434; text-transform: uppercase; letter-spacing: 0.025em;">{{ $newStatus->label() }}</strong>.
                                    </p>

                                    {{-- ── Stepper ──────────────────────────────── --}}
                                    @php
                                        $processingActive   = in_array($newStatus->value, ['processing', 'out_for_delivery', 'completed']);
                                        $outForDeliveryActive = in_array($newStatus->value, ['out_for_delivery', 'completed']);
                                        $completedActive    = $newStatus->value === 'completed';
                                    @endphp
                                    <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin-bottom: 20px;">
                                        <tr>
                                            {{-- Placed (always active) --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="step-icon-on" style="line-height: 32px; margin: 0 auto; height: 32px; width: 32px; border-radius: 50%; background-color: #c02434; padding: 6px; text-align: center; color: #fff;">
                                                    <svg fill="currentColor" viewBox="0 0 100 125" style="width:100%;height:100%;">
                                                        <path d="M68.31,88.13H28.58a8.85,8.85,0,0,1-8.71-7.52L13.78,54.28H12.32a6.06,6.06,0,0,1,0-12.11h7.4a3.12,3.12,0,0,1,.15-.39,8.41,8.41,0,0,1,6.07-5l9-22a4.7,4.7,0,1,1,8.69,3.58l-9,22a8.56,8.56,0,0,1,.92,1.84h25a16.43,16.43,0,0,1,.8-4L53.21,18.36a4.7,4.7,0,1,1,8.69,3.58L68,29.55a16.35,16.35,0,0,1,9.14-2.76A16.6,16.6,0,0,1,82,59.27L77,80.69A8.84,8.84,0,0,1,68.31,88.13Z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><div style="height: 3px; background-color: #c02434;"></div></td>
                                            {{-- Processing --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="{{ $processingActive ? 'step-icon-on' : 'step-icon-off' }}" style="margin: 0 auto; {{ $processingActive ? 'line-height: 32px;' : 'display: flex; align-items: center; justify-content: center;' }} height: 32px; width: 32px; border-radius: 50%; background-color: {{ $processingActive ? '#c02434' : '#f1f5f9' }}; padding: 6px; {{ $processingActive ? '' : 'border: 1px solid #e2e8f0;' }} text-align: center; color: {{ $processingActive ? '#fff' : '#94a3b8' }};">
                                                    <svg fill="currentColor" viewBox="0 0 100 125" style="width:100%;height:100%;">
                                                        <path d="M50,61.9c-6.6,0-11.9-5.3-11.9-11.9S43.4,38.1,50,38.1S61.9,43.4,61.9,50S56.6,61.9,50,61.9z M50,42.1 c-4.4,0-7.9,3.5-7.9,7.9s3.5,7.9,7.9,7.9s7.9-3.5,7.9-7.9S54.4,42.1,50,42.1z"/>
                                                        <path d="M56.5,87.5h-13l-1.7-7.8c-2.1-0.7-4.1-1.6-5.9-2.8l-7.3,3.3l-9.2-9.2l3.3-7.3c-1.2-1.8-2.1-3.8-2.8-5.9l-7.8-1.7v-13 l7.8-1.7c0.7-2.1,1.6-4.1,2.8-5.9l-3.3-7.3l9.2-9.2l7.3,3.3c1.8-1.2,3.8-2.1,5.9-2.8l1.7-7.8h13l1.7,7.8 c2.1,0.7,4.1,1.6,5.9,2.8l7.3-3.3l9.2,9.2l-3.3,7.3c1.2,1.8,2.1,3.8,2.8,5.9l7.8,1.7v13l-7.8,1.7c-0.7,2.1-1.6,4.1-2.8,5.9 l3.3,7.3l-9.2,9.2l-7.3-3.3c-1.8,1.2-3.8,2.1-5.9,2.8L56.5,87.5z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><div style="height: 3px; background-color: {{ $outForDeliveryActive ? '#c02434' : '#e2e8f0' }};"></div></td>
                                            {{-- Out for Delivery --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="{{ $outForDeliveryActive ? 'step-icon-on' : 'step-icon-off' }}" style="margin: 0 auto; {{ $outForDeliveryActive ? 'line-height: 32px;' : 'display: flex; align-items: center; justify-content: center;' }} height: 32px; width: 32px; border-radius: 50%; background-color: {{ $outForDeliveryActive ? '#c02434' : '#f1f5f9' }}; padding: 6px; {{ $outForDeliveryActive ? '' : 'border: 1px solid #e2e8f0;' }} text-align: center; color: {{ $outForDeliveryActive ? '#fff' : '#94a3b8' }};">
                                                    <svg fill="currentColor" viewBox="0 0 100 125" style="width:100%;height:100%;">
                                                        <path d="M41.3,78.9c0.1,0.5,0.5,0.8,1.1,0.8H45c0-0.1,0-0.3,0-0.5c0-3.2,2.5-5.7,5.7-5.7c3.2,0,5.7,2.5,5.7,5.7c0,0.2,0,0.3,0,0.5h18c0-0.1,0-0.3,0-0.5c0-3.2,2.5-5.7,5.7-5.7c3.2,0,5.7,2.5,5.7,5.7c0,0.2,0,0.3,0,0.5h3.2c0.9,0,1.7-0.8,1.5-1.7l-0.6-4.2c-0.1-0.3-0.1-0.6-0.3-0.9c-1.1-1.9-2.8-3.4-4.9-4.3l-5.3-2.3c-0.2-0.1-0.5-0.2-0.7-0.4l-7.3-5.2c-0.8-0.6-1.9-0.9-2.9-0.9h-17c-0.1,0-0.1,0-0.1,0h-5.4c-0.6,0-1.1,0.6-0.8,1.1l0.5,1.5c-0.6,0.6-1.1,1.5-1.3,2.3l-2.9,7.6C40.9,74.2,40.9,76.5,41.3,78.9z"/>
                                                        <path d="M92.6,83.6H81.1c1.9-0.5,3.3-2.3,3.3-4.3c0-2.5-2-4.5-4.5-4.5s-4.5,2-4.5,4.5c0,2.1,1.4,3.8,3.3,4.3H52c1.9-0.5,3.3-2.3,3.3-4.3c0-2.5-2-4.5-4.5-4.5s-4.5,2-4.5,4.5c0,2.1,1.4,3.8,3.3,4.3H8.1v3h84.5V83.6z"/>
                                                        <polygon points="66.2,26.4 66.2,13.4 35.4,13.4 35.4,47.8 6.6,47.8 6.6,77.3 9.6,77.3 9.6,50.8 35.4,50.8 35.4,76.1 38.4,76.1 38.4,16.4 63.2,16.4 63.2,56.3 66.2,56.3 66.2,29.4 90.4,29.3 90.4,65.7 93.4,65.7 93.4,26.3"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><div style="height: 3px; background-color: {{ $completedActive ? '#c02434' : '#e2e8f0' }};"></div></td>
                                            {{-- Completed --}}
                                            <td style="width: 32px; text-align: center;">
                                                <div class="{{ $completedActive ? 'step-icon-on' : 'step-icon-off' }}" style="margin: 0 auto; {{ $completedActive ? 'line-height: 32px;' : 'display: flex; align-items: center; justify-content: center;' }} height: 32px; width: 32px; border-radius: 50%; background-color: {{ $completedActive ? '#c02434' : '#f1f5f9' }}; padding: 6px; {{ $completedActive ? '' : 'border: 1px solid #e2e8f0;' }} text-align: center; color: {{ $completedActive ? '#fff' : '#94a3b8' }};">
                                                    <svg fill="currentColor" viewBox="-5.0 -10.0 110.0 135.0" style="width:100%;height:100%;">
                                                        <path d="m74.34 65.082c-2.5039 0.90234-5.0078 1.8047-7.5117 2.707-1.6758 0.60156-3.3477 1.207-5.0195 1.8086 0.039062-0.26953 0.058594-0.53906 0.058594-0.81641 0-3.0586-2.4922-5.5508-5.5547-5.5508h-8.5273c-0.62891 0-1.1484-0.11328-1.7266-0.37891-10.355-4.7461-14.098-4.6602-22.289-4.4727-0.32422 0.007813-0.65625 0.015625-0.99609 0.023438v-2.5859c0-0.60547-0.48828-1.0938-1.0938-1.0938h-10.582c-0.60547 0-1.0938 0.48828-1.0938 1.0938v28.652c0 0.60547 0.48828 1.0938 1.0938 1.0938h10.582c0.60547 0 1.0938-0.48828 1.0938-1.0938v-3.1055c4.6914 0.078125 5.5664 0.52344 10.84 3.1953 0.73828 0.375 1.5625 0.79297 2.4883 1.2539 2.6328 1.4062 5.3711 2.1094 8.2695 2.1094 2.3281 0 4.7578-0.45312 7.3125-1.3594l26.656-10.344c3.0586-1.1094 4.6484-4.5039 3.5469-7.5703-1.1055-3.0664-4.5039-4.6641-7.5742-3.5586z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #c02434;">Placed</p>
                                            </td>
                                            <td></td>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: {{ $processingActive ? '700' : '600' }}; text-transform: uppercase; letter-spacing: 0.04em; color: {{ $processingActive ? '#c02434' : '#94a3b8' }};">Processing</p>
                                            </td>
                                            <td></td>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: {{ $outForDeliveryActive ? '700' : '600' }}; text-transform: uppercase; letter-spacing: 0.04em; color: {{ $outForDeliveryActive ? '#c02434' : '#94a3b8' }};">Out for Delivery</p>
                                            </td>
                                            <td></td>
                                            <td style="padding-top: 6px; text-align: center;">
                                                <p class="step-label" style="margin: 0; white-space: nowrap; font-size: 9px; font-weight: {{ $completedActive ? '700' : '600' }}; text-transform: uppercase; letter-spacing: 0.04em; color: {{ $completedActive ? '#c02434' : '#94a3b8' }};">Completed</p>
                                            </td>
                                        </tr>
                                    </table>

                                    {{-- ── Items table ──────────────────────────── --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin-bottom: 24px; border-radius: 4px; overflow: hidden;">
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
                                                    $productName = $item->product_name ?? 'Product';
                                                    $productSku  = $item->product_sku ?? '';
                                                    $productSlug = $item->product_snapshot['slug'] ?? ($item->product?->slug ?? null);
                                                    $productUrl  = $productSlug ? route('product.show', $productSlug) : null;
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
                                                        {{ money($item->unit_price_cents) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    {{-- ── Closing ──────────────────────────────── --}}
                                    <p class="closing" style="margin: 0 0 10px; font-size: 13px; line-height: 20px; color: #64748b;">
                                        Questions about your order? Our support team is happy to help.
                                    </p>
                                    <p class="closing" style="margin: 0; font-size: 13px; line-height: 20px; color: #475569;">
                                        Thank you for your order.<br>
                                        <strong style="color: #1e293b;">{{ config('app.name') }} Support Team</strong>
                                    </p>

                                </td>
                            </tr>
                        </table>

                        {{-- ── Footer ───────────────────────────────── --}}
                        <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
                            <tr>
                                <td class="footer-pad" style="padding: 16px 8px;">
                                    <p class="footer-text" style="margin: 0; font-size: 11px; color: #94a3b8; text-align: center;">
                                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
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
