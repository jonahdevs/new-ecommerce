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
                padding-right: 16px !important;
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
        Your KRA tax invoice for order {{ $order->order_number }} is ready.
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

                                    {{-- KRA badge --}}
                                    <div style="text-align: center; margin-bottom: 24px;">
                                        <span
                                            style="display: inline-block; background-color: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 4px 12px; border-radius: 9999px; border: 1px solid #bbf7d0;">
                                            KRA Validated Tax Invoice
                                        </span>
                                    </div>

                                    <p style="margin: 0 0 12px; font-size: 16px; line-height: 24px; color: #475569;">Hi
                                        {{ $customerName }},</p>
                                    <p style="margin: 0 0 24px; font-size: 16px; line-height: 24px; color: #475569;">
                                        Your KRA-validated tax invoice for order <span
                                            style="font-weight: 600; color: #1e293b;">{{ $order->order_number }}</span> is
                                        now ready and attached to this email as a PDF.
                                    </p>

                                    {{-- KRA details block --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 32px; width: 100%; background-color: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                                        <tr>
                                            <td style="padding: 16px 20px;">
                                                <p
                                                    style="margin: 0 0 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">
                                                    Invoice Details</p>
                                                <table cellpadding="0" cellspacing="0" role="presentation"
                                                    style="width: 100%;">
                                                    <tr>
                                                        <td
                                                            style="padding-bottom: 8px; font-size: 13px; color: #64748b; width: 50%;">
                                                            Order Reference</td>
                                                        <td
                                                            style="padding-bottom: 8px; font-size: 13px; font-weight: 600; color: #1e293b; text-align: right;">
                                                            {{ $order->order_number }}</td>
                                                    </tr>
                                                    @if ($order->kra_cu_number)
                                                        <tr>
                                                            <td
                                                                style="padding-bottom: 8px; font-size: 13px; color: #64748b;">
                                                                KRA CU Number</td>
                                                            <td
                                                                style="padding-bottom: 8px; font-size: 13px; font-weight: 600; color: #1e293b; text-align: right;">
                                                                {{ $order->kra_cu_number }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td
                                                            style="padding-bottom: 8px; font-size: 13px; color: #64748b;">
                                                            Order Total</td>
                                                        <td
                                                            style="padding-bottom: 8px; font-size: 13px; font-weight: 600; color: #c02434; text-align: right;">
                                                            {{ money($order->total_cents) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="font-size: 13px; color: #64748b;">Date</td>
                                                        <td
                                                            style="font-size: 13px; font-weight: 600; color: #1e293b; text-align: right;">
                                                            {{ $order->created_at->format('d M Y') }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    {{-- CTA --}}
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                        style="margin-bottom: 32px;">
                                        <tr>
                                            <td style="border-radius: 4px; background-color: #c02434;">
                                                <a href="{{ $orderUrl }}"
                                                    style="display: block; font-size: 14px; font-weight: 600; line-height: 1; padding: 14px 24px; color: #fffffe; text-decoration: none;">View
                                                    Order</a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                                        Please keep this invoice for your records. If you have any questions, contact
                                        our support team.
                                    </p>
                                    <div role="separator" style="line-height: 24px">&zwj;</div>
                                    <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                                        Thank you for choosing {{ config('app.name') }}!
                                        <br><br>
                                        Best regards,<br>
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
