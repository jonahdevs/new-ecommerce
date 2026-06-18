<?php

namespace App\Notifications\Inventory;

use App\Models\Product;
use App\Notifications\Concerns\RespectsStaffPreferences;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsStaffPreferences;

    public function __construct(
        public readonly Product $product,
        public readonly int $currentQuantity,
    ) {}

    protected function staffGlobalKey(): ?string
    {
        return 'staff_low_stock';
    }

    protected function staffPreferenceKey(): ?string
    {
        return 'low_stock';
    }

    protected function supportsInApp(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low stock alert — '.$this->product->name)
            ->greeting('Low stock alert')
            ->line('"'.$this->product->name.'" has '.$this->currentQuantity.' unit(s) remaining, at or below its low stock threshold.')
            ->action('View product', route('admin.products.edit', $this->product));
    }

    public function toWhatsapp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::template('staff_low_stock')
            ->body(
                $this->product->name,
                (string) $this->currentQuantity,
            );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_quantity' => $this->currentQuantity,
            'url' => route('admin.products.edit', $this->product),
        ];
    }
}
