<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\ReorderOrder;
use App\Models\Scope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('merges duplicate stock rows and their references into the oldest one', function () {
    (require database_path('migrations/2026_09_22_060416_add_unique_location_index_to_stocks_table.php'))->down();

    $scope = Scope::factory()->create();
    $location = InventoryLocation::factory()->create(['scope_id' => $scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $scope->id, 'inventory_location_id' => $location->id]);
    $inventory = Inventory::factory()->create(['scope_id' => $scope->id]);
    $movement = Movement::factory()->create(['scope_id' => $scope->id]);

    $stockRow = fn (): int => DB::table('stocks')->insertGetId([
        'scope_id' => $scope->id,
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $position->id,
        'path' => 'path',
        'stock' => 0,
    ]);
    $keeperId = $stockRow();
    $duplicateId = $stockRow();

    $movementItemId = DB::table('movement_items')->insertGetId([
        'scope_id' => $scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $inventory->id,
        'incoming_stock_id' => $duplicateId,
        'stock' => 4,
    ]);

    $reorderRow = fn (int $stockId): int => DB::table('reorders')->insertGetId([
        'scope_id' => $scope->id,
        'stock_id' => $stockId,
        'reorder_point' => 1,
    ]);
    $keeperReorderId = $reorderRow($keeperId);
    $duplicateReorderId = $reorderRow($duplicateId);

    $sharedOrder = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    $duplicateOnlyOrder = ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);

    $orderItemRow = fn (int $orderId, int $stockId, int $reorderId): int => DB::table('reorder_order_items')->insertGetId([
        'scope_id' => $scope->id,
        'reorder_order_id' => $orderId,
        'stock_id' => $stockId,
        'reorder_id' => $reorderId,
        'current_stock' => 0,
        'reorder_point' => 1,
        'suggested_qty' => 1,
    ]);
    $orderItemRow($sharedOrder->id, $keeperId, $keeperReorderId);
    $sharedDuplicateItemId = $orderItemRow($sharedOrder->id, $duplicateId, $duplicateReorderId);
    $movedItemId = $orderItemRow($duplicateOnlyOrder->id, $duplicateId, $duplicateReorderId);

    (require database_path('migrations/2026_09_22_060415_merge_duplicate_stocks.php'))->up();

    expect(DB::table('stocks')->pluck('id')->all())->toBe([$keeperId])
        ->and(DB::table('stocks')->where('id', $keeperId)->value('stock'))->toEqual(4)
        ->and(DB::table('movement_items')->where('id', $movementItemId)->value('incoming_stock_id'))->toEqual($keeperId)
        ->and(DB::table('reorders')->pluck('id')->all())->toBe([$keeperReorderId])
        ->and(DB::table('reorder_order_items')->where('id', $sharedDuplicateItemId)->exists())->toBeFalse()
        ->and(DB::table('reorder_order_items')->where('id', $movedItemId)->first())
        ->stock_id->toEqual($keeperId)
        ->reorder_id->toEqual($keeperReorderId);

    (require database_path('migrations/2026_09_22_060416_add_unique_location_index_to_stocks_table.php'))->up();
});

it('prevents duplicate stock rows for the same inventory position', function () {
    $scope = Scope::factory()->create();
    $location = InventoryLocation::factory()->create(['scope_id' => $scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $scope->id, 'inventory_location_id' => $location->id]);
    $inventory = Inventory::factory()->create(['scope_id' => $scope->id]);

    $row = [
        'scope_id' => $scope->id,
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $position->id,
        'path' => 'path',
    ];

    DB::table('stocks')->insert($row);

    expect(fn () => DB::table('stocks')->insert($row))
        ->toThrow(UniqueConstraintViolationException::class);
});
