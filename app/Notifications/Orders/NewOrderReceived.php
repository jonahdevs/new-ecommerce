<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Notifications\Concerns\RespectsStaffPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsStaffPreferences;

    public function __construct(public Order $order) {}

    protected function staffGlobalKey(): ?string
    {
        return 'staff_new_order';
    }

    protected function staffPreferenceKey(): ?string
    {
        return 'new_order';
    }

    protected function supportsInApp(): bool
    {
        return true;
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

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->user?->name,
            'total' => $this->order->total_cents,
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
