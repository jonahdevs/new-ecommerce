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
    //    - CONFIRMED: Order has been confirmed
    //    - PROCESSING: Order is being prepared
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
        $customerName = $this->order->user?->name ?? 'Customer';
        $orderUrl = route('customer.orders.show', $this->order);

        $subject = match ($this->newStatus) {
            OrderStatus::CONFIRMED => "Order Confirmed — {$this->order->reference}",
            OrderStatus::PROCESSING => "Order Being Prepared — {$this->order->reference}",
            OrderStatus::SHIPPED => "Order Shipped — {$this->order->reference}",
            OrderStatus::DELIVERED => "Order Delivered — {$this->order->reference}",
            OrderStatus::CANCELLED => "Order Cancelled — {$this->order->reference}",
            default => "Order Update — {$this->order->reference}",
        };

        $message = match ($this->newStatus) {
            OrderStatus::CONFIRMED => 'Your order has been confirmed and is being processed.',
            OrderStatus::PROCESSING => 'Your order is now being prepared for shipment.',
            OrderStatus::SHIPPED => 'Great news! Your order has been shipped and is on its way.',
            OrderStatus::DELIVERED => 'Your order has been delivered. We hope you enjoy your purchase!',
            OrderStatus::CANCELLED => 'Your order has been cancelled. If you have any questions, please contact us.',
            default => 'Your order status has been updated.',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$customerName},")
            ->line($message)
            ->line("**Order reference:** {$this->order->reference}")
            ->line('**Total:** '.format_currency($this->order->total));

        if ($this->newStatus === OrderStatus::SHIPPED && $this->order->tracking_number) {
            $mail->line("**Tracking number:** {$this->order->tracking_number}");
        }

        return $mail
            ->action('View Order', $orderUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }

    public function toArray(): array
    {
        $title = match ($this->newStatus) {
            OrderStatus::CONFIRMED => 'Order Confirmed',
            OrderStatus::PROCESSING => 'Order Being Prepared',
            OrderStatus::SHIPPED => 'Order Shipped',
            OrderStatus::DELIVERED => 'Order Delivered',
            OrderStatus::CANCELLED => 'Order Cancelled',
            default => 'Order Update',
        };

        $message = match ($this->newStatus) {
            OrderStatus::CONFIRMED => "Your order {$this->order->reference} has been confirmed.",
            OrderStatus::PROCESSING => "Your order {$this->order->reference} is being prepared.",
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
