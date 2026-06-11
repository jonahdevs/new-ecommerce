<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Delivers the customer's KRA-validated tax receipt once it has been generated.
 * As a statutory tax document this is transactional and always sent — it is not
 * subject to the customer's notification preferences.
 */
class KraInvoiceReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        $mail = (new MailMessage)
            ->subject('Your KRA Tax Invoice — '.$order->order_number)
            ->view('mails.orders.kra-invoice', [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'there',
                'orderUrl' => route('account.orders.show', $order),
            ]);

        if ($order->kra_receipt_path && Storage::disk('local')->exists($order->kra_receipt_path)) {
            $mail->attach(Storage::disk('local')->path($order->kra_receipt_path), [
                'as' => 'KRA-Invoice-'.$order->order_number.'.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
