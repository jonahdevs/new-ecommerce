<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Roles that cannot be edited or deleted through the UI.
     *
     * @var list<string>
     */
    public const PROTECTED_ROLES = ['super-admin', 'admin'];

    /**
     * Full set of admin-panel permissions grouped by resource segment.
     *
     * Naming: <resource>.<action>
     *   .view   — read-only access (list + show)
     *   .manage — full CRUD
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        // Orders
        'orders.view',
        'orders.manage',

        // Quotes
        'quotes.view',
        'quotes.manage',

        // Payments
        'payments.view',

        // Customers
        'customers.view',
        'customers.manage',

        // Reviews
        'reviews.manage',

        // Products
        'products.view',
        'products.manage',

        // Catalog (categories, brands, attributes, tags)
        'catalog.manage',
        'tags.manage',

        // Logistics
        'delivery.manage',

        // Marketing (subscribers, cart recovery, campaigns, coupons…)
        'marketing.manage',

        // Staff
        'staff.manage',

        // Roles & permissions — super-admin only
        'roles.manage',

        // System
        'settings.manage',
    ];

    /**
     * Permissions granted to the "admin" role.
     * Admins can do everything except manage roles and permissions.
     *
     * @var list<string>
     */
    public const ADMIN_PERMISSIONS = [
        'orders.view',
        'orders.manage',
        'quotes.view',
        'quotes.manage',
        'payments.view',
        'customers.view',
        'customers.manage',
        'reviews.manage',
        'products.view',
        'products.manage',
        'catalog.manage',
        'tags.manage',
        'delivery.manage',
        'marketing.manage',
        'staff.manage',
        'settings.manage',
    ];

    /**
     * Permissions granted to the default "staff" role.
     * Staff can view and action orders/quotes but cannot manage
     * catalog structure, staff accounts, or system config.
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
        'products.view',
        'reviews.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure every permission exists.
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // super-admin — no explicit permissions needed; Gate::before() in
        // AppServiceProvider bypasses all permission checks for this role.
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'])
            ->syncPermissions([]);

        // admin — all operational + management permissions, but NOT roles.manage.
        // Use can('roles.manage') anywhere in the codebase; only super-admin passes.
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(self::ADMIN_PERMISSIONS);

        // staff — operational access only.
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions(self::STAFF_PERMISSIONS);
    }
}
