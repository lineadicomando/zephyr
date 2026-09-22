<?php

use App\Filament\Resources\StockResource;
use App\Filament\Resources\StockResource\Pages\EditStock;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);

    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->user->scopes()->attach($this->scope);
    $this->actingAs($this->user);

    activateFilamentTenant($this->scope, [StockResource::class]);

    $location = InventoryLocation::factory()->create(['scope_id' => $this->scope->id]);
    $position = InventoryPosition::factory()->create(['scope_id' => $this->scope->id, 'inventory_location_id' => $location->id]);

    $this->stock = Stock::factory()->create([
        'scope_id' => $this->scope->id,
        'inventory_id' => Inventory::factory()->create(['scope_id' => $this->scope->id])->id,
        'inventory_position_id' => $position->id,
    ]);

    $this->reorderId = DB::table('reorders')->insertGetId([
        'scope_id' => $this->scope->id,
        'stock_id' => $this->stock->id,
        'reorder_point' => 1,
    ]);
});

it('deletes an unused stock together with its reorder rule', function () {
    Livewire::test(EditStock::class, ['record' => $this->stock->getRouteKey()])
        ->assertActionVisible(DeleteAction::class)
        ->callAction(DeleteAction::class);

    expect(Stock::query()->whereKey($this->stock->id)->exists())->toBeFalse()
        ->and(DB::table('reorders')->where('id', $this->reorderId)->exists())->toBeFalse();
});

it('keeps a stock referenced by movement items', function (string $column) {
    $itemId = DB::table('movement_items')->insertGetId([
        'scope_id' => $this->scope->id,
        'movement_id' => Movement::factory()->create(['scope_id' => $this->scope->id])->id,
        'inventory_id' => $this->stock->inventory_id,
        $column => $this->stock->id,
        'stock' => 1,
    ]);

    Livewire::test(EditStock::class, ['record' => $this->stock->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    expect($this->stock->delete())->toBeFalse()
        ->and(DB::table('movement_items')->where('id', $itemId)->value($column))->toEqual($this->stock->id);
})->with(['incoming_stock_id', 'outcoming_stock_id']);

it('keeps a stock referenced by reorder order items', function () {
    $order = ReorderOrder::query()->create(['scope_id' => $this->scope->id, 'status' => ReorderOrder::STATUS_DRAFT]);
    DB::table('reorder_order_items')->insert([
        'scope_id' => $this->scope->id,
        'reorder_order_id' => $order->id,
        'stock_id' => $this->stock->id,
        'reorder_id' => $this->reorderId,
        'current_stock' => 0,
        'reorder_point' => 1,
        'suggested_qty' => 1,
    ]);

    Livewire::test(EditStock::class, ['record' => $this->stock->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    expect($this->stock->delete())->toBeFalse()
        ->and($order->items()->count())->toBe(1);
});
