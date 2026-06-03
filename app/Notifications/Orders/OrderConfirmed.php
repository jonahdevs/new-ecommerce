<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Notifications\Concerns\RespectsPreferences;
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
        return ['orders', 'confirmed'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order confirmed — '.$this->order->order_number)
            ->greeting('Thank you for your order')
            ->line('We\'ve received payment for order '.$this->order->order_number.' and it\'s now being processed.')
            ->line('Order total: '.money($this->order->total_cents))
            ->action('View your order', route('account.orders.show', $this->order))
            ->line('We\'ll let you know as soon as it ships.');
    }
}
