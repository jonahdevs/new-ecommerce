<?php

namespace App\Notifications\Concerns;

use App\Models\User;
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

        // 1. Global gate — store owner master switch
        $globalKey = $this->resolveGlobalKey($key);

        if ($globalKey !== null && ! app(NotificationSettings::class)->{$globalKey}) {
            return [];
        }

        // 2. Personal gate — registered user's own preference
        if ($notifiable instanceof User) {
            [$group, $field] = $key;
            $prefs = $notifiable->notification_preferences ?? [];

            if (($prefs[$group][$field] ?? true) === false) {
                return [];
            }
        }

        return ['mail'];
    }

    /**
     * The [group, field] path into the user's notification_preferences that
     * controls this notification, or null when it shouldn't be sent.
     *
     * @return array{0: string, 1: string}|null
     */
    abstract protected function preferenceKey(): ?array;

    /**
     * Derive the NotificationSettings property name from the preference key.
     * Per-channel properties are named {base}_email / _inapp / _whatsapp, so
     * we check the _email variant (the only channel currently implemented).
     * Returns null when there is no corresponding global toggle.
     *
     * @param  array{0: string, 1: string}  $key
     */
    private function resolveGlobalKey(array $key): ?string
    {
        [$group, $field] = $key;

        $base = match ($group) {
            'orders' => "customer_order_{$field}",
            'quotes' => "customer_quote_{$field}",
            'marketing' => 'customer_marketing',
            'account' => 'customer_account_security',
            default => null,
        };

        return $base !== null ? "{$base}_email" : null;
    }
}
