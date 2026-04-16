<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed – {{ $order->reference }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .brand-red { color: #c02434; }
        .bg-brand-red { background-color: #c02434; }
        .text-slate-600 { color: #475569; }
        .text-slate-800 { color: #1e293b; }
    </style>
</head>
<body style="background-color: #f8fafc; padding: 24px 16px;">
    <table align="center" class="container" cellpadding="0" cellspacing="0" role="presentation" style="border-radius: 6px; overflow: hidden; border: 1px solid #c02434;">
        <tr>
            <td style="padding: 24px 36px;">
                <!-- Logo -->
                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ config('app.url') }}">
                        <img src="{{ asset('images/logo.png') }}" width="120" alt="Sheffield Logo">
                    </a>
                </div>

                <div style="height: 1px; background-color: #c02434; margin-bottom: 24px;"></div>

                <p style="margin: 0 0 12px; font-size: 16px; color: #475569;">Hi {{ $customerName }},</p>

                <p style="margin: 0 0 32px; font-size: 16px; line-height: 24px; color: #475569;">
                    Thank you for shopping with us! Your order <span style="font-weight: 600; color: #1e293b;">#{{ $order->reference }}</span> has been placed successfully and is now being processed.
                </p>

                <!-- Horizontal Timeline -->
                <table style="margin-bottom: 32px;" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <!-- Step 1: Placed (Active) -->
                        <td style="width: 40px; text-align: center;">
                            <div style="height: 40px; width: 40px; margin: 0 auto; background-color: #c02434; border: 2px solid #c02434; border-radius: 9999px; line-height: 36px; text-align: center;">
                                <span style="color: #ffffff; font-weight: bold; font-size: 18px;">✓</span>
                            </div>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="height: 4px; background-color: #c02434;"></div>
                        </td>
                        <!-- Step 2: Confirmed (Active) -->
                        <td style="width: 40px; text-align: center;">
                            <div style="height: 40px; width: 40px; margin: 0 auto; background-color: #c02434; border: 2px solid #c02434; border-radius: 9999px; line-height: 36px; text-align: center;">
                                <span style="color: #ffffff; font-weight: bold; font-size: 18px;">✓</span>
                            </div>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="height: 4px; background-color: #e2e8f0;"></div>
                        </td>
                        <!-- Step 3: Shipped -->
                        <td style="width: 40px; text-align: center;">
                            <div style="height: 40px; width: 40px; margin: 0 auto; background-color: #ffffff; border: 2px solid #e2e8f0; border-radius: 9999px; line-height: 36px; text-align: center;">
                                <span style="color: #cbd5e1; font-weight: bold; font-size: 14px;">3</span>
                            </div>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="height: 4px; background-color: #e2e8f0;"></div>
                        </td>
                        <!-- Step 4: Delivered -->
                        <td style="width: 40px; text-align: center;">
                            <div style="height: 40px; width: 40px; margin: 0 auto; background-color: #ffffff; border: 2px solid #e2e8f0; border-radius: 9999px; line-height: 36px; text-align: center;">
                                <span style="color: #cbd5e1; font-weight: bold; font-size: 14px;">4</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding-top: 12px;">
                            <p style="margin: 0; font-size: 11px; font-weight: bold; color: #c02434; text-transform: uppercase; letter-spacing: 1px;">Placed</p>
                        </td>
                        <td></td>
                        <td style="text-align: center; padding-top: 12px;">
                            <p style="margin: 0; font-size: 11px; font-weight: bold; color: #c02434; text-transform: uppercase; letter-spacing: 1px;">Confirmed</p>
                        </td>
                        <td></td>
                        <td style="text-align: center; padding-top: 12px;">
                            <p style="margin: 0; font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Shipped</p>
                        </td>
                        <td></td>
                        <td style="text-align: center; padding-top: 12px;">
                            <p style="margin: 0; font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Delivered</p>
                        </td>
                    </tr>
                </table>

                <!-- Items Table -->
                <table style="margin-bottom: 32px;" cellpadding="0" cellspacing="0" role="presentation">
                    <thead style="background-color: #d02e3a;">
                        <tr>
                            <th style="text-align: left; padding: 8px 12px; font-size: 12px; font-weight: bold; color: #ffffff; text-transform: uppercase;">Item</th>
                            <th style="text-align: center; padding: 8px 12px; font-size: 12px; font-weight: bold; color: #ffffff; text-transform: uppercase;">Qty</th>
                            <th style="text-align: right; padding: 8px 12px; font-size: 12px; font-weight: bold; color: #ffffff; text-transform: uppercase;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td style="padding: 12px; border-bottom: 1px solid #f1f5f9;">
                                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">{{ $item->getProductName() }}</p>
                                @if($item->getProductSku())
                                <p style="margin: 0; font-size: 12px; color: #64748b;">SKU: {{ $item->getProductSku() }}</p>
                                @endif
                            </td>
                            <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: center; font-size: 14px; color: #475569;">{{ $item->quantity }}</td>
                            <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: right; font-size: 14px; font-weight: 600; color: #1e293b;">{{ format_currency($item->total_cents / 100) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Summary -->
                <table style="margin-bottom: 32px;" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="width: 50%; vertical-align: bottom;">
                            <p style="margin: 0 0 4px; font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase;">Payment Method</p>
                            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">{{ $paymentLabel }}</p>
                        </td>
                        <td style="width: 50%;">
                            <table cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="padding: 4px 0; font-size: 14px; color: #475569;">Subtotal</td>
                                    <td style="padding: 4px 0; font-size: 14px; font-weight: 600; color: #1e293b; text-align: right;">{{ format_currency($order->subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0; font-size: 14px; color: #475569;">Shipping</td>
                                    <td style="padding: 4px 0; font-size: 14px; font-weight: 600; color: #1e293b; text-align: right;">{{ $order->shipping == 0 ? 'Free' : format_currency($order->shipping) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0 0; border-top: 1px solid #e2e8f0; font-size: 16px; font-weight: bold; color: #1e293b;">Total</td>
                                    <td style="padding: 12px 0 0; border-top: 1px solid #e2e8f0; font-size: 16px; font-weight: bold; color: #c02434; text-align: right;">{{ format_currency($order->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <p style="margin: 0 0 24px; font-size: 16px; line-height: 24px; color: #475569;">
                    If you need any help or have questions about your order, please don't hesitate to reach out to our support team. We're here to help!
                </p>

                <p style="margin: 0; font-size: 16px; line-height: 24px; color: #475569;">
                    Thank you for choosing Sheffield Africa!
                    <br><br>
                    Best regards,<br>
                    <span style="font-weight: 600; color: #1e293b;">Sheffield Africa Support Team</span>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px 36px; background-color: #f8fafc; text-align: center;">
                <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                    &copy; {{ date('Y') }} Sheffield Africa. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
