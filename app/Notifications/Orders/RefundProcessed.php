<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the customer a refund has been issued against their order, with the
 * refunded amount and reason. Gated under the customer's order-updates
 * preference.
 *
 * Note: not yet dispatched anywhere — there is no refund flow in the app today.
 * The class and its template are ready for when one is built.
 */
class RefundProcessed extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsPreferences;

    public function __construct(
        public Order $order,
        public int $refundAmountCents,
        public ?string $refundReason = null,
    ) {}

    protected function preferenceKey(): ?array
    {
        return ['orders', 'updates'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject('Refund processed — '.$order->order_number)
            ->view('mails.orders.refund-processed', [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'there',
                'refundAmount' => $this->refundAmountCents,
                'refundReason' => $this->refundReason,
                'orderUrl' => route('account.orders.show', $order),
            ]);
    }
}
