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

    {{-- Preheader (hidden preview text in inbox) --}}
    <div style="display: none;">
        We've received your quote request {{ $quote->quote_number }} — sit tight, our team is on it!
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
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

                                    {{-- Logo --}}
                                    <div style="display: flex; align-items: center; justify-content: center;">
                                        <a href="{{ config('app.url') }}">
                                            <img src="{{ asset('images/mails/logo.png') }}" width="120" height="auto"
                                                alt="{{ config('app.name') }} logo"
                                                style="max-width: 100%; vertical-align: middle;">
                                        </a>
                                    </div>
                                    <div role="separator"
                                        style="height: 1px; line-height: 1px; margin-top: 24px; margin-bottom: 24px; background-color: #c02434;">
                                        &zwj;</div>

                                    {{-- Greeting --}}
                                    <p style="margin: 0 0 8px; font-size: 16px; line-height: 24px; color: #475569;">Hi
                                        {{ $customerName }},</p>
                                    <p
                                        style="margin: 0 0 6px; font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1.3;">
                                        Your quote request is in good hands.
                                    </p>
                                    <p style="margin: 0 0 28px; font-size: 15px; line-height: 24px; color: #475569;">
                                        We've received your quotation request and our team is already on the case. You
                                        can sit back — we'll crunch the numbers and send you a detailed, priced
                                        quotation within <strong style="color: #1e293b;">1 business day</strong>.
                                    </p>

                                    {{-- Reference badge --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 28px; width: 100%; background-color: #fff8f6; border: 1px solid #fde0e2; border-radius: 6px;">
                                        <tr>
                                            <td style="padding: 16px 20px;">
                                                <p
                                                    style="margin: 0 0 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                                    Quote Reference</p>
                                                <p
                                                    style="margin: 0; font-size: 20px; font-weight: 700; color: #c02434; letter-spacing: 0.02em;">
                                                    {{ $quote->quote_number }}</p>
                                            </td>
                                            <td style="padding: 16px 20px; text-align: right; vertical-align: middle;">
                                                @php
                                                    $isPickup = ! $quote->delivery_required;
                                                @endphp
                                                <p
                                                    style="margin: 0 0 2px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                                    Fulfilment</p>
                                                <p style="margin: 0; font-size: 13px; font-weight: 600; color: #1e293b;">
                                                    @if ($isPickup)
                                                        &#127981; In-store pickup
                                                    @else
                                                        &#128230; Delivery requested
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    {{-- Items summary --}}
                                    @php $itemCount = $quote->items->count(); @endphp
                                    <p
                                        style="margin: 0 0 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                        Items in your request ({{ $itemCount }} {{ Str::plural('item', $itemCount) }})
                                    </p>
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 28px; width: 100%; border: 1px solid #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <thead style="background-color: #d02e3a;">
                                            <tr>
                                                <th
                                                    style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe;">
                                                    Product</th>
                                                <th
                                                    style="padding: 8px 12px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe;">
                                                    Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($quote->items as $item)
                                                @php
                                                    $subtitle = $item->product_sku ? 'SKU: ' . $item->product_sku : null;
                                                @endphp
                                                <tr>
                                                    <td
                                                        style="border-bottom: 1px solid #f1f5f9; padding: 10px 12px; font-size: 14px; color: #1e293b;">
                                                        <span style="font-weight: 600;">{{ $item->product_name }}</span>
                                                        @if ($subtitle)
                                                            <br><span
                                                                style="font-size: 12px; color: #64748b;">{{ $subtitle }}</span>
                                                        @endif
                                                    </td>
                                                    <td
                                                        style="border-bottom: 1px solid #f1f5f9; padding: 10px 12px; text-align: center; font-size: 14px; font-weight: 600; color: #475569;">
                                                        {{ $item->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    {{-- What happens next --}}
                                    <p
                                        style="margin: 0 0 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                        What happens next</p>
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 28px; width: 100%;">
                                        <tr>
                                            <td style="vertical-align: top; padding-right: 12px; width: 32px;">
                                                <div
                                                    style="width: 28px; height: 28px; border-radius: 9999px; background-color: #c02434; text-align: center; line-height: 28px; color: #fffffe; font-weight: 700; font-size: 13px;">
                                                    1</div>
                                            </td>
                                            <td style="vertical-align: top; padding-bottom: 16px;">
                                                <p style="margin: 0 0 2px; font-size: 14px; font-weight: 600; color: #1e293b; line-height: 28px;">
                                                    Our team reviews your request</p>
                                                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 20px;">
                                                    We check availability, source the best pricing, and
                                                    @if ($isPickup)
                                                        prepare your items for collection.
                                                    @else
                                                        calculate accurate delivery costs to your location.
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; padding-right: 12px; width: 32px;">
                                                <div
                                                    style="width: 28px; height: 28px; border-radius: 9999px; background-color: #c02434; text-align: center; line-height: 28px; color: #fffffe; font-weight: 700; font-size: 13px;">
                                                    2</div>
                                            </td>
                                            <td style="vertical-align: top; padding-bottom: 16px;">
                                                <p style="margin: 0 0 2px; font-size: 14px; font-weight: 600; color: #1e293b; line-height: 28px;">
                                                    You receive your priced quotation</p>
                                                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 20px;">
                                                    Within 1 business day you'll get a formal quote
                                                    @if ($isPickup)
                                                        with item pricing — no shipping charges apply since you'll be collecting from us.
                                                    @else
                                                        including item pricing and delivery costs to your selected location.
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; padding-right: 12px; width: 32px;">
                                                <div
                                                    style="width: 28px; height: 28px; border-radius: 9999px; background-color: #c02434; text-align: center; line-height: 28px; color: #fffffe; font-weight: 700; font-size: 13px;">
                                                    3</div>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <p style="margin: 0 0 2px; font-size: 14px; font-weight: 600; color: #1e293b; line-height: 28px;">
                                                    You accept or request changes</p>
                                                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 20px;">
                                                    Happy with the quote? Accept it online and we'll get your order
                                                    moving. Need adjustments? Just let us know.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    @if ($quote->notes)
                                        {{-- Customer notes echo-back --}}
                                        <table cellpadding="0" cellspacing="0" role="presentation"
                                            style="margin-bottom: 28px; width: 100%; background-color: #f8fafc; border-left: 3px solid #c02434; border-radius: 0 4px 4px 0;">
                                            <tr>
                                                <td style="padding: 12px 16px;">
                                                    <p
                                                        style="margin: 0 0 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">
                                                        Your notes</p>
                                                    <p style="margin: 0; font-size: 13px; color: #475569; line-height: 20px; font-style: italic;">
                                                        "{{ $quote->notes }}"</p>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    {{-- CTA --}}
                                    <p style="margin: 0 0 20px; font-size: 15px; line-height: 24px; color: #475569;">
                                        You can track your quotation status at any time from your account portal.
                                    </p>
                                    <div>
                                        <a href="{{ $quotationsUrl }}"
                                            style="display: inline-block; text-decoration: none; padding: 14px 24px; font-size: 15px; line-height: 1; border-radius: 4px; background-color: #c02434; text-align: center; color: #fffffe;"
                                            class="hover-bg-_c93237">
                                            <!--[if mso]><i style="mso-font-width: 150%; mso-text-raise: 31px;" hidden>&emsp;</i><![endif]-->
                                            <span style="mso-text-raise: 16px">View My Quotations</span>
                                            <!--[if mso]><i hidden style="mso-font-width: 150%;">&emsp;&#8203;</i><![endif]-->
                                        </a>
                                    </div>
                                    <div role="separator" style="line-height: 24px">&zwj;</div>
                                    <p style="margin: 0; font-size: 15px; line-height: 24px; color: #475569;">
                                        Thank you for choosing {{ config('app.name') }}. We look forward to serving you!
                                        <br><br>
                                        Warm regards,<br>
                                        <span style="font-weight: 600; color: #1e293b;">The {{ config('app.name') }} Team</span>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
                            <tr>
                                <td class="sm-px-6" style="padding: 20px 36px">
                                    <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                                        You're receiving this because you submitted a quote request with us.
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
