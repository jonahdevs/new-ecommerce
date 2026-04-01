<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FailedPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
        public readonly ?string $reason = null
    ) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->order->customerName();
        $customerEmail = $this->order->customerEmail();
        $total = format_currency($this->order->total);
        $adminUrl = route('admin.orders.show', $this->order);

        $mail = (new MailMessage)
            ->subject("Payment Failed — {$this->order->reference}")
            ->greeting('Payment failure alert')
            ->line("A payment attempt has failed for order {$this->order->reference}.")
            ->line("**Customer:** {$customerName} ({$customerEmail})")
            ->line("**Amount:** {$total}")
            ->line("**Gateway:** {$this->payment->gateway}");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->action('View Order', $adminUrl)
            ->salutation('Sheffield Africa · Payments Team');
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id,
            'payment_id' => $this->payment->id,
            'reference' => $this->order->reference,
            'title' => 'Payment Failed',
            'message' => "Payment failed for order {$this->order->reference} — {$this->reason}",
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
