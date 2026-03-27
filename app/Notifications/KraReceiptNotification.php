<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class KraReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Your KRA receipt — Order {$this->order->reference}")
            ->greeting("Thank you for your order!")
            ->line("Your KRA-compliant receipt for order **{$this->order->reference}** is attached.")
            ->line("**CU Number:** {$this->order->kra_cu_number}")
            ->line("**KRA Invoice:** {$this->order->kra_invoice_number}")
            ->line("**Validated at:** {$this->order->kra_validated_at?->format('d M Y, H:i')}")
            ->line("**Order total:** KES " . number_format($this->order->total, 2))
            ->action('View your order', url("/orders/{$this->order->id}"))
            ->line('Please keep this receipt for your records.');

        // Attach the PDF if it exists on disk
        if ($this->order->kra_receipt_path && Storage::exists($this->order->kra_receipt_path)) {
            $mail->attach(
                Storage::path($this->order->kra_receipt_path),
                ['as' => "KRA-Receipt-{$this->order->reference}.pdf", 'mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}
