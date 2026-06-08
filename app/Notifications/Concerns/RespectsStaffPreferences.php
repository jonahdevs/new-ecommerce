<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Settings\NotificationSettings;

/**
 * Shared `via()` for staff-facing operational alerts. Two-tier gate:
 *
 *  1. Global (NotificationSettings) — if the store owner has disabled this
 *     alert type entirely, no staff member receives it.
 *  2. Personal (staff_preferences) — each staff member can mute individual
 *     alert types for themselves via admin → Settings → My notifications.
 */
trait RespectsStaffPreferences
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $settings = app(NotificationSettings::class);
        $baseKey = $this->staffGlobalKey();
        $prefKey = $this->staffPreferenceKey();
        $channels = [];

        // Personal preferences for this notifiable (staff member).
        $prefs = ($notifiable instanceof User && $prefKey !== null)
            ? ($notifiable->staff_preferences['notifications'][$prefKey] ?? [])
            : [];

        // Mail channel — global gate then personal gate.
        if ($baseKey === null || ($settings->{$baseKey.'_email'} ?? true)) {
            if (($prefs['email'] ?? true) !== false) {
                $channels[] = 'mail';
            }
        }

        // Database (in-app) channel — only for User notifiables; anonymous central-email
        // recipients have no notifications() relationship to write to.
        if ($notifiable instanceof User && $this->supportsInApp() && ($baseKey === null || ($settings->{$baseKey.'_inapp'} ?? true))) {
            if (($prefs['inapp'] ?? true) !== false) {
                $channels[] = 'database';
            }
        }

        return $channels;
    }

    /**
     * The base NotificationSettings key for this alert (e.g. 'staff_new_order').
     * The trait appends '_email' / '_inapp' to resolve the per-channel properties.
     * Return null to skip the global gate.
     */
    abstract protected function staffGlobalKey(): ?string;

    /**
     * The key inside staff_preferences['notifications'] for this alert
     * (e.g. 'new_order'), or null to skip personal gating.
     */
    abstract protected function staffPreferenceKey(): ?string;

    /**
     * Override to return true in notifications that implement toArray() for
     * the admin in-app notification bell.
     */
    protected function supportsInApp(): bool
    {
        return false;
    }
}
