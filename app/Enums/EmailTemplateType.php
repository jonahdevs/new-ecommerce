<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    case OrderConfirmed  = 'order_confirmed';
    case KraTaxInvoice   = 'kra_invoice';
    case QuoteSent       = 'quote_sent';
    case OrderStatus     = 'order_status';
    case PasswordReset   = 'password_reset';
    case EmailVerification = 'email_verification';

    public function label(): string
    {
        return match ($this) {
            self::OrderConfirmed    => 'Order Confirmation',
            self::KraTaxInvoice     => 'KRA Tax Invoice',
            self::QuoteSent         => 'Quote Sent',
            self::OrderStatus       => 'Order Status Update',
            self::PasswordReset     => 'Password Reset',
            self::EmailVerification => 'Email Verification',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OrderConfirmed    => 'Sent to customers immediately after a successful payment.',
            self::KraTaxInvoice     => 'Sent once the KRA CU number is received and the invoice PDF is generated.',
            self::QuoteSent         => 'Sent to customers when a quotation is shared with them.',
            self::OrderStatus       => 'Sent when an order status changes (processing, shipped, delivered).',
            self::PasswordReset     => 'Sent when a customer requests a password reset.',
            self::EmailVerification => 'Sent to verify a new customer email address.',
        };
    }

    /** Variable tokens available in this template */
    public function variables(): array
    {
        $base = [
            ['token' => '{{customer_name}}',    'description' => 'Customer full name'],
            ['token' => '{{store_name}}',        'description' => 'Store name'],
        ];

        $order = [
            ['token' => '{{order_reference}}',  'description' => 'Order reference number'],
            ['token' => '{{order_total}}',       'description' => 'Order total (KES)'],
            ['token' => '{{order_url}}',         'description' => 'Link to order detail page'],
            ['token' => '{{payment_method}}',    'description' => 'Payment method used'],
            ['token' => '{{order_date}}',        'description' => 'Date the order was placed'],
        ];

        return match ($this) {
            self::OrderConfirmed => array_merge($base, $order),
            self::KraTaxInvoice  => array_merge($base, $order, [
                ['token' => '{{kra_cu_number}}', 'description' => 'KRA CU number'],
            ]),
            self::QuoteSent => array_merge($base, [
                ['token' => '{{quote_reference}}', 'description' => 'Quotation reference'],
                ['token' => '{{quote_url}}',        'description' => 'Link to quotation page'],
                ['token' => '{{valid_until}}',      'description' => 'Quote expiry date'],
            ]),
            self::OrderStatus => array_merge($base, $order, [
                ['token' => '{{status_label}}', 'description' => 'New order status'],
            ]),
            self::PasswordReset => array_merge($base, [
                ['token' => '{{reset_url}}',    'description' => 'Password reset link'],
                ['token' => '{{expires_in}}',   'description' => 'Link expiry time'],
            ]),
            self::EmailVerification => array_merge($base, [
                ['token' => '{{verify_url}}',   'description' => 'Email verification link'],
            ]),
        };
    }
}
