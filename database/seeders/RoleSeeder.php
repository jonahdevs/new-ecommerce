<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'products' => ['view', 'create', 'edit', 'delete'],
            'product-variants' => ['view', 'create', 'edit', 'delete'],
            'product-images' => ['view', 'create', 'edit', 'delete'],
            'categories' => ['view', 'create', 'edit', 'delete'],
            'brands' => ['view', 'create', 'edit', 'delete'],
            'attributes' => ['view', 'create', 'edit', 'delete'],
            'tags' => ['view', 'create', 'edit', 'delete'],
            'orders' => ['view', 'edit', 'delete'],
            'order-status' => ['manage'],
            'payments' => ['view', 'manage'],
            'inventory' => ['view', 'manage'],
            'reviews' => ['view', 'edit', 'delete', 'approve'],
            'shipping' => ['view', 'create', 'edit', 'delete'],
            'shipping-zones' => ['view', 'create', 'edit', 'delete'],
            'shipping-rules' => ['view', 'create', 'edit', 'delete'],
            'pickup-stations' => ['view', 'create', 'edit', 'delete'],
            'areas' => ['view', 'create', 'edit', 'delete'],
            'settings' => ['view', 'manage'],
            'reports' => ['view', 'export'],
        ];

        foreach ($permissions as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}.{$resource}"]);
            }
        }

        Role::firstOrCreate([
            'name' => 'super_admin',
        ], [
            'is_system' => true,
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
        ], [
            'is_system' => true,
        ]);

        $admin->syncPermissions(
            Permission::whereNotIn('name', [
                'view.roles',
                'create.roles',
                'edit.roles',
                'delete.roles',
            ])->get()
        );

        $logistics = Role::firstOrCreate(['name' => 'logistics_manager'], [
            'is_system' => false,
        ]);

        $logistics->syncPermissions([
            'view.orders',
            'edit.orders',
            'manage.order-status',
            'view.inventory',
            'manage.inventory',
            'view.shipping',
            'create.shipping',
            'edit.shipping',
            'delete.shipping',
            'view.shipping-zones',
            'create.shipping-zones',
            'edit.shipping-zones',
            'delete.shipping-zones',
            'view.shipping-rules',
            'create.shipping-rules',
            'edit.shipping-rules',
            'delete.shipping-rules',
            'view.pickup-stations',
            'create.pickup-stations',
            'edit.pickup-stations',
            'delete.pickup-stations',
            'view.areas',
            'create.areas',
            'edit.areas',
            'delete.areas',
            'view.payments',
        ]);
    }
}
