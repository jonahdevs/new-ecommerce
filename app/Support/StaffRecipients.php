<?php

namespace App\Support;

use App\Models\User;
use App\Settings\NotificationSettings;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Resolves the staff who should receive an operational alert.
 *
 * When staff_email_routing is 'central' and a central email is configured,
 * all notifications are directed to that single inbox instead of individual
 * staff members. Otherwise every user holding the given permission (plus
 * super-admins) receives their own copy.
 *
 * Defensive by design — a missing permission/role must never break the flow
 * (e.g. payment confirmation) that triggers the notification.
 */
class StaffRecipients
{
    /**
     * @return Collection<int, User|AnonymousNotifiable>
     */
    public static function for(string $permission): Collection
    {
        $settings = app(NotificationSettings::class);

        if ($settings->staff_email_routing === 'central' && filled($settings->staff_central_email)) {
            return collect([
                (new AnonymousNotifiable)->route('mail', $settings->staff_central_email),
            ]);
        }

        $ids = collect();

        if (Permission::where('name', $permission)->exists()) {
            $ids = $ids->merge(User::permission($permission)->pluck('id'));
        }

        if (Role::where('name', 'super-admin')->exists()) {
            $ids = $ids->merge(User::role('super-admin')->pluck('id'));
        }

        $ids = $ids->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $ids)->get();
    }
}
