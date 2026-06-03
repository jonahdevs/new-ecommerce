<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Staff alert that a paid order needs fulfilment. Not gated by customer
 * preferences — it always reaches the operations team.
 */
class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customer = $this->order->user?->name ?? 'A customer';

        return (new MailMessage)
            ->subject('New order '.$this->order->order_number.' — '.money($this->order->total_cents))
            ->greeting('New order received')
            ->line($customer.' placed order '.$this->order->order_number.'.')
            ->line('Total: '.money($this->order->total_cents))
            ->action('Open in admin', route('admin.orders.show', $this->order));
    }
}
