<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function renamePermissionsMigration(): object
{
    return require database_path('migrations/2026_09_23_082302_rename_permissions_to_the_shield_format.php');
}

it('renames the snake_case permissions keeping their role and user assignments', function () {
    (new RolesAndPermissionsSeeder)->run();
    $user = User::factory()->create();
    $user->givePermissionTo('ViewAny:InventoryLocation');

    renamePermissionsMigration()->down();

    expect(Permission::query()->where('name', 'view_any_inventory_location')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'like', '%:%')->exists())->toBeFalse();

    renamePermissionsMigration()->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Permission::query()->where('name', 'not like', '%:%')->pluck('name')->all())->toBe([])
        ->and(Role::findByName('admin')->hasPermissionTo('ForceDeleteAny:InventoryLocation'))->toBeTrue()
        ->and(Role::findByName('user')->hasPermissionTo('Transition:ReorderOrder'))->toBeTrue()
        ->and(Role::findByName('user')->hasPermissionTo('View:StatsOverview'))->toBeTrue()
        ->and($user->fresh()->hasPermissionTo('ViewAny:InventoryLocation'))->toBeTrue();
});

it('merges a snake_case permission into the one already named in the new format', function () {
    $old = Permission::findOrCreate('view_any_product', 'web');
    $new = Permission::findOrCreate('ViewAny:Product', 'web');
    $oldRole = Role::findOrCreate('old', 'web')->givePermissionTo($old);
    $bothRole = Role::findOrCreate('both', 'web')->givePermissionTo([$old, $new]);

    renamePermissionsMigration()->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Permission::query()->where('name', 'like', '%roduct')->pluck('name')->all())->toBe(['ViewAny:Product'])
        ->and($oldRole->fresh()->hasPermissionTo('ViewAny:Product'))->toBeTrue()
        ->and($bothRole->fresh()->permissions()->count())->toBe(1);
});
