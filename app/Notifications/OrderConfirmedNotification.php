<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\TaxService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];
        $channels = [];

        if ($prefs['order_confirmation']['email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing([
            'items.product',
            'payment',
            'user',
        ]);

        $taxService = app(TaxService::class);

        return (new MailMessage)
            ->subject("We've received your order — {$order->reference}")
            ->view('mails.orders.confirmation', [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'Customer',
                'orderUrl' => route('customer.orders.show', $order),
                'paymentLabel' => $this->resolvePaymentLabel(),
                'taxEnabled' => $taxService->isEnabled(),
                'taxInclusive' => $taxService->isInclusive(),
                'taxLabel' => $taxService->name().' ('.$taxService->rateLabel().')',
            ]);
    }

    private function resolvePaymentLabel(): string
    {
        return match ($this->order->payment?->gateway) {
            'mpesa' => 'M-Pesa',
            'stripe' => 'Card',
            'pesawise' => 'Pesawise',
            'pesapal' => 'Pesapal',
            'paypal' => 'PayPal',
            default => ucfirst($this->order->payment?->gateway ?? 'Online'),
        };
    }
}
