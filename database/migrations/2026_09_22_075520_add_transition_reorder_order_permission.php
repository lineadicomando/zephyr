<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create the permission required by the reorder order status actions and
     * grant it to the preset roles that are expected to use them.
     */
    public function up(): void
    {
        $permission = Permission::findOrCreate('transition_reorder_order', 'web');

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['super_admin', 'admin', 'user'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', 'transition_reorder_order')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
