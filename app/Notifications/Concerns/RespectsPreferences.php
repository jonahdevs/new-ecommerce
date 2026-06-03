<?php

namespace App\Notifications\Concerns;

use App\Models\User;

/**
 * Shared `via()` for customer-facing notifications: a registered user can mute
 * a category from their notification preferences, while guests (notified by
 * on-demand mail route) always receive the message. A null preference key means
 * the notification doesn't apply to the notifiable and nothing is sent.
 */
trait RespectsPreferences
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $key = $this->preferenceKey();

        if ($key === null) {
            return [];
        }

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
}
