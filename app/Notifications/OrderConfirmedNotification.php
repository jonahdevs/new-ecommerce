<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class OrderConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing([
            'items.product',
            'payment',
            'deliveryOrder.shippingMethod',
            'deliveryOrder.pickupStation',
            'user',
        ]);

        $mail = (new MailMessage)
            ->subject("Order Confirmed — {$order->reference}")
            ->view('mails.orders.confirmation', [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'Customer',
                'orderUrl' => route('customer.orders.show', $order),
                'deliveryWindow' => $this->resolveDeliveryWindow(),
                'paymentLabel' => $this->resolvePaymentLabel(),
            ]);

        // Attach the invoice PDF if it exists on disk
        if ($order->invoice_path && Storage::disk('local')->exists($order->invoice_path)) {
            $mail->attach(
                Storage::disk('local')->path($order->invoice_path),
                ['as' => "Invoice-{$order->reference}.pdf", 'mime' => 'application/pdf']
            );
        }

        return $mail;
    }

    private function resolveDeliveryWindow(): ?string
    {
        $delivery = $this->order->deliveryOrder;

        if (! $delivery) {
            return null;
        }

        $min = $delivery->shippingRate?->estimated_days_min;
        $max = $delivery->shippingRate?->estimated_days_max;

        if ($min && $max) {
            return $min === $max ? "{$min} business days" : "{$min}–{$max} business days";
        }

        if ($delivery->estimated_delivery_at) {
            return 'By '.$delivery->estimated_delivery_at->format('D, M j');
        }

        return null;
    }

    private function resolvePaymentLabel(): string
    {
        return match ($this->order->payment?->gateway) {
            'mpesa' => 'M-Pesa',
            'stripe' => 'Card',
            'pesawise' => 'Pesawise',
            'pesapal' => 'Pesapal',
            'paypal' => 'PayPal',
            default => ucfirst($this->order->payment?->gateway ?? 'Online'),
        };
    }
}

