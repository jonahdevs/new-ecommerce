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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" media="screen">
  <style>
    .hover-bg-_a0272b:hover {
      background-color: #a0272b !important;
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
<body style="margin: 0; width: 100%; background-color: #f8fafc; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word;">
  <div style="display: none;">
    Your quotation {{ $quote->quote_number }} is expiring soon — act now.
    &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
  </div>
  <div role="article" aria-roledescription="email" aria-label lang="en">
    <div class="sm-px-4" style="background-color: #f8fafc; font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif">
      <table align="center" style="margin: 0 auto;" cellpadding="0" cellspacing="0" role="none">
        <tr>
          <td style="width: 552px; max-width: 100%;">
            <div role="separator" style="line-height: 24px">&zwj;</div>
            <table style="width: 100%;" cellpadding="0" cellspacing="0" role="none">
              <tr>
                <td class="sm-p-6" style="border-radius: 6px; background-color: #fffffe; padding: 24px 36px; border: 1px solid #c02434">
                  <div style="display: flex; align-items: center; justify-content: center;">
                    <a href="{{ config('app.url') }}">
                      <img src="{{ asset('images/mails/logo.png') }}" width="120" height="auto" alt="{{ config('app.name') }} logo" style="max-width: 100%; vertical-align: middle;">
                    </a>
                  </div>
                  <div role="separator" style="height: 1px; line-height: 1px; margin-top: 24px; margin-bottom: 24px; background-color: #c02434;">&zwj;</div>
                  <p style="margin: 0 0 12px; font-size: 16px; line-height: 24px; color: #475569;">Hi {{ $customerName }},</p>
                  <p style="margin: 0 0 24px; font-size: 16px; line-height: 24px; color: #475569;">
                    Just a quick reminder that your quotation <span style="font-weight: 600; color: #1e293b;">{{ $quote->quote_number }}</span>
                    is expiring in <span style="font-weight: 700; color: #c93237;">{{ $daysLeft }} days</span>.
                  </p>
                  {{-- Stepper: Requested → Quoted (active) → Accepted --}}
                  <table cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 32px; width: 100%;">
                    <tr>
                      <td style="width: 33.333333%; text-align: center; vertical-align: top;">
                        <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%;">
                          <tr>
                            <td style="width: 50%; vertical-align: middle;">
                              <div style="height: 4px; width: 100%; background-color: transparent;"></div>
                            </td>
                            <td style="width: 40px; vertical-align: middle;">
                              <div style="margin-left: auto; margin-right: auto; display: flex; height: 40px; width: 40px; align-items: center; justify-content: center; border-radius: 9999px; border-width: 3px; border-color: #16a34a; background-color: #f0fdf4; padding: 8px; text-align: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                                <span style="font-size: 18px; font-weight: 700; color: #16a34a;">&#10003;</span>
                              </div>
                            </td>
                            <td style="width: 50%; vertical-align: middle;">
                              <div style="height: 4px; width: 100%; background-color: #16a34a;"></div>
                            </td>
                          </tr>
                        </table>
                        <p class="sm-text-xs" style="margin: 12px 0 0; white-space: nowrap; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #16a34a">
                          Requested
                        </p>
                      </td>
                      <td style="width: 33.333333%; text-align: center; vertical-align: top;">
                        <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%;">
                          <tr>
                            <td style="width: 50%; vertical-align: middle;">
                              <div style="height: 4px; width: 100%; background-color: #16a34a;"></div>
                            </td>
                            <td style="width: 40px; vertical-align: middle;">
                              <div style="margin-left: auto; margin-right: auto; display: flex; height: 40px; width: 40px; align-items: center; justify-content: center; border-radius: 9999px; border-width: 3px; border-color: #16a34a; background-color: #f0fdf4; padding: 8px; text-align: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                                <span style="font-size: 18px; font-weight: 700; color: #16a34a;">&#10003;</span>
                              </div>
                            </td>
                            <td style="width: 50%; vertical-align: middle;">
                              <div style="height: 4px; width: 100%; background-color: #e2e8f0;"></div>
                            </td>
                          </tr>
                        </table>
                        <p class="sm-text-xs" style="margin: 12px 0 0; white-space: nowrap; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #16a34a">
                          Quoted
                        </p>
                      </td>
                      <td style="width: 33.333333%; text-align: center; vertical-align: top;">
                        <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%;">
                          <tr>
                            <td style="width: 50%; vertical-align: middle;">
                              <div style="height: 4px; width: 100%; background-color: #e2e8f0;"></div>
                            </td>
                            <td style="width: 40px; vertical-align: middle;">
                              <div style="margin-left: auto; margin-right: auto; display: flex; height: 40px; width: 40px; align-items: center; justify-content: center; border-radius: 9999px; background-color: #f1f5f9; padding: 8px; text-align: center; border: 1px solid #e2e8f0;">
                                <svg version="1.1" x="0px" y="0px" viewBox="0 0 100 125" fill="currentColor" style="height: 16px; width: 16px;">
                                  <path d="M41.3,78.9c0.1,0.5,0.5,0.8,1.1,0.8H45c0-0.1,0-0.3,0-0.5c0-3.2,2.5-5.7,5.7-5.7c3.2,0,5.7,2.5,5.7,5.7c0,0.2,0,0.3,0,0.5  h18c0-0.1,0-0.3,0-0.5c0-3.2,2.5-5.7,5.7-5.7c3.2,0,5.7,2.5,5.7,5.7c0,0.2,0,0.3,0,0.5h3.2c0.9,0,1.7-0.8,1.5-1.7l-0.6-4.2  c-0.1-0.3-0.1-0.6-0.3-0.9c-1.1-1.9-2.8-3.4-4.9-4.3l-5.3-2.3c-0.2-0.1-0.5-0.2-0.7-0.4l-7.3-5.2c-0.8-0.6-1.9-0.9-2.9-0.9h-17  c-0.1,0-0.1,0-0.1,0h-5.4c-0.6,0-1.1,0.6-0.8,1.1l0.5,1.5c-0.6,0.6-1.1,1.5-1.3,2.3l-2.9,7.6C40.9,74.2,40.9,76.5,41.3,78.9z   M68.1,61.7c0.2,0,0.5,0.1,0.7,0.2l6.3,4.6c0.9,0.7,0.4,2.2-0.7,2.2l-11.5-0.4l-1.2-6.6H68.1z M64.7,70.2l2.9,4.5l-3.2-0.5L67,79  l-7.8-6.5l3.8,0.1l-2.1-2.7L64.7,70.2z M49.4,66.6l1.7-4.2c0.2-0.5,0.6-0.7,1.1-0.7h7.2l0.5,6.5l-9.4-0.3  C49.8,67.9,49.1,67.3,49.4,66.6z"></path>
                                  <path d="M92.6,83.6H81.1c1.9-0.5,3.3-2.3,3.3-4.3c0-2.5-2-4.5-4.5-4.5s-4.5,2-4.5,4.5c0,2.1,1.4,3.8,3.3,4.3H52  c1.9-0.5,3.3-2.3,3.3-4.3c0-2.5-2-4.5-4.5-4.5s-4.5,2-4.5,4.5c0,2.1,1.4,3.8,3.3,4.3H8.1v3h84.5V83.6z"></path>
                                  <polygon points="66.2,26.4 66.2,13.4 35.4,13.4 35.4,47.8 6.6,47.8 6.6,77.3 9.6,77.3 9.6,50.8 35.4,50.8 35.4,76.1 38.4,76.1   38.4,16.4 63.2,16.4 63.2,56.3 66.2,56.3 66.2,29.4 90.4,29.3 90.4,65.7 93.4,65.7 93.4,26.3 "></polygon>
                                </svg>
                              </div>
                            </td>
                            <td style="width: 50%; vertical-align: middle;">
                              <div style="height: 4px; width: 100%; background-color: transparent;"></div>
                            </td>
                          </tr>
                        </table>
                        <p class="sm-text-xs" style="margin: 12px 0 0; white-space: nowrap; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8">
                          Accepted
                        </p>
                      </td>
                    </tr>
                  </table>
                  {{-- Items table --}}
                  <table cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 32px; width: 100%;">
                    <thead style="background-color: #d02e3a;">
                      <tr>
                        <th style="padding: 8px 12px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe;">Item</th>
                        <th style="padding: 8px 12px; text-align: center; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe;">Qty</th>
                        <th style="padding: 8px 12px; text-align: right; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #fffffe;">Price</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($quote->items as $item)
                        @php
                          $variantLabel = collect($item->product_snapshot['variant']['attributes'] ?? [])->map(fn ($v, $k) => "$k: $v")->join(', ');
                          $subtitle = $variantLabel ?: ($item->product_sku ? 'SKU: '.$item->product_sku : null);
                        @endphp
                        <tr>
                          <td style="border-bottom-width: 1px; border-color: #f1f5f9; padding: 12px;">
                            <div style="display: flex; align-items: center;">
                              <div style="margin-right: 12px; height: 48px; width: 48px; border-radius: 6px; background-color: #f1f5f9; flex-shrink: 0;"></div>
                              <div>
                                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">{{ $item->product_name }}</p>
                                @if ($subtitle)
                                  <p style="margin: 0; font-size: 12px; color: #64748b;">{{ $subtitle }}</p>
                                @endif
                              </div>
                            </div>
                          </td>
                          <td style="border-bottom-width: 1px; border-color: #f1f5f9; padding: 12px; text-align: center; font-size: 14px; color: #475569;">{{ $item->quantity }}</td>
                          <td style="border-bottom-width: 1px; border-color: #f1f5f9; padding: 12px; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b;">
                            {{ $item->line_total_cents > 0 ? money($item->line_total_cents) : '—' }}
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                  {{-- Totals + expiry --}}
                  <table cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 32px; width: 100%;">
                    <tr>
                      <td style="width: 50%; vertical-align: bottom;">
                        <p style="margin: 0 0 4px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #c93237;">Expires</p>
                        <p style="margin: 0; font-size: 14px; font-weight: 700; color: #c93237;">{{ $quote->expires_at?->format('d M Y') }}</p>
                      </td>
                      <td style="width: 50%;">
                        <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%;">
                          <tr>
                            <td style="padding-top: 4px; padding-bottom: 4px; font-size: 14px; color: #475569;">Subtotal</td>
                            <td style="padding-top: 4px; padding-bottom: 4px; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b;">{{ money($quote->subtotal_cents) }}</td>
                          </tr>
                          @if ($quote->discount_cents > 0)
                            <tr>
                              <td style="padding-top: 4px; padding-bottom: 4px; font-size: 14px; color: #475569;">Discount</td>
                              <td style="padding-top: 4px; padding-bottom: 4px; text-align: right; font-size: 14px; font-weight: 600; color: #16a34a;">− {{ money($quote->discount_cents) }}</td>
                            </tr>
                          @endif
                          @if ($quote->shipping_cents > 0)
                            <tr>
                              <td style="padding-top: 4px; padding-bottom: 4px; font-size: 14px; color: #475569;">Shipping</td>
                              <td style="padding-top: 4px; padding-bottom: 4px; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b;">{{ money($quote->shipping_cents) }}</td>
                            </tr>
                          @endif
                          <tr>
                            <td style="border-top-width: 1px; border-color: #e2e8f0; padding-top: 12px; font-size: 16px; font-weight: 700; color: #1e293b;">Total Quote Amount</td>
                            <td style="border-top-width: 1px; border-color: #e2e8f0; padding-top: 12px; text-align: right; font-size: 16px; font-weight: 700; color: #c02434;">{{ money($quote->total_cents) }}</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                  <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                    Please review it as soon as possible. Feel free to reply to this email if you need
                    any adjustments before the offer expires!
                  </p>
                  <div role="separator" style="line-height: 32px">&zwj;</div>
                  <div>
                    <a href="{{ $portalUrl }}" style="display: inline-block; text-decoration: none; padding: 16px 24px; font-size: 16px; line-height: 1; border-radius: 4px; background-color: #c93237; text-align: center; color: #fffffe;" class="hover-bg-_a0272b">
                      <!--[if mso]><i style="mso-font-width: 150%; mso-text-raise: 31px;" hidden>&emsp;</i><![endif]-->
                      <span style="mso-text-raise: 16px">Review &amp; Accept Quote NOW</span>
                      <!--[if mso]><i hidden style="mso-font-width: 150%;">&emsp;&#8203;</i><![endif]-->
                    </a>
                  </div>
                  <div role="separator" style="line-height: 24px">&zwj;</div>
                  <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                    Thank you for choosing {{ config('app.name') }}!
                    <br><br>
                    Best regards,<br>
                    <span style="font-weight: 600; color: #1e293b;">{{ config('app.name') }} Support Team</span>
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
