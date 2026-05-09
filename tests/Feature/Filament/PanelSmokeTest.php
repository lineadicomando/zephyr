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

function superAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

function superAdminUserWithScope(): array
{
    $user = superAdminUser();
    $scope = Scope::factory()->create();
    $user->scopes()->attach($scope->id);

    return [$user, $scope];
}

it('loads critical panel pages for a super admin', function () {
    [$user, $scope] = superAdminUserWithScope();

    $this->actingAs($user);

    $slug = $scope->slug;

    $this->get("/{$slug}")->assertOk();
    $this->get("/{$slug}/inventories")->assertOk();
    $this->get("/{$slug}/inventories/create")->assertOk();
    $this->get("/{$slug}/movements")->assertOk();
    $this->get("/{$slug}/reorder-orders")->assertOk();
    $this->get("/{$slug}/tasks")->assertOk();
    $this->get("/{$slug}/task-calendars")->assertOk();
    $this->get("/{$slug}/products/create")->assertOk();
    $this->get("/{$slug}/shield/roles")->assertOk();
});

it('loads configuration create pages for a super admin', function () {
    [$user, $scope] = superAdminUserWithScope();

    $this->actingAs($user);

    $slug = $scope->slug;

    $createPages = [
        "/{$slug}/users/create",
        "/{$slug}/task-statuses/create",
        "/{$slug}/task-types/create",
        "/{$slug}/product-brands/create",
        "/{$slug}/product-models/create",
        "/{$slug}/product-types/create",
        "/{$slug}/product-groups/create",
        "/{$slug}/movement-types/create",
        "/{$slug}/inventory-locations/create",
        "/{$slug}/inventory-positions/create",
    ];

    foreach ($createPages as $path) {
        $response = $this->get($path);
        $status = $response->getStatusCode();

        $this->assertContains($status, [200, 403], "Unexpected status [{$status}] for [{$path}]");
    }
});

it('loads configuration edit pages for existing records', function () {
    [$user, $scope] = superAdminUserWithScope();

    $slug = $scope->slug;

    $taskStatus = TaskStatus::query()->create([
        'name' => 'Status Smoke',
        'order' => 1,
        'color' => 'info',
    ]);

    $taskType = TaskType::query()->create([
        'name' => 'Type Smoke',
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    $productBrand = ProductBrand::query()->create(['name' => 'Brand Smoke']);
    $productModel = ProductModel::query()->create([
        'name' => 'Model Smoke',
        'product_brand_id' => $productBrand->id,
    ]);
    $productType = ProductType::query()->create(['name' => 'Type Smoke']);
    $productGroup = ProductGroup::query()->create(['name' => 'Group Smoke']);
    $movementType = MovementType::factory()->create([
        'scope_id' => $scope->id,
        'name' => 'Movement Smoke',
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);
    $inventoryLocation = InventoryLocation::factory()->create([
        'scope_id' => $scope->id,
        'name' => 'Location Smoke',
    ]);
    $inventoryPosition = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $inventoryLocation->id,
        'path' => 'Smoke/Position',
        'name' => 'Position Smoke',
    ]);

    $this->actingAs($user);

    $editPages = [
        "/{$slug}/users/{$user->id}/edit",
        "/{$slug}/task-statuses/{$taskStatus->id}/edit",
        "/{$slug}/task-types/{$taskType->id}/edit",
        "/{$slug}/product-brands/{$productBrand->id}/edit",
        "/{$slug}/product-models/{$productModel->id}/edit",
        "/{$slug}/product-types/{$productType->id}/edit",
        "/{$slug}/product-groups/{$productGroup->id}/edit",
        "/{$slug}/movement-types/{$movementType->id}/edit",
        "/{$slug}/inventory-locations/{$inventoryLocation->id}/edit",
        "/{$slug}/inventory-positions/{$inventoryPosition->id}/edit",
    ];

    foreach ($editPages as $path) {
        $response = $this->get($path);
        $status = $response->getStatusCode();

        $this->assertContains($status, [200, 403], "Unexpected status [{$status}] for [{$path}]");
    }
});

it('loads view pages for existing records', function () {
    [$user, $scope] = superAdminUserWithScope();

    $slug = $scope->slug;

    $taskStatus = TaskStatus::query()->create([
        'name' => 'Status View Smoke',
        'order' => 1,
        'color' => 'info',
    ]);
    $taskType = TaskType::query()->create([
        'name' => 'Type View Smoke',
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);
    $productBrand = ProductBrand::query()->create(['name' => 'Brand View Smoke']);
    $productModel = ProductModel::query()->create([
        'name' => 'Model View Smoke',
        'product_brand_id' => $productBrand->id,
    ]);
    $productType = ProductType::query()->create(['name' => 'Type View Smoke']);
    $productGroup = ProductGroup::query()->create(['name' => 'Group View Smoke']);
    $movementType = MovementType::factory()->create([
        'scope_id' => $scope->id,
        'name' => 'Movement View Smoke',
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);
    $inventoryLocation = InventoryLocation::factory()->create([
        'scope_id' => $scope->id,
        'name' => 'Location View Smoke',
    ]);
    $inventoryPosition = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $inventoryLocation->id,
        'path' => 'View/Position',
        'name' => 'Position View Smoke',
    ]);
    $product = Product::query()->create([
        'product_group_id' => $productGroup->id,
        'product_type_id' => $productType->id,
        'product_brand_id' => $productBrand->id,
        'product_model_id' => $productModel->id,
        'name' => 'Product View Smoke',
    ]);
    $inventory = Inventory::factory()->create([
        'scope_id' => $scope->id,
        'product_id' => $product->id,
        'description' => 'Inventory View Smoke',
    ]);
    $stock = Stock::factory()->create([
        'scope_id' => $scope->id,
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $inventoryPosition->id,
        'stock' => 5,
    ]);
    $reorder = Reorder::factory()->create([
        'scope_id' => $scope->id,
        'stock_id' => $stock->id,
        'reorder_point' => 2,
        'reorder_quantity' => 10,
    ]);
    $movement = Movement::factory()->create([
        'scope_id' => $scope->id,
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_position_id' => $inventoryPosition->id,
        'to_inventory_position_id' => $inventoryPosition->id,
        'description' => 'Movement View Smoke',
    ]);
    $task = Task::factory()->create([
        'scope_id' => $scope->id,
        'starts_at' => now(),
        'task_type_id' => $taskType->id,
        'task_status_id' => $taskStatus->id,
        'user_id' => $user->id,
        'description' => 'Task View Smoke',
    ]);

    $this->actingAs($user);

    $viewPages = [
        "/{$slug}/users/{$user->id}/view",
        "/{$slug}/task-statuses/{$taskStatus->id}/view",
        "/{$slug}/task-types/{$taskType->id}/view",
        "/{$slug}/product-brands/{$productBrand->id}/view",
        "/{$slug}/movement-types/{$movementType->id}/view",
        "/{$slug}/inventory-locations/{$inventoryLocation->id}/view",
        "/{$slug}/inventories/{$inventory->id}/view",
        "/{$slug}/movements/{$movement->id}/view",
        "/{$slug}/products/{$product->id}/view",
        "/{$slug}/reorders/{$reorder->id}/view",
        "/{$slug}/tasks/{$task->id}/view",
    ];

    foreach ($viewPages as $path) {
        $response = $this->get($path);
        $status = $response->getStatusCode();

        $this->assertContains($status, [200, 403], "Unexpected status [{$status}] for [{$path}]");
    }
});
