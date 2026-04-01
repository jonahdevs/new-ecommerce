<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a new order is placed and payment is confirmed.
    //  Sent to the admin team email to alert them of new orders.
    //
    //  Triggered from:
    //    SendNewOrderNotification listener (on PaymentConfirmed event)
    // =========================================================================

    public function __construct(public readonly Order $order) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->order->customerName();
        $customerEmail = $this->order->customerEmail();
        $itemCount = $this->order->items()->count();
        $total = format_currency($this->order->total);
        $adminUrl = route('admin.orders.show', $this->order);

        $shippingAddress = $this->order->shipping_address;
        $location = isset($shippingAddress['county']) 
            ? $shippingAddress['county'] . (isset($shippingAddress['area']) ? ', ' . $shippingAddress['area'] : '')
            : 'Not specified';

        $mail = (new MailMessage)
            ->subject("New Order Received — {$this->order->reference}")
            ->greeting('New order placed')
            ->line("{$customerName} ({$customerEmail}) has placed a new order.")
            ->line("**Reference:** {$this->order->reference}")
            ->line("**Items:** {$itemCount} item(s)")
            ->line("**Total:** {$total}")
            ->line("**Shipping to:** {$location}");

        if ($this->order->customer_notes) {
            $mail->line("**Customer notes:** {$this->order->customer_notes}");
        }

        return $mail
            ->line('Please log in to the admin panel to process this order.')
            ->action('View Order', $adminUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'title' => 'New Order Received',
            'message' => "New order {$this->order->reference} from {$this->order->customerName()} — " . format_currency($this->order->total),
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
