<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Order $order,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order Confirmed – {$this->order->reference}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.orders.confirmation',
            with: [
                'order' => $this->order->load([
                    'items.product',
                    'payment',
                    'deliveryOrder.shippingMethod',
                    'deliveryOrder.pickupStation',
                    'user',
                ]),
                'customerName' => $this->order->user?->name
                    ?? $this->order->shipping_address['full_name']
                    ?? 'Customer',
                'deliveryWindow' => $this->resolveDeliveryWindow(),
                'paymentLabel' => $this->resolvePaymentLabel(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function resolveDeliveryWindow(): ?string
    {
        $delivery = $this->order->deliveryOrder;

        if (!$delivery)
            return null;

        $min = $delivery->shippingRate?->estimated_days_min;
        $max = $delivery->shippingRate?->estimated_days_max;

        if ($min && $max) {
            return $min === $max ? "{$min} business days" : "{$min}–{$max} business days";
        }

        if ($delivery->estimated_delivery_at) {
            return 'By ' . $delivery->estimated_delivery_at->format('D, M j');
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
