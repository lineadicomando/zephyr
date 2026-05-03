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
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Reorder;
use App\Models\Stock;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
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

function makeInventoryForAuthTest(string $suffix): Inventory
{
    $product = makeProductForAuthTest($suffix);

    return Inventory::query()->create([
        'product_id' => $product->id,
        'description' => "Inventory {$suffix}",
    ]);
}

function makeMovementForAuthTest(string $suffix): Movement
{
    $location = InventoryLocation::query()->create(['name' => "L {$suffix}"]);
    $position = InventoryPosition::query()->create([
        'inventory_location_id' => $location->id,
        'name' => "P {$suffix}",
    ]);

    $movementType = MovementType::query()->create([
        'name' => "Move {$suffix}",
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    return Movement::query()->create([
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_position_id' => $position->id,
        'to_inventory_position_id' => $position->id,
        'description' => "Movement {$suffix}",
    ]);
}

function makeTaskForAuthTest(string $suffix): Task
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

    return Task::query()->create([
        'starts_at' => now(),
        'task_type_id' => $type->id,
        'task_status_id' => $status->id,
        'user_id' => $owner->id,
        'description' => "Task {$suffix}",
    ]);
}

function makeReorderForAuthTest(string $suffix): Reorder
{
    $inventory = makeInventoryForAuthTest($suffix);
    $location = InventoryLocation::query()->create(['name' => "RL {$suffix}"]);
    $position = InventoryPosition::query()->create([
        'inventory_location_id' => $location->id,
        'name' => "RP {$suffix}",
    ]);

    $stock = Stock::query()->create([
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $position->id,
        'stock' => 1,
    ]);

    return Reorder::query()->create([
        'stock_id' => $stock->id,
        'reorder_point' => 2,
        'reorder_quantity' => 5,
    ]);
}

it('forbids edit and allows view for read-only users on critical resources', function (string $resourceKey, string $viewAnyPerm, string $viewPerm) {
    $suffix = (string) str()->uuid();

    $record = match ($resourceKey) {
        'product' => makeProductForAuthTest($suffix),
        'inventory' => makeInventoryForAuthTest($suffix),
        'movement' => makeMovementForAuthTest($suffix),
        'task' => makeTaskForAuthTest($suffix),
        'inventory-location' => InventoryLocation::query()->create(['name' => "Location {$suffix}"]),
        'movement-type' => MovementType::query()->create(['name' => "MType {$suffix}", 'chart' => false, 'chart_color' => '#ffffff']),
        'product-brand' => ProductBrand::query()->create(['name' => "Brand {$suffix}"]),
        'reorder' => makeReorderForAuthTest($suffix),
        'task-status' => TaskStatus::query()->create(['name' => "Status {$suffix}", 'color' => 'info']),
        'task-type' => TaskType::query()->create(['name' => "Type {$suffix}", 'chart' => false, 'chart_color' => '#ffffff']),
        'user' => User::factory()->create(),
    };

    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo($viewAnyPerm);
    $user->givePermissionTo($viewPerm);

    $this->actingAs($user);

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

    $this->get("/{$basePath}/{$record->id}/view")->assertOk();
    $this->get("/{$basePath}/{$record->id}/edit")->assertForbidden();
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
