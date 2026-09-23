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

