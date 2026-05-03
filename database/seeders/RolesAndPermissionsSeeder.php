<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Generate Filament/Shield permissions for the app panel.
        Artisan::call('shield:generate', [
            '--all' => true,
            '--option' => 'permissions',
            '--panel' => 'app',
            '--no-interaction' => true,
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $allPermissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        // Super admin keeps full access (also covered by Shield gate intercept).
        $superAdmin->permissions()->sync($allPermissionIds);

        // Admin preset: full operational access, no role-management permissions.
        $adminPermissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'not like', '%role%')
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $admin->permissions()->sync($adminPermissionIds);

        // User preset: focused operational permissions.
        $userPermissionNames = [
            'view_any_task',
            'view_task',
            'create_task',
            'update_task',
            'view_any_inventory',
            'view_inventory',
            'view_any_stock',
            'view_stock',
            'view_any_product',
            'view_product',
            'view_any_movement',
            'view_movement',
            'create_movement',
            'update_movement',
            'view_any_reorder',
            'view_reorder',
            'view_any_reorder_order',
            'view_reorder_order',
            'create_reorder_order',
            'update_reorder_order',
            'transition_reorder_order',
            'view_any_task_status',
            'view_task_status',
            'view_any_task_type',
            'view_task_type',
            'view_any_inventory_location',
            'view_inventory_location',
            'view_any_inventory_position',
            'view_inventory_position',
            'view_any_scope',
            'view_scope',
        ];

        $userPermissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $userPermissionNames)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $user->permissions()->sync($userPermissionIds);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
