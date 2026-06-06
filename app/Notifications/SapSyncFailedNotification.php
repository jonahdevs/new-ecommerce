<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SapSyncFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly \Throwable $exception,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('SAP sync failed — '.$this->order->order_number)
            ->greeting('SAP sync failed')
            ->line('Order **'.$this->order->order_number.'** could not be synced to SAP after all retry attempts.')
            ->line('**Customer:** '.($this->order->user?->name ?? 'Guest').' ('.($this->order->user?->email ?? '—').')')
            ->line('**Total:** '.number_format($this->order->total_cents / 100, 2).' KES')
            ->line('**Error:** '.$this->exception->getMessage())
            ->action('View order', route('admin.orders.show', $this->order))
            ->line('Please sync this order manually or contact the SAP administrator.');
    }
}
