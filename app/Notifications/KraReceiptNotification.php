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
            ->subject("Your Tax Invoice — Order {$this->order->reference}")
            ->view('mails.orders.kra-receipt', [
                'order' => $this->order,
                'customerName' => $this->order->user?->name ?? 'Customer',
                'orderUrl' => route('customer.orders.show', $this->order),
            ]);

        // Attach the invoice PDF if it exists on disk
        if ($this->order->invoice_path && Storage::disk('local')->exists($this->order->invoice_path)) {
            $mail->attach(
                Storage::disk('local')->path($this->order->invoice_path),
                ['as' => "Invoice-{$this->order->reference}.pdf", 'mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}
