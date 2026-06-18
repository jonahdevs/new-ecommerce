<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Notifications\Concerns\RespectsPreferences;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Customer receipt sent once payment is confirmed and the order moves into
 * processing.
 */
class OrderConfirmed extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsPreferences;

    public function __construct(public Order $order) {}

    protected function preferenceKey(): ?array
    {
        return ['orders', 'confirmation'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject('Order confirmed — '.$order->order_number)
            ->view('mails.orders.confirmation', [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'there',
                'paymentLabel' => $this->resolvePaymentLabel($order->payment_method),
                'orderUrl' => route('account.orders.show', $order),
            ]);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_confirmed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_cents' => $this->order->total_cents,
            'currency' => $this->order->currency,
        ];
    }

    public function toWhatsapp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::template('hello_world');
    }

    /**
     * Map the stored payment method key to a human-friendly label.
     */
    private function resolvePaymentLabel(?string $method): string
    {
        return match ($method) {
            'mpesa' => 'M-Pesa',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'net_30' => 'Net 30 Terms',
            default => 'Payment',
        };
    }
}
