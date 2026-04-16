<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when an order status changes to a customer-relevant status.
    //  Sent to the customer to keep them informed about their order.
    //
    //  Statuses that trigger this notification:
    //    - SHIPPED: Order has been shipped
    //    - DELIVERED: Order has been delivered
    //    - CANCELLED: Order has been cancelled
    // =========================================================================

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $newStatus,
    ) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $subject = match ($this->newStatus) {
            OrderStatus::SHIPPED => "Order Shipped — {$this->order->reference}",
            OrderStatus::DELIVERED => "Order Delivered — {$this->order->reference}",
            OrderStatus::CANCELLED => "Order Cancelled — {$this->order->reference}",
            default => "Order Update — {$this->order->reference}",
        };

        return (new MailMessage)->subject($subject)->view('mails.orders.status', [
            'order' => $this->order->loadMissing('items'),
            'newStatus' => $this->newStatus,
            'customerName' => $this->order->user?->name ?? 'Customer',
            'orderUrl' => route('customer.orders.show', $this->order),
            'subject' => $subject,
        ]);
    }

    public function toArray(): array
    {
        $title = match ($this->newStatus) {
            OrderStatus::SHIPPED => 'Order Shipped',
            OrderStatus::DELIVERED => 'Order Delivered',
            OrderStatus::CANCELLED => 'Order Cancelled',
            default => 'Order Update',
        };

        $message = match ($this->newStatus) {
            OrderStatus::SHIPPED => "Your order {$this->order->reference} has been shipped.",
            OrderStatus::DELIVERED => "Your order {$this->order->reference} has been delivered.",
            OrderStatus::CANCELLED => "Your order {$this->order->reference} has been cancelled.",
            default => "Your order {$this->order->reference} status has been updated.",
        };

        return [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'status' => $this->newStatus->value,
            'title' => $title,
            'message' => $message,
            'url' => route('customer.orders.show', $this->order),
        ];
    }
}
