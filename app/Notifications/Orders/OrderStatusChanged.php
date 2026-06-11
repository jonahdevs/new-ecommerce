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
            OrderStatus::OUT_FOR_DELIVERY,
            OrderStatus::COMPLETED,
            OrderStatus::CANCELLED => ['orders', 'updates'],
            default => null,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $number = $order->order_number;

        $subject = match ($order->status) {
            OrderStatus::OUT_FOR_DELIVERY => 'Your order is on its way — '.$number,
            OrderStatus::COMPLETED => 'Order completed — '.$number,
            OrderStatus::CANCELLED => 'Order cancelled — '.$number,
            default => 'Order update — '.$number,
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('mails.orders.status-update', [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'there',
                'newStatus' => $order->status,
            ]);
    }
}
