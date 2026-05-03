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
use App\Models\Stock;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

function superAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    return $user;
}

it('loads critical panel pages for a super admin', function () {
    $user = superAdminUser();

    $this->actingAs($user);

    $this->get('/')->assertOk();
    $this->get('/inventories')->assertOk();
    $this->get('/inventories/create')->assertOk();
    $this->get('/movements')->assertOk();
    $this->get('/reorder-orders')->assertOk();
    $this->get('/tasks')->assertOk();
    $this->get('/task-calendars')->assertOk();
    $this->get('/products/create')->assertOk();
    $this->get('/shield/roles')->assertOk();
});

it('loads configuration create pages for a super admin', function () {
    $user = superAdminUser();

    $this->actingAs($user);

    $createPages = [
        '/users/create',
        '/task-statuses/create',
        '/task-types/create',
        '/product-brands/create',
        '/product-models/create',
        '/product-types/create',
        '/product-groups/create',
        '/movement-types/create',
        '/inventory-locations/create',
        '/inventory-positions/create',
    ];

    foreach ($createPages as $path) {
        $response = $this->get($path);
        $status = $response->getStatusCode();

        $this->assertContains($status, [200, 403], "Unexpected status [{$status}] for [{$path}]");
    }
});

it('loads configuration edit pages for existing records', function () {
    $user = superAdminUser();

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
    $movementType = MovementType::query()->create([
        'name' => 'Movement Smoke',
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);
    $inventoryLocation = InventoryLocation::query()->create(['name' => 'Location Smoke']);
    $inventoryPosition = InventoryPosition::query()->create([
        'inventory_location_id' => $inventoryLocation->id,
        'path' => 'Smoke/Position',
        'name' => 'Position Smoke',
    ]);

    $this->actingAs($user);

    $editPages = [
        "/users/{$user->id}/edit",
        "/task-statuses/{$taskStatus->id}/edit",
        "/task-types/{$taskType->id}/edit",
        "/product-brands/{$productBrand->id}/edit",
        "/product-models/{$productModel->id}/edit",
        "/product-types/{$productType->id}/edit",
        "/product-groups/{$productGroup->id}/edit",
        "/movement-types/{$movementType->id}/edit",
        "/inventory-locations/{$inventoryLocation->id}/edit",
        "/inventory-positions/{$inventoryPosition->id}/edit",
    ];

    foreach ($editPages as $path) {
        $response = $this->get($path);
        $status = $response->getStatusCode();

        $this->assertContains($status, [200, 403], "Unexpected status [{$status}] for [{$path}]");
    }
});

it('loads view pages for existing records', function () {
    $user = superAdminUser();

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
    $movementType = MovementType::query()->create([
        'name' => 'Movement View Smoke',
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);
    $inventoryLocation = InventoryLocation::query()->create(['name' => 'Location View Smoke']);
    $inventoryPosition = InventoryPosition::query()->create([
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
    $inventory = Inventory::query()->create([
        'product_id' => $product->id,
        'description' => 'Inventory View Smoke',
    ]);
    $stock = Stock::query()->create([
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $inventoryPosition->id,
        'stock' => 5,
    ]);
    $reorder = Reorder::query()->create([
        'stock_id' => $stock->id,
        'reorder_point' => 2,
        'reorder_quantity' => 10,
    ]);
    $movement = Movement::query()->create([
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_position_id' => $inventoryPosition->id,
        'to_inventory_position_id' => $inventoryPosition->id,
        'description' => 'Movement View Smoke',
    ]);
    $task = Task::query()->create([
        'starts_at' => now(),
        'task_type_id' => $taskType->id,
        'task_status_id' => $taskStatus->id,
        'user_id' => $user->id,
        'description' => 'Task View Smoke',
    ]);

    $this->actingAs($user);

    $viewPages = [
        "/users/{$user->id}/view",
        "/task-statuses/{$taskStatus->id}/view",
        "/task-types/{$taskType->id}/view",
        "/product-brands/{$productBrand->id}/view",
        "/movement-types/{$movementType->id}/view",
        "/inventory-locations/{$inventoryLocation->id}/view",
        "/inventories/{$inventory->id}/view",
        "/movements/{$movement->id}/view",
        "/products/{$product->id}/view",
        "/reorders/{$reorder->id}/view",
        "/tasks/{$task->id}/view",
    ];

    foreach ($viewPages as $path) {
        $response = $this->get($path);
        $status = $response->getStatusCode();

        $this->assertContains($status, [200, 403], "Unexpected status [{$status}] for [{$path}]");
    }
});
