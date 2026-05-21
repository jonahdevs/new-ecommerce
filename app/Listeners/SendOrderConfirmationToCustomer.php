<?php

namespace App\Listeners;

use App\Events\PaymentConfirmed;
use App\Notifications\OrderConfirmedNotification;
use App\Settings\CustomerNotificationSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendOrderConfirmationToCustomer
{
    public function __construct(
        private readonly CustomerNotificationSettings $customerNotificationSettings,
    ) {}

    public function handle(PaymentConfirmed $event): void
    {
        if (! $this->customerNotificationSettings->order_confirmation) {
            return;
        }

        $order = $event->order;
        $email = $order->customerEmail();

        if (! $email) {
            Log::warning('Order confirmation: no customer email, skipping', [
                'order_id' => $order->id,
            ]);

            return;
        }

        try {
            $order->user
                ? $order->user->notify(new OrderConfirmedNotification($order))
                : Notification::route('mail', $email)->notify(new OrderConfirmedNotification($order));

            Log::info('Order confirmation email sent to customer', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
