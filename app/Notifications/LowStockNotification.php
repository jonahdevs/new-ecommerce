<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $adminUrl = route('admin.products.edit', $this->product);
        $stockQty = $this->product->stock_quantity;
        $threshold = $this->product->low_stock_threshold;

        return (new MailMessage)
            ->subject("Low Stock Alert — {$this->product->name}")
            ->greeting('Low stock alert')
            ->line("Product **{$this->product->name}** is running low on stock.")
            ->line("**SKU:** {$this->product->sku}")
            ->line("**Current stock:** {$stockQty} units")
            ->line("**Threshold:** {$threshold} units")
            ->line('Please restock this product to avoid running out.')
            ->action('View Product', $adminUrl)
            ->salutation('Sheffield Africa · Inventory Team');
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'title' => 'Low Stock Alert',
            'message' => "{$this->product->name} is low on stock ({$this->product->stock_quantity} units remaining)",
            'url' => route('admin.products.edit', $this->product),
        ];
    }
}
