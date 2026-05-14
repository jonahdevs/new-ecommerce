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
        }
    </style>
</head>

<body
    style="margin: 0; width: 100%; background-color: #f8fafc; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word;">
    <div style="display: none;">
        Your refund has been processed for order #{{ $order->reference }}
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
                                            <img src="{{ asset('images/mails/logo.png') }}" width="120"
                                                height="auto" alt="Sheffield logo"
                                                style="max-width: 100%; vertical-align: middle;">
                                        </a>
                                    </div>

                                    <div role="separator"
                                        style="height: 1px; line-height: 1px; margin-top: 24px; margin-bottom: 24px; background-color: #c02434;">
                                        &zwj;</div>

                                    {{-- Greeting --}}
                                    <p style="margin: 0 0 12px; font-size: 16px; line-height: 24px; color: #475569;">
                                        Hi {{ $customerName }},
                                    </p>

                                    {{-- Main message --}}
                                    <p style="margin: 0 0 24px; font-size: 16px; line-height: 24px; color: #475569;">
                                        We've processed a refund for your order <span
                                            style="font-weight: 600; color: #1e293b;">#{{ $order->reference }}</span>.
                                        The refund amount will be credited back to your original payment method within
                                        5-10 business days.
                                    </p>

                                    {{-- Refund details card --}}
                                    <div
                                        style="background-color: #f8fafc; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                        <p
                                            style="margin: 0 0 16px; font-size: 14px; font-weight: 600; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em;">
                                            Refund Details
                                        </p>

                                        <table style="width: 100%;" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #64748b;">Order
                                                    Reference</td>
                                                <td
                                                    style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: right; font-weight: 500;">
                                                    #{{ $order->reference }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #64748b;">Original
                                                    Amount</td>
                                                <td
                                                    style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: right;">
                                                    {{ format_currency($order->total) }}</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="padding: 8px 0; font-size: 14px; color: #64748b; border-top: 1px solid #e2e8f0;">
                                                    Refund Amount</td>
                                                <td
                                                    style="padding: 8px 0; font-size: 18px; color: #059669; text-align: right; font-weight: 600; border-top: 1px solid #e2e8f0;">
                                                    {{ format_currency($refundAmount) }}</td>
                                            </tr>
                                        </table>
                                    </div>

                                    {{-- Reason --}}
                                    @if ($refundReason)
                                        <div
                                            style="background-color: #fef3c7; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                                            <p
                                                style="margin: 0 0 4px; font-size: 12px; font-weight: 600; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em;">
                                                Reason
                                            </p>
                                            <p style="margin: 0; font-size: 14px; color: #78350f;">
                                                {{ $refundReason }}
                                            </p>
                                        </div>
                                    @endif

                                    {{-- CTA Button --}}
                                    <div style="text-align: center; margin-bottom: 24px;">
                                        <a href="{{ $orderUrl }}"
                                            style="display: inline-block; background-color: #c02434; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 12px 32px; border-radius: 6px;">
                                            View Order Details
                                        </a>
                                    </div>

                                    {{-- Help text --}}
                                    <p style="margin: 0; font-size: 14px; line-height: 22px; color: #64748b;">
                                        If you have any questions about this refund, please don't hesitate to contact
                                        our support team.
                                    </p>

                                    <div role="separator"
                                        style="height: 1px; line-height: 1px; margin-top: 24px; margin-bottom: 24px; background-color: #e2e8f0;">
                                        &zwj;</div>

                                    {{-- Footer --}}
                                    <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center;">
                                        © {{ date('Y') }} Sheffield Steel Systems. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <div role="separator" style="line-height: 24px">&zwj;</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
