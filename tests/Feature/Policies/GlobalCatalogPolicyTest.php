<?php

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

dataset('global catalog models', [
    'product' => Product::class,
    'product brand' => ProductBrand::class,
    'product group' => ProductGroup::class,
    'product model' => ProductModel::class,
    'product type' => ProductType::class,
    'task status' => TaskStatus::class,
    'task type' => TaskType::class,
]);

it('lets admins read the global catalog without changing it', function (string $modelClass) {
    $admin = User::factory()->create()->assignRole('admin');
    $record = new $modelClass;

    expect($admin->can('viewAny', $modelClass))->toBeTrue()
        ->and($admin->can('view', $record))->toBeTrue()
        ->and($admin->can('create', $modelClass))->toBeFalse()
        ->and($admin->can('update', $record))->toBeFalse()
        ->and($admin->can('delete', $record))->toBeFalse()
        ->and($admin->can('deleteAny', $modelClass))->toBeFalse()
        ->and($admin->can('restore', $record))->toBeFalse()
        ->and($admin->can('forceDelete', $record))->toBeFalse();
})->with('global catalog models');

it('lets super admins change the global catalog', function (string $modelClass) {
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $record = new $modelClass;

    expect($superAdmin->can('create', $modelClass))->toBeTrue()
        ->and($superAdmin->can('update', $record))->toBeTrue()
        ->and($superAdmin->can('delete', $record))->toBeTrue()
        ->and($superAdmin->can('deleteAny', $modelClass))->toBeTrue();
})->with('global catalog models');

it('does not let the change permissions bypass the super admin restriction', function (string $modelClass) {
    $user = User::factory()->create();
    $user->givePermissionTo(Role::findByName('super_admin')->permissions);
    $record = new $modelClass;

    expect($user->can('create', $modelClass))->toBeFalse()
        ->and($user->can('update', $record))->toBeFalse()
        ->and($user->can('delete', $record))->toBeFalse();
})->with('global catalog models');

it('gives the admin preset read-only permissions on the global catalog', function () {
    $admin = Role::findByName('admin');

    expect($admin->hasPermissionTo('view_any_product'))->toBeTrue()
        ->and($admin->hasPermissionTo('view_task_type'))->toBeTrue()
        ->and($admin->hasPermissionTo('create_product'))->toBeFalse()
        ->and($admin->hasPermissionTo('update_product_brand'))->toBeFalse()
        ->and($admin->hasPermissionTo('delete_task_status'))->toBeFalse()
        ->and($admin->hasPermissionTo('create_movement_type'))->toBeTrue()
        ->and($admin->hasPermissionTo('create_inventory'))->toBeTrue();
});
