<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function checklistPermissionsMigration(): object
{
    return require database_path('migrations/2026_09_23_174627_grant_the_checklist_permissions.php');
}

it('adds the checklist permissions to the preset roles keeping their other permissions', function () {
    $customPermission = Permission::findOrCreate('Update:Inventory', 'web');
    $superAdmin = Role::findOrCreate('super_admin', 'web');
    $admin = Role::findOrCreate('admin', 'web')->givePermissionTo($customPermission);
    $user = Role::findOrCreate('user', 'web');

    checklistPermissionsMigration()->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($superAdmin->fresh()->hasPermissionTo('ForceDeleteAny:ChecklistTemplate'))->toBeTrue()
        ->and($superAdmin->fresh()->hasPermissionTo('Update:Tag'))->toBeTrue()
        ->and($admin->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe([
            'Update:Inventory',
            'View:ChecklistAnomaliesWidget',
            'View:ChecklistTemplate',
            'View:Tag',
            'ViewAny:ChecklistTemplate',
            'ViewAny:Tag',
        ])
        ->and($user->fresh()->permissions->pluck('name')->all())->toBe(['View:ChecklistAnomaliesWidget']);
});

it('removes the checklist permissions when rolled back', function () {
    Permission::findOrCreate('View:TaskType', 'web');
    checklistPermissionsMigration()->up();

    checklistPermissionsMigration()->down();

    expect(Permission::query()->where('name', 'like', '%Checklist%')->orWhere('name', 'like', '%:Tag')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'View:TaskType')->exists())->toBeTrue();
});
