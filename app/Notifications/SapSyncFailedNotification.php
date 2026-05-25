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

    public function via(object $notifiable): array
    {
        // This notification is dispatched to an anonymous notifiable via
        // Notification::route('mail', ...). The 'database' channel requires a
        // real Eloquent model with an id and notifications() relationship, so it
        // is intentionally omitted here to avoid a runtime exception.
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("SAP Sync Failed — Order {$this->order->reference}")
            ->greeting('SAP sync alert')
            ->line("Order **{$this->order->reference}** failed to sync with SAP Business One after 3 attempts.")
            ->line("**Error:** {$this->exception->getMessage()}")
            ->line("**Customer:** {$this->order->customerName()} ({$this->order->customerEmail()})")
            ->line('**Order total:** KES '.number_format($this->order->total, 2))
            ->action('View order in admin', url("/admin/orders/{$this->order->id}"))
            ->line('Please investigate and manually retry the sync from the admin panel.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sap_sync_failed',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'error' => $this->exception->getMessage(),
            'attempts' => $this->order->sap_sync_attempts,
        ];
    }
}
