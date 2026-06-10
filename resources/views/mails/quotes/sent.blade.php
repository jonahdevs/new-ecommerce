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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"
        media="screen">
    <style>
        .hover-bg-_c93237:hover {
            background-color: #c93237 !important;
        }

        @media (max-width: 600px) {
            .sm-p-6 {
                padding: 24px !important;
            }

            .sm-px-4 {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .sm-px-6 {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .sm-text-xs {
                font-size: 12px !important;
            }
        }
    </style>
</head>

<body
    style="margin: 0; width: 100%; background-color: #f8fafc; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word;">
    <div style="display: none;">
        Your quotation {{ $quote->quote_number }} is ready for review.
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
    </div>
    <div role="article" aria-roledescription="email" aria-label lang="en">
        <div class="sm-px-4"
            style="background-color: #f8fafc; font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif">
            <table align="center" style="margin: 0 auto;" cellpadding="0" cellspacing="0" role="none">
                <tr>
                    <td style="width: 552px; max-width: 100%;">
                        <div role="separator" style="line-height: 24px">&zwj;</div>
                        <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
                            <tr>
                                <td class="sm-p-6"
                                    style="border-radius: 6px; background-color: #fffffe; padding: 24px 36px; border: 1px solid #c02434">
                                    <div style="display: flex; align-items: center; justify-content: center;">
                                        <a href="{{ config('app.url') }}">
                                            <img src="{{ asset('images/mails/logo.png') }}" width="120"
                                                height="auto" alt="{{ config('app.name') }} logo"
                                                style="max-width: 100%; vertical-align: middle;">
                                        </a>
                                    </div>
                                    <div role="separator"
                                        style="height: 1px; line-height: 1px; margin-top: 24px; margin-bottom: 24px; background-color: #c02434;">
                                        &zwj;</div>
                                    <p style="margin: 0 0 12px; font-size: 16px; line-height: 24px; color: #475569;">Hi
                                        {{ $customerName }},</p>
                                    <p style="margin: 0 0 24px; font-size: 16px; line-height: 24px; color: #475569;">
                                        Thank you for your interest! Your formal quotation <span
                                            style="font-weight: 600; color: #1e293b;">{{ $quote->quote_number }}</span> is
                                        now ready
                                        for your
                                        review and approval.
                                    </p>
                                    {{-- Progress stepper --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 32px; width: 100%;">
                                        <tr>
                                            {{-- REQUESTED --}}
                                            <td style="width: 33.333333%; text-align: center; vertical-align: top;">
                                                <table cellpadding="0" cellspacing="0" role="presentation"
                                                    style="width: 100%;">
                                                    <tr>
                                                        <td style="vertical-align: middle;">
                                                            <table cellpadding="0" cellspacing="0" role="none"
                                                                style="width: 100%; border-collapse: collapse;">
                                                                <tr>
                                                                    <td
                                                                        style="height: 4px; line-height: 4px; font-size: 1px; background-color: transparent;">
                                                                        &zwnj;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td style="width: 40px; vertical-align: middle;">
                                                            <div
                                                                style="margin: 0 auto; height: 40px; width: 40px; border-radius: 9999px; border: 3px solid #16a34a; background-color: #f0fdf4; text-align: center; line-height: 40px; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                                                <span
                                                                    style="font-size: 18px; font-weight: 700; color: #16a34a; line-height: 40px;">&#10003;</span>
                                                            </div>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <table cellpadding="0" cellspacing="0" role="none"
                                                                style="width: 100%; border-collapse: collapse;">
                                                                <tr>
                                                                    <td
                                                                        style="height: 4px; line-height: 4px; font-size: 1px; background-color: #16a34a;">
                                                                        &zwnj;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <p class="sm-text-xs"
                                                    style="margin: 12px 0 0; white-space: nowrap; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #16a34a">
                                                    Requested</p>
                                            </td>
                                            {{-- QUOTED --}}
                                            <td style="width: 33.333333%; text-align: center; vertical-align: top;">
                                                <table cellpadding="0" cellspacing="0" role="presentation"
                                                    style="width: 100%;">
                                                    <tr>
                                                        <td style="vertical-align: middle;">
                                                            <table cellpadding="0" cellspacing="0" role="none"
                                                                style="width: 100%; border-collapse: collapse;">
                                                                <tr>
                                                                    <td
                                                                        style="height: 4px; line-height: 4px; font-size: 1px; background-color: #16a34a;">
                                                                        &zwnj;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td style="width: 40px; vertical-align: middle;">
                                                            <div
                                                                style="margin: 0 auto; height: 40px; width: 40px; border-radius: 9999px; border: 3px solid #16a34a; background-color: #f0fdf4; text-align: center; line-height: 40px; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                                                <span
                                                                    style="font-size: 18px; font-weight: 700; color: #16a34a; line-height: 40px;">&#10003;</span>
                                                            </div>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <table cellpadding="0" cellspacing="0" role="none"
                                                                style="width: 100%; border-collapse: collapse;">
                                                                <tr>
                                                                    <td
                                                                        style="height: 4px; line-height: 4px; font-size: 1px; background-color: #e2e8f0;">
                                                                        &zwnj;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <p class="sm-text-xs"
                                                    style="margin: 12px 0 0; white-space: nowrap; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #16a34a">
                                                    Quoted</p>
                                            </td>
                                            {{-- ACCEPTED --}}
                                            <td style="width: 33.333333%; text-align: center; vertical-align: top;">
                                                <table cellpadding="0" cellspacing="0" role="presentation"
                                                    style="width: 100%;">
                                                    <tr>
                                                        <td style="vertical-align: middle;">
                                                            <table cellpadding="0" cellspacing="0" role="none"
                                                                style="width: 100%; border-collapse: collapse;">
                                                                <tr>
                                                                    <td
                                                                        style="height: 4px; line-height: 4px; font-size: 1px; background-color: #e2e8f0;">
                                                                        &zwnj;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td style="width: 40px; vertical-align: middle;">
                                                            <div
                                                                style="margin: 0 auto; width: 40px; height: 40px; border-radius: 9999px; border: 1px solid #e2e8f0; background-color: #f1f5f9;">
                                                                <table cellpadding="0" cellspacing="0" role="none"
                                                                    style="width: 40px; height: 40px;">
                                                                    <tr>
                                                                        <td
                                                                            style="text-align: center; vertical-align: middle; height: 40px;">
                                                                            <svg viewBox="0 0 24 24" fill="none"
                                                                                stroke="#94a3b8" stroke-width="2.5"
                                                                                stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                style="height: 18px; width: 18px; display: block; margin: 0 auto;">
                                                                                <polyline points="20 6 9 17 4 12">
                                                                                </polyline>
                                                                            </svg>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <table cellpadding="0" cellspacing="0" role="none"
                                                                style="width: 100%; border-collapse: collapse;">
                                                                <tr>
                                                                    <td
                                                                        style="height: 4px; line-height: 4px; font-size: 1px; background-color: transparent;">
                                                                        &zwnj;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <p class="sm-text-xs"
                                                    style="margin: 12px 0 0; white-space: nowrap; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8">
                                                    Accepted</p>
                                            </td>
                                        </tr>
                                    </table>
                                    {{-- Items table --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 32px; width: 100%;">
                                        <thead style="background-color: #d02e3a;">
                                            <tr>
                                                <th
                                                    style="padding: 8px 12px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe;">
                                                    Item</th>
                                                <th
                                                    style="padding: 8px 12px; text-align: center; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe; white-space: nowrap;">
                                                    Qty</th>
                                                <th
                                                    style="padding: 8px 12px; text-align: right; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe; white-space: nowrap;">
                                                    Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($quote->items as $item)
                                                @php
                                                    $subtitle    = $item->product_sku ? 'SKU: ' . $item->product_sku : null;
                                                    $imagePath   = $item->product_snapshot['image_path'] ?? null;
                                                    $imgUrl      = $imagePath ? asset('storage/' . $imagePath) : null;
                                                    $productSlug = $item->product_snapshot['slug'] ?? ($item->product?->slug ?? null);
                                                    $productUrl  = $productSlug ? route('product.show', $productSlug) : null;
                                                @endphp
                                                <tr>
                                                    <td
                                                        style="border-bottom-width: 1px; border-color: #f1f5f9; padding: 12px;">
                                                        <div style="display: flex; align-items: center;">
                                                            @if ($imgUrl)
                                                                @if ($productUrl)
                                                                    <a href="{{ $productUrl }}"
                                                                        style="margin-right: 12px; display: block; flex-shrink: 0;">
                                                                        <img src="{{ $imgUrl }}" width="48"
                                                                            height="48"
                                                                            alt="{{ $item->product_name }}"
                                                                            style="height: 48px; width: 48px; border-radius: 6px; object-fit: cover; display: block;">
                                                                    </a>
                                                                @else
                                                                    <img src="{{ $imgUrl }}" width="48"
                                                                        height="48"
                                                                        alt="{{ $item->product_name }}"
                                                                        style="margin-right: 12px; height: 48px; width: 48px; border-radius: 6px; object-fit: cover; flex-shrink: 0;">
                                                                @endif
                                                            @else
                                                                <div
                                                                    style="margin-right: 12px; height: 48px; width: 48px; border-radius: 6px; background-color: #f1f5f9; flex-shrink: 0;">
                                                                </div>
                                                            @endif
                                                            <div>
                                                                @if ($productUrl)
                                                                    <a href="{{ $productUrl }}"
                                                                        style="text-decoration: none;">
                                                                        <p
                                                                            style="margin: 0; font-size: 14px; font-weight: 600; color: #c02434;">
                                                                            {{ $item->product_name }}</p>
                                                                    </a>
                                                                @else
                                                                    <p
                                                                        style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">
                                                                        {{ $item->product_name }}</p>
                                                                @endif
                                                                @if ($subtitle)
                                                                    <p
                                                                        style="margin: 0; font-size: 12px; color: #64748b;">
                                                                        {{ $subtitle }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td
                                                        style="border-bottom-width: 1px; border-color: #f1f5f9; padding: 12px; text-align: center; font-size: 14px; color: #475569;">
                                                        {{ $item->quantity }}</td>
                                                    <td
                                                        style="border-bottom-width: 1px; border-color: #f1f5f9; padding: 12px; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap;">
                                                        @if ($item->unit_price_cents > 0)
                                                            {{ money($item->unit_price_cents) }}
                                                        @else
                                                            <span
                                                                style="color: #94a3b8; font-weight: 400; font-style: italic;">TBD</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    {{-- Totals --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 8px; width: 100%;">
                                        <tr>
                                            <td style="width: 50%;"></td>
                                            <td style="width: 50%;">
                                                <table cellpadding="0" cellspacing="0" role="presentation"
                                                    style="width: 100%;">
                                                    <tr>
                                                        <td
                                                            style="padding-top: 4px; padding-bottom: 4px; font-size: 14px; color: #475569;">
                                                            Subtotal</td>
                                                        <td
                                                            style="padding-top: 4px; padding-bottom: 4px; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap;">
                                                            {{ money($quote->subtotal_cents) }}</td>
                                                    </tr>
                                                    @if ($quote->discount_cents > 0)
                                                        <tr>
                                                            <td
                                                                style="padding-top: 4px; padding-bottom: 4px; font-size: 14px; color: #475569;">
                                                                Discount</td>
                                                            <td
                                                                style="padding-top: 4px; padding-bottom: 4px; text-align: right; font-size: 14px; font-weight: 600; color: #16a34a; white-space: nowrap;">
                                                                − {{ money($quote->discount_cents) }}</td>
                                                        </tr>
                                                    @endif
                                                    @if ($quote->shipping_cents > 0)
                                                        <tr>
                                                            <td
                                                                style="padding-top: 4px; padding-bottom: 4px; font-size: 14px; color: #475569;">
                                                                Shipping</td>
                                                            <td
                                                                style="padding-top: 4px; padding-bottom: 4px; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap;">
                                                                {{ money($quote->shipping_cents) }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td
                                                            style="border-top: 1px solid #e2e8f0; padding-top: 12px; font-size: 16px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                                                            Total</td>
                                                        <td
                                                            style="border-top: 1px solid #e2e8f0; padding-top: 12px; text-align: right; font-size: 16px; font-weight: 700; color: #c02434; white-space: nowrap;">
                                                            {{ money($quote->total_cents) }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    @if ($quote->expires_at)
                                        <p style="margin: 0 0 32px; font-size: 13px; color: #64748b;">
                                            <span
                                                style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px; color: #94a3b8;">Valid
                                                Until</span>
                                            <span
                                                style="font-weight: 600; color: #1e293b; margin-left: 6px;">{{ $quote->expires_at->format('d M Y') }}</span>
                                        </p>
                                    @endif
                                    <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                                        Please review the pricing and drop us a line if you have any questions or would
                                        like
                                        to request adjustments before accepting!
                                    </p>
                                    <div role="separator" style="line-height: 32px">&zwj;</div>
                                    <div>
                                        <a href="{{ $portalUrl }}"
                                            style="display: inline-block; text-decoration: none; padding: 16px 24px; font-size: 16px; line-height: 1; border-radius: 4px; background-color: #c02434; text-align: center; color: #fffffe;"
                                            class="hover-bg-_c93237">
                                            <!--[if mso]><i style="mso-font-width: 150%; mso-text-raise: 31px;" hidden>&emsp;</i><![endif]-->
                                            <span style="mso-text-raise: 16px">Review & Accept Quote</span>
                                            <!--[if mso]><i hidden style="mso-font-width: 150%;">&emsp;&#8203;</i><![endif]-->
                                        </a>
                                    </div>
                                    <div role="separator" style="line-height: 24px">&zwj;</div>
                                    <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                                        Thank you for choosing {{ config('app.name') }}!
                                        <br>
                                        <br>
                                        Best regards,
                                        <br>
                                        <span style="font-weight: 600; color: #1e293b;">{{ config('app.name') }} Support
                                            Team</span>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
                            <tr>
                                <td class="sm-px-6" style="padding: 24px 36px">
                                    <p style="margin: 0; font-size: 12px; color: #64748b;">
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
