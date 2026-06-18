<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Settings\NotificationSettings;

/**
 * Shared `via()` for customer-facing notifications. Two-tier gate:
 *
 *  1. Global (NotificationSettings) — if the store owner has disabled this
 *     notification type entirely, no one receives it regardless of personal prefs.
 *  2. Personal (user's notification_preferences) — a registered user can mute
 *     a category for themselves. Guests always receive the message.
 *
 * A null preference key means the notification doesn't apply to this notifiable
 * and nothing is sent.
 */
trait RespectsPreferences
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $key = $this->preferenceKey();

        if ($key === null) {
            return [];
        }

        // Personal gate — user opted out of this notification category entirely.
        if ($notifiable instanceof User) {
            [$group, $field] = $key;
            $prefs = $notifiable->notification_preferences ?? [];

            if (($prefs[$group][$field] ?? true) === false) {
                return [];
            }
        }

        $settings = app(NotificationSettings::class);
        $channels = [];

        // Email channel
        if ($settings->email_channel_enabled) {
            $emailKey = $this->resolveGlobalKey($key, 'email');

            if ($emailKey === null || $settings->{$emailKey}) {
                $channels[] = 'mail';
            }
        }

        // In-app (database) channel
        if ($settings->inapp_channel_enabled) {
            $inappKey = $this->resolveGlobalKey($key, 'inapp');

            if ($inappKey === null || $settings->{$inappKey}) {
                [$group, $field] = $key;
                $inappPrefs = ($notifiable instanceof User)
                    ? ($notifiable->notification_preferences['inapp'] ?? [])
                    : [];

                $inappMuted = in_array($group, ['marketing', 'account'])
                    ? ($inappPrefs[$group] ?? true) === false
                    : ($inappPrefs[$group][$field] ?? true) === false;

                if (! $inappMuted) {
                    $channels[] = 'database';
                }
            }
        }

        // WhatsApp channel — globally enabled, notification implements toWhatsapp(),
        // notifiable has a phone, and the user hasn't muted this type on WhatsApp.
        if ($settings->whatsapp_channel_enabled && method_exists($this, 'toWhatsapp')) {
            $whatsappKey = $this->resolveGlobalKey($key, 'whatsapp');

            if ($whatsappKey === null || $settings->{$whatsappKey}) {
                $phone = $notifiable instanceof User ? $notifiable->phone : null;

                if ($phone) {
                    [$group, $field] = $key;
                    $waPrefs = ($notifiable instanceof User)
                        ? ($notifiable->notification_preferences['whatsapp'] ?? [])
                        : [];

                    // marketing and account are stored as plain booleans; others as nested arrays.
                    $waMuted = in_array($group, ['marketing', 'account'])
                        ? ($waPrefs[$group] ?? true) === false
                        : ($waPrefs[$group][$field] ?? true) === false;

                    if (! $waMuted) {
                        $channels[] = WhatsAppChannel::class;
                    }
                }
            }
        }

        return $channels;
    }

    /**
     * The [group, field] path into the user's notification_preferences that
     * controls this notification, or null when it shouldn't be sent.
     *
     * @return array{0: string, 1: string}|null
     */
    abstract protected function preferenceKey(): ?array;

    /**
     * Derive the NotificationSettings property name for a given channel.
     * Per-channel properties are named {base}_email / _whatsapp.
     * Returns null when there is no corresponding global toggle.
     *
     * @param  array{0: string, 1: string}  $key
     */
    private function resolveGlobalKey(array $key, string $channel): ?string
    {
        [$group, $field] = $key;

        $base = match ($group) {
            'orders' => "customer_order_{$field}",
            'quotes' => "customer_quote_{$field}",
            'marketing' => 'customer_marketing',
            'account' => 'customer_account_security',
            default => null,
        };

        return $base !== null ? "{$base}_{$channel}" : null;
    }
}
