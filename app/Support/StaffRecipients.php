<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Resolves the staff who should receive an operational alert: everyone holding
 * the given permission (through any role) plus super-admins, who bypass the
 * permission gate and therefore wouldn't match a permission query.
 *
 * Defensive by design — a missing permission/role must never break the flow
 * (e.g. payment confirmation) that triggers the notification.
 */
class StaffRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function for(string $permission): Collection
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
