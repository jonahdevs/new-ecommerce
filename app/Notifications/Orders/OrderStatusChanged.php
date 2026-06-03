<?php

namespace App\Notifications\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Customer update for fulfilment milestones (out for delivery, delivered,
 * cancelled). Other statuses map to no preference key, so nothing is sent.
 */
class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsPreferences;

    public function __construct(public Order $order) {}

    protected function preferenceKey(): ?array
    {
        return match ($this->order->status) {
            OrderStatus::OUT_FOR_DELIVERY => ['orders', 'shipped'],
            OrderStatus::DELIVERED => ['orders', 'delivered'],
            OrderStatus::CANCELLED => ['orders', 'cancelled'],
            default => null,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $number = $this->order->order_number;

        $mail = (new MailMessage)->action('View your order', route('account.orders.show', $this->order));

        return match ($this->order->status) {
            OrderStatus::OUT_FOR_DELIVERY => $mail
                ->subject('Your order is on its way — '.$number)
                ->greeting('Your order is out for delivery')
                ->line('Order '.$number.' has left our warehouse and is on its way to you.'),
            OrderStatus::DELIVERED => $mail
                ->subject('Order delivered — '.$number)
                ->greeting('Your order has been delivered')
                ->line('Order '.$number.' has been delivered. We hope everything arrived in great shape.'),
            OrderStatus::CANCELLED => $mail
                ->subject('Order cancelled — '.$number)
                ->greeting('Your order has been cancelled')
                ->line('Order '.$number.' has been cancelled. If this is unexpected, please get in touch.'),
            default => $mail
                ->subject('Order update — '.$number)
                ->line('There is an update to your order '.$number.'.'),
        };
    }
}
