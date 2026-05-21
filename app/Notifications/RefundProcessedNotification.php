<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use App\Settings\CustomerNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Sent to the customer when a refund has been processed for their order.
    // =========================================================================

    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
        public readonly float $refundAmount,
        public readonly string $refundReason,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! app(CustomerNotificationSettings::class)->order_refunded) {
            return $channels;
        }

        $prefs = $notifiable->notification_preferences ?? [];

        if ($prefs['order_updates']['email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject("Refund Processed — {$this->order->reference}")
            ->view('mails.orders.refund-processed', [
                'order' => $this->order,
                'payment' => $this->payment,
                'refundAmount' => $this->refundAmount,
                'refundReason' => $this->refundReason,
                'customerName' => $this->order->user?->name ?? 'Customer',
                'orderUrl' => route('customer.orders.show', $this->order),
            ]);
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id,
            'payment_id' => $this->payment->id,
            'reference' => $this->order->reference,
            'refund_amount' => $this->refundAmount,
            'title' => 'Refund Processed',
            'message' => 'A refund of '.format_currency($this->refundAmount)." has been processed for order {$this->order->reference}.",
            'url' => route('customer.orders.show', $this->order),
        ];
    }
}
