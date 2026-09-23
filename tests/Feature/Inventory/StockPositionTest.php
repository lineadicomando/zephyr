<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\Scope;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->scope = Scope::factory()->create();
    $this->location = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $this->inventory = Inventory::factory()->create(['scope_id' => $this->scope->id]);
});

it('uses the default position of a movement location given without position', function () {
    $movement = Movement::factory()->create([
        'scope_id' => $this->scope->id,
        'to_inventory_location_id' => $this->location->id,
        'to_inventory_position_id' => null,
    ]);

    $item = MovementItem::query()->create([
        'scope_id' => $this->scope->id,
        'movement_id' => $movement->id,
        'inventory_id' => $this->inventory->id,
        'stock' => 2,
    ]);

    expect($movement->to_inventory_position_id)->toBe($this->location->defaultPosition()->id)
        ->and(Stock::query()->find($item->incoming_stock_id))
        ->inventory_position_id->toBe($this->location->defaultPosition()->id)
        ->stock->toBe(2);
});

it('always takes the movement location from its position', function () {
    $otherLocation = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $otherLocation->id]);

    $movement = Movement::factory()->create(['scope_id' => $this->scope->id]);
    $movement->update(['to_inventory_position_id' => $position->id, 'to_inventory_location_id' => $this->location->id]);

    expect($movement->to_inventory_location_id)->toBe($otherLocation->id);
});

it('moves the movement items of stocks without position to the stock of their position', function () {
    $migration = require database_path('migrations/2026_09_23_063856_give_every_stock_a_position.php');
    $migration->down();

    $position = InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $this->location->id]);
    $movementAt = fn (?int $positionId): int => DB::table('movements')->insertGetId([
        'scope_id' => $this->scope->id,
        'date' => now(),
        'movement_type_id' => Movement::factory()->create(['scope_id' => $this->scope->id])->movement_type_id,
        'to_inventory_location_id' => $this->location->id,
        'to_inventory_position_id' => $positionId,
        'description' => 'load',
    ]);
    $withoutPosition = DB::table('stocks')->insertGetId([
        'scope_id' => $this->scope->id,
        'inventory_id' => $this->inventory->id,
        'path' => 'path',
        'stock' => 5,
    ]);
    DB::table('reorders')->insert(['scope_id' => $this->scope->id, 'stock_id' => $withoutPosition, 'reorder_point' => 1]);
    foreach ([$movementAt(null) => 2, $movementAt($position->id) => 3] as $movementId => $quantity) {
        DB::table('movement_items')->insert([
            'scope_id' => $this->scope->id,
            'movement_id' => $movementId,
            'inventory_id' => $this->inventory->id,
            'incoming_stock_id' => $withoutPosition,
            'stock' => $quantity,
        ]);
    }

    $migration->up();

    $defaultPosition = $this->location->defaultPosition();

    expect(Stock::query()->whereNull('inventory_position_id')->exists())->toBeFalse()
        ->and(DB::table('movements')->whereNull('to_inventory_position_id')->exists())->toBeFalse()
        ->and(Stock::query()->where('inventory_position_id', $defaultPosition->id)->sole()->stock)->toBe(2)
        ->and(Stock::query()->where('inventory_position_id', $position->id)->sole()->stock)->toBe(3)
        ->and(DB::table('reorders')->count())->toBe(0);
});
