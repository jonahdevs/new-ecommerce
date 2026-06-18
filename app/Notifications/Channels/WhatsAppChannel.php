<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\WhatsAppMessage;
use App\Settings\NotificationSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $phone) {
            return;
        }

        $settings = app(NotificationSettings::class);
        $token = $settings->whatsapp_api_token ?: config('services.whatsapp.api_token');
        $phoneNumberId = $settings->whatsapp_phone_number_id ?: config('services.whatsapp.phone_number_id');

        if (! $token || ! $phoneNumberId) {
            Log::warning('WhatsApp credentials not configured — notification skipped.', [
                'notification' => $notification::class,
            ]);

            return;
        }

        /** @var WhatsAppMessage $message */
        $message = $notification->toWhatsapp($notifiable);

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalisePhone($phone),
                'type' => 'template',
                'template' => [
                    'name' => $message->template,
                    'language' => ['code' => $message->language],
                    'components' => $message->components,
                ],
            ]);

        if (! $response->successful()) {
            Log::error('WhatsApp message failed.', [
                'notification' => $notification::class,
                'to' => $phone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }
    }

    /**
     * Normalise to E.164 digits only (no + prefix).
     * Handles 0712… (Kenya local) → 254712…
     */
    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // Kenyan local format: 07xx or 01xx → 254xx
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        }

        return $digits;
    }
}
