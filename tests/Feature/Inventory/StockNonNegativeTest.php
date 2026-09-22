<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\Scope;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->scope = Scope::factory()->create();
    $this->inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $this->movementType = MovementType::factory()->create(['scope_id' => $this->scope->id, 'chart' => false, 'chart_color' => '#ffffff']);

    $makePosition = function (): InventoryPosition {
        $location = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);

        return InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $location->id]);
    };
    $this->positionA = $makePosition();
    $this->positionB = $makePosition();
    $this->positionC = $makePosition();

    $this->load = nonNegativeMovement($this, from: null, to: $this->positionA);
    $this->loadItem = nonNegativeItem($this, $this->load, 5);
});

function nonNegativeMovement(object $test, ?InventoryPosition $from, ?InventoryPosition $to): Movement
{
    return Movement::factory()->create([
        'scope_id' => $test->scope->id,
        'date' => now(),
        'movement_type_id' => $test->movementType->id,
        'from_inventory_location_id' => $from?->inventory_location_id,
        'from_inventory_position_id' => $from?->id,
        'to_inventory_location_id' => $to?->inventory_location_id,
        'to_inventory_position_id' => $to?->id,
    ]);
}

function nonNegativeItem(object $test, Movement $movement, int $quantity): MovementItem
{
    return MovementItem::query()->create([
        'scope_id' => $test->scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $test->inventory->id,
        'stock' => $quantity,
    ]);
}

function stockQuantityAt(object $test, InventoryPosition $position): ?int
{
    $stock = Stock::query()
        ->where('inventory_id', $test->inventory->id)
        ->where('inventory_position_id', $position->id)
        ->value('stock');

    return $stock === null ? null : (int) $stock;
}

it('allows withdrawing up to the available quantity', function () {
    nonNegativeItem($this, nonNegativeMovement($this, $this->positionA, $this->positionB), 5);

    expect(stockQuantityAt($this, $this->positionA))->toBe(0)
        ->and(stockQuantityAt($this, $this->positionB))->toBe(5);
});

it('rejects a movement item that withdraws more than available', function () {
    $transfer = nonNegativeMovement($this, $this->positionA, $this->positionB);

    expect(fn () => nonNegativeItem($this, $transfer, 6))->toThrow(ValidationException::class);

    expect($transfer->movement_items()->count())->toBe(0)
        ->and(stockQuantityAt($this, $this->positionA))->toBe(5)
        ->and(stockQuantityAt($this, $this->positionB))->not->toBeGreaterThan(0);
});

it('rejects increasing a withdrawal beyond the available quantity', function () {
    $item = nonNegativeItem($this, nonNegativeMovement($this, $this->positionA, $this->positionB), 3);

    expect(fn () => $item->update(['stock' => 6]))->toThrow(ValidationException::class);

    expect((int) $item->fresh()->stock)->toBe(3)
        ->and(stockQuantityAt($this, $this->positionA))->toBe(2);
});

it('rejects reducing or deleting a load whose quantity was already withdrawn', function () {
    nonNegativeItem($this, nonNegativeMovement($this, $this->positionA, $this->positionB), 4);

    expect(fn () => $this->loadItem->update(['stock' => 3]))->toThrow(ValidationException::class)
        ->and(fn () => $this->loadItem->fresh()->delete())->toThrow(ValidationException::class)
        ->and(fn () => $this->load->fresh()->delete())->toThrow(ValidationException::class);

    expect((int) $this->loadItem->fresh()->stock)->toBe(5)
        ->and(Movement::query()->whereKey($this->load->id)->exists())->toBeTrue()
        ->and(stockQuantityAt($this, $this->positionA))->toBe(1);
});

it('rejects moving the origin of a movement to a position without enough stock', function () {
    $transfer = nonNegativeMovement($this, $this->positionA, $this->positionB);
    nonNegativeItem($this, $transfer, 5);

    expect(fn () => $transfer->update([
        'from_inventory_location_id' => $this->positionC->inventory_location_id,
        'from_inventory_position_id' => $this->positionC->id,
    ]))->toThrow(ValidationException::class);

    expect($transfer->fresh()->from_inventory_position_id)->toBe($this->positionA->id)
        ->and(stockQuantityAt($this, $this->positionA))->toBe(0)
        ->and(stockQuantityAt($this, $this->positionB))->toBe(5);
});

it('still allows loading a stock that is already negative', function () {
    $stock = Stock::query()->where('inventory_position_id', $this->positionA->id)->first();
    DB::table('movement_items')->insert([
        'scope_id' => $this->scope->id,
        'movement_id' => nonNegativeMovement($this, $this->positionA, null)->id,
        'inventory_id' => $this->inventory->id,
        'outcoming_stock_id' => $stock->id,
        'stock' => 8,
    ]);
    $stock->updateStockByMovementItems(allowNegative: true);

    nonNegativeItem($this, nonNegativeMovement($this, null, $this->positionA), 1);

    expect(stockQuantityAt($this, $this->positionA))->toBe(-2);
});
