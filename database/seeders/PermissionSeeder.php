<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * The full set of admin-panel permissions, grouped for the management UI
     * by the segment before the dot.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'orders.view',
        'orders.manage',
        'quotes.view',
        'quotes.manage',
        'payments.view',
        'customers.view',
        'reviews.manage',
        'products.view',
        'products.manage',
        'catalog.manage',
        'tags.manage',
        'delivery.manage',
        'settings.manage',
        'staff.manage',
        'roles.manage',
    ];

    /**
     * Permissions granted to the default "staff" role.
     *
     * @var list<string>
     */
    public const STAFF_PERMISSIONS = [
        'orders.view',
        'orders.manage',
        'quotes.view',
        'quotes.manage',
        'payments.view',
        'customers.view',
        'reviews.manage',
        'products.view',
        'products.manage',
        'catalog.manage',
        'tags.manage',
        'delivery.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(self::PERMISSIONS);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions(self::STAFF_PERMISSIONS);
    }
}
