<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\Reorder;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

function makeProductForAuthTest(string $suffix): Product
{
    $brand = ProductBrand::query()->create(['name' => "Brand {$suffix}"]);
    $model = ProductModel::query()->create(['name' => "Model {$suffix}", 'product_brand_id' => $brand->id]);
    $type = ProductType::query()->create(['name' => "Type {$suffix}"]);
    $group = ProductGroup::query()->create(['name' => "Group {$suffix}"]);

    return Product::query()->create([
        'product_group_id' => $group->id,
        'product_type_id' => $type->id,
        'product_brand_id' => $brand->id,
        'product_model_id' => $model->id,
        'name' => "Product {$suffix}",
    ]);
}

function makeInventoryForAuthTest(string $suffix, Scope $scope): Inventory
{
    $product = makeProductForAuthTest($suffix);

    return Inventory::factory()->create([
        'scope_id' => $scope->id,
        'product_id' => $product->id,
        'description' => "Inventory {$suffix}",
    ]);
}

function makeMovementForAuthTest(string $suffix, Scope $scope): Movement
{
    $location = InventoryLocation::factory()->create([
        'scope_id' => $scope->id,
        'name' => "L {$suffix}",
    ]);
    $position = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $location->id,
        'name' => "P {$suffix}",
    ]);

    $movementType = MovementType::factory()->create([
        'scope_id' => $scope->id,
        'name' => "Move {$suffix}",
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    return Movement::factory()->create([
        'scope_id' => $scope->id,
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_position_id' => $position->id,
        'to_inventory_position_id' => $position->id,
        'description' => "Movement {$suffix}",
    ]);
}

function makeTaskForAuthTest(string $suffix, Scope $scope): Task
{
    $status = TaskStatus::query()->create([
        'name' => "Status {$suffix}",
        'color' => 'info',
        'default' => false,
        'completed' => false,
    ]);

    $type = TaskType::query()->create([
        'name' => "Type {$suffix}",
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    $owner = User::factory()->create();

    return Task::factory()->create([
        'scope_id' => $scope->id,
        'starts_at' => now(),
        'task_type_id' => $type->id,
        'task_status_id' => $status->id,
        'user_id' => $owner->id,
        'description' => "Task {$suffix}",
    ]);
}

function makeReorderForAuthTest(string $suffix, Scope $scope): Reorder
{
    $inventory = makeInventoryForAuthTest($suffix, $scope);
    $location = InventoryLocation::factory()->create([
        'scope_id' => $scope->id,
        'name' => "RL {$suffix}",
    ]);
    $position = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $location->id,
        'name' => "RP {$suffix}",
    ]);

    $stock = Stock::factory()->create([
        'scope_id' => $scope->id,
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $position->id,
        'stock' => 1,
    ]);

    return Reorder::factory()->create([
        'scope_id' => $scope->id,
        'stock_id' => $stock->id,
        'reorder_point' => 2,
        'reorder_quantity' => 5,
    ]);
}

it('forbids edit and allows view for read-only users on critical resources', function (string $resourceKey, string $viewAnyPerm, string $viewPerm) {
    $suffix = (string) str()->uuid();
    $scope = Scope::factory()->create();

    $record = match ($resourceKey) {
        'product' => makeProductForAuthTest($suffix),
        'inventory' => makeInventoryForAuthTest($suffix, $scope),
        'movement' => makeMovementForAuthTest($suffix, $scope),
        'task' => makeTaskForAuthTest($suffix, $scope),
        'inventory-location' => InventoryLocation::factory()->create(['scope_id' => $scope->id, 'name' => "Location {$suffix}"]),
        'movement-type' => MovementType::factory()->create(['scope_id' => $scope->id, 'name' => "MType {$suffix}", 'chart' => false, 'chart_color' => '#ffffff']),
        'product-brand' => ProductBrand::query()->create(['name' => "Brand {$suffix}"]),
        'reorder' => makeReorderForAuthTest($suffix, $scope),
        'task-status' => TaskStatus::query()->create(['name' => "Status {$suffix}", 'color' => 'info']),
        'task-type' => TaskType::query()->create(['name' => "Type {$suffix}", 'chart' => false, 'chart_color' => '#ffffff']),
        'user' => User::factory()->create(),
    };

    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo($viewAnyPerm);
    $user->givePermissionTo($viewPerm);
    $user->scopes()->attach($scope->id);

    $this->actingAs($user);

    $slug = $scope->slug;

    $basePath = match ($resourceKey) {
        'product' => 'products',
        'inventory' => 'inventories',
        'movement' => 'movements',
        'task' => 'tasks',
        'inventory-location' => 'inventory-locations',
        'movement-type' => 'movement-types',
        'product-brand' => 'product-brands',
        'reorder' => 'reorders',
        'task-status' => 'task-statuses',
        'task-type' => 'task-types',
        'user' => 'users',
    };

    $this->get("/{$slug}/{$basePath}/{$record->id}/view")->assertOk();
    $this->get("/{$slug}/{$basePath}/{$record->id}/edit")->assertForbidden();
})->with([
    ['product', 'view_any_product', 'view_product'],
    ['inventory', 'view_any_inventory', 'view_inventory'],
    ['movement', 'view_any_movement', 'view_movement'],
    ['task', 'view_any_task', 'view_task'],
    ['inventory-location', 'view_any_inventory_location', 'view_inventory_location'],
    ['movement-type', 'view_any_movement_type', 'view_movement_type'],
    ['product-brand', 'view_any_product_brand', 'view_product_brand'],
    ['reorder', 'view_any_reorder', 'view_reorder'],
    ['task-status', 'view_any_task_status', 'view_task_status'],
    ['task-type', 'view_any_task_type', 'view_task_type'],
    ['user', 'view_any_user', 'view_user'],
]);
