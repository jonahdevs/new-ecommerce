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
 * When staff_email_routing is 'central' and a central email is configured, the
 * email copy is redirected to that single shared inbox — but individual staff
 * are still returned so they continue to receive their in-app (database)
 * notifications. Their notification's via() suppresses the mail channel in
 * central mode to avoid emailing them on top of the central inbox. Otherwise
 * every user holding the given permission (plus super-admins) gets their own
 * email and in-app copy.
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

        $staff = self::staffHolding($permission);

        if ($settings->staff_email_routing === 'central' && filled($settings->staff_central_email)) {
            return $staff->prepend(
                (new AnonymousNotifiable)->route('mail', $settings->staff_central_email),
            );
        }

        return $staff;
    }

    /**
     * Every user holding the permission, plus super-admins.
     *
     * @return Collection<int, User>
     */
    private static function staffHolding(string $permission): Collection
    {
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
