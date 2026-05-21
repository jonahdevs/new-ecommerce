<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutOfStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsNotification('notify_out_of_stock')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(): MailMessage
    {
        $adminUrl = route('admin.products.edit', $this->product);

        return (new MailMessage)
            ->subject("Out of Stock — {$this->product->name}")
            ->greeting('Out of stock alert')
            ->line("Product **{$this->product->name}** has run out of stock.")
            ->line("**SKU:** {$this->product->sku}")
            ->line('Please restock this product as soon as possible to avoid losing sales.')
            ->action('View Product', $adminUrl)
            ->salutation('Sheffield Africa · Inventory Team');
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'title' => 'Out of Stock',
            'message' => "{$this->product->name} is now out of stock",
            'url' => route('admin.products.edit', $this->product),
        ];
    }
}
