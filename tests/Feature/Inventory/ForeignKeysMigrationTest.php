<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\Scope;
use App\Models\Task;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('deletes orphan inventories and stocks before adding the foreign keys', function () {
    $migration = require database_path('migrations/2026_09_23_063627_add_product_and_inventory_foreign_keys_to_inventories_and_stocks.php');
    $migration->down();

    $scope = Scope::factory()->create();
    $location = InventoryLocation::factory()->create(['scope_id' => $scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $scope->id, 'inventory_location_id' => $location->id]);
    $movement = Movement::factory()->create(['scope_id' => $scope->id]);
    $validInventory = Inventory::factory()->create(['scope_id' => $scope->id]);
    $orphanInventory = Inventory::factory()->create(['scope_id' => $scope->id]);
    $task = Task::factory()->create(['scope_id' => $scope->id]);
    $orphanInventory->tasks()->attach($task);

    $stockRow = fn (int $inventoryId, array $attributes = []): int => DB::table('stocks')->insertGetId([
        'scope_id' => $scope->id,
        'inventory_id' => $inventoryId,
        'inventory_position_id' => $position->id,
        'path' => 'path',
        'stock' => 1,
        ...$attributes,
    ]);
    $validStockId = $stockRow($validInventory->id, ['product_brand_id' => 999999]);
    $orphanInventoryStockId = $stockRow($orphanInventory->id);

    DB::statement('PRAGMA foreign_keys = OFF');
    $missingInventoryStockId = $stockRow(999999);
    DB::table('inventories')->where('id', $orphanInventory->id)->update(['product_id' => 999999]);
    DB::statement('PRAGMA foreign_keys = ON');

    DB::table('movement_items')->insert([
        'scope_id' => $scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $orphanInventory->id,
        'incoming_stock_id' => $orphanInventoryStockId,
        'stock' => 1,
    ]);
    DB::table('reorders')->insert(['scope_id' => $scope->id, 'stock_id' => $missingInventoryStockId, 'reorder_point' => 1]);

    $migration->up();

    expect(DB::table('inventories')->pluck('id')->all())->toBe([$validInventory->id])
        ->and(DB::table('stocks')->pluck('id')->all())->toBe([$validStockId])
        ->and(DB::table('stocks')->where('id', $validStockId)->value('product_brand_id'))->toBeNull()
        ->and(DB::table('movement_items')->count())->toBe(0)
        ->and(DB::table('task_inventory')->count())->toBe(0)
        ->and(DB::table('reorders')->count())->toBe(0)
        ->and(Task::query()->whereKey($task->id)->exists())->toBeTrue();

    expect(fn () => DB::table('inventories')->where('id', $validInventory->id)->update(['product_id' => 999999]))
        ->toThrow(QueryException::class);
})->skip(fn (): bool => Schema::getConnection()->getDriverName() !== 'sqlite', 'Disables the foreign keys with a SQLite pragma.');
