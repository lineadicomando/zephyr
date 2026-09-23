<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\Product;
use App\Models\Scope;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->scope = Scope::factory()->create();
    $this->location = InventoryLocation::factory()->create(['scope_id' => $this->scope->id, 'name' => 'Warehouse']);
});

/**
 * Number of queries run by $callback.
 */
function queriesRunBy(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

/**
 * An inventory with $count stocks, each with a movement item.
 */
function inventoryWithStocks(object $test, int $count): Inventory
{
    $inventory = Inventory::factory()->create(['scope_id' => $test->scope->id]);
    $movement = Movement::factory()->create(['scope_id' => $test->scope->id]);

    foreach (range(1, $count) as $_) {
        $position = InventoryPosition::factory()->create(['scope_id' => $test->scope->id, 'inventory_location_id' => $test->location->id]);
        $stock = Stock::factory()->create(['scope_id' => $test->scope->id, 'inventory_id' => $inventory->id, 'inventory_position_id' => $position->id, 'stock' => 3]);
        DB::table('movement_items')->insert([
            'scope_id' => $test->scope->id,
            'movement_id' => $movement->id,
            'inventory_id' => $inventory->id,
            'incoming_stock_id' => $stock->id,
            'stock' => 3,
        ]);
    }

    return $inventory->fresh();
}

it('copies the new product and summary of an inventory to its stocks and movement items', function () {
    $inventory = inventoryWithStocks($this, 2);
    $product = Product::factory()->create(['name' => 'Replacement']);

    $inventory->update(['product_id' => $product->id]);

    expect(Stock::query()->where('inventory_id', $inventory->id)->get())
        ->each(fn ($stock) => $stock
            ->product_id->toBe($product->id)
            ->product_type_id->toBe($product->product_type_id)
            ->inventory_summary->toContain('Replacement'))
        ->and(DB::table('movement_items')->where('inventory_id', $inventory->id)->pluck('inventory_summary')->unique()->all())
        ->toBe([$inventory->fresh()->summary]);
});

it('saves an inventory with the same number of queries whatever its stocks', function () {
    $few = inventoryWithStocks($this, 1);
    $many = inventoryWithStocks($this, 6);

    expect(queriesRunBy(fn () => $many->update(['description' => 'changed'])))
        ->toBe(queriesRunBy(fn () => $few->update(['description' => 'changed'])));
});

it('copies a renamed position to the path of its stocks with a bulk update', function () {
    $inventory = inventoryWithStocks($this, 3);
    $position = InventoryPosition::query()->find(Stock::query()->where('inventory_id', $inventory->id)->value('inventory_position_id'));

    $queries = queriesRunBy(fn () => $position->update(['name' => 'Shelf 9']));

    expect(Stock::query()->where('inventory_position_id', $position->id)->sole()->path)->toBe('Warehouse \ Shelf 9: 3')
        ->and($queries)->toBeLessThan(6);
});

it('computes whether movement items are the last of their inventory in the table query', function () {
    $inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $movement = Movement::factory()->create(['scope_id' => $this->scope->id]);
    $itemIds = collect(range(1, 3))->map(fn (): int => DB::table('movement_items')->insertGetId([
        'scope_id' => $this->scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $inventory->id,
        'stock' => 1,
    ]));

    $items = MovementItem::query()->withIsLast()->orderBy('id')->get();

    expect(queriesRunBy(fn () => $items->map->isLast()->all()))->toBe(0)
        ->and($items->map->isLast()->all())->toBe([false, false, true])
        ->and(MovementItem::query()->find($itemIds->last())->isLast())->toBeTrue();
});
