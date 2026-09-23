<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\StockResource;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeMovementDomain(): array
{
    $suffix = (string) str()->uuid();
    $scope = Scope::factory()->create();

    $brand = ProductBrand::query()->create(['name' => "Brand {$suffix}"]);
    $model = ProductModel::query()->create(['name' => "Model {$suffix}", 'product_brand_id' => $brand->id]);
    $type = ProductType::query()->create(['name' => "Type {$suffix}"]);
    $group = ProductGroup::query()->create(['name' => "Group {$suffix}"]);

    $product = Product::query()->create([
        'product_group_id' => $group->id,
        'product_type_id' => $type->id,
        'product_brand_id' => $brand->id,
        'product_model_id' => $model->id,
        'name' => "Product {$suffix}",
    ]);

    $locationA = InventoryLocation::factory()->create(['scope_id' => $scope->id, 'name' => "A {$suffix}"]);
    $locationB = InventoryLocation::factory()->create(['scope_id' => $scope->id, 'name' => "B {$suffix}"]);

    $positionA = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $locationA->id,
        'name' => "P-A {$suffix}",
    ]);

    $positionB = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $locationB->id,
        'name' => "P-B {$suffix}",
    ]);

    $inventory = Inventory::factory()->create([
        'scope_id' => $scope->id,
        'product_id' => $product->id,
    ]);

    $movementType = MovementType::factory()->create([
        'scope_id' => $scope->id,
        'name' => "Move {$suffix}",
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    return compact('scope', 'inventory', 'locationA', 'locationB', 'positionA', 'positionB', 'movementType');
}

it('recomputes stock totals when movement item is created updated and deleted', function () {
    [
        'scope' => $scope,
        'inventory' => $inventory,
        'locationA' => $locationA,
        'locationB' => $locationB,
        'positionA' => $positionA,
        'positionB' => $positionB,
        'movementType' => $movementType,
    ] = makeMovementDomain();

    $load = Movement::factory()->create([
        'scope_id' => $scope->id,
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'to_inventory_location_id' => $locationA->id,
        'to_inventory_position_id' => $positionA->id,
        'description' => 'Load test',
    ]);
    MovementItem::query()->create([
        'scope_id' => $load->scope_id,
        'movement_id' => $load->id,
        'inventory_id' => $inventory->id,
        'stock' => 10,
    ]);

    $movement = Movement::factory()->create([
        'scope_id' => $scope->id,
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_location_id' => $locationA->id,
        'from_inventory_position_id' => $positionA->id,
        'to_inventory_location_id' => $locationB->id,
        'to_inventory_position_id' => $positionB->id,
        'description' => 'Transfer test',
    ]);

    $item = MovementItem::query()->create([
        'scope_id' => $movement->scope_id,
        'movement_id' => $movement->id,
        'inventory_id' => $inventory->id,
        'stock' => 5,
    ]);

    $incoming = Stock::find($item->incoming_stock_id);
    $outgoing = Stock::find($item->outcoming_stock_id);

    expect($incoming)->not->toBeNull()
        ->and($outgoing)->not->toBeNull();

    $incoming->refresh();
    $outgoing->refresh();

    expect((int) $incoming->stock)->toBe(5)
        ->and((int) $outgoing->stock)->toBe(5);

    $item->update(['stock' => 8]);

    $incoming->refresh();
    $outgoing->refresh();

    expect((int) $incoming->stock)->toBe(8)
        ->and((int) $outgoing->stock)->toBe(2);

    $item->delete();

    $incoming->refresh();
    $outgoing->refresh();

    expect((int) $incoming->stock)->toBe(0)
        ->and((int) $outgoing->stock)->toBe(10);
});

it('repairs stored stock that does not match the movement history instead of aborting the db check', function () {
    [
        'scope' => $scope,
        'inventory' => $inventory,
        'locationA' => $locationA,
        'locationB' => $locationB,
        'positionA' => $positionA,
        'positionB' => $positionB,
        'movementType' => $movementType,
    ] = makeMovementDomain();

    $load = Movement::factory()->create([
        'scope_id' => $scope->id,
        'movement_type_id' => $movementType->id,
        'to_inventory_location_id' => $locationA->id,
        'to_inventory_position_id' => $positionA->id,
    ]);
    $loadItem = MovementItem::query()->create([
        'scope_id' => $scope->id,
        'movement_id' => $load->id,
        'inventory_id' => $inventory->id,
        'stock' => 10,
    ]);

    $transfer = Movement::factory()->create([
        'scope_id' => $scope->id,
        'movement_type_id' => $movementType->id,
        'from_inventory_location_id' => $locationA->id,
        'from_inventory_position_id' => $positionA->id,
        'to_inventory_location_id' => $locationB->id,
        'to_inventory_position_id' => $positionB->id,
    ]);
    $transferItem = MovementItem::query()->create([
        'scope_id' => $scope->id,
        'movement_id' => $transfer->id,
        'inventory_id' => $inventory->id,
        'stock' => 5,
    ]);

    DB::table('movement_items')->where('id', $loadItem->id)->update(['stock' => 2]);

    Inventory::dbCheck();

    expect(Stock::find($transferItem->outcoming_stock_id)->stock)->toBe(-3)
        ->and(Stock::find($transferItem->incoming_stock_id)->stock)->toBe(5);
});

it('refreshes the product data copied in every scope when a product changes inside the panel', function () {
    [
        'scope' => $scope,
        'inventory' => $inventory,
        'locationA' => $locationA,
        'positionA' => $positionA,
        'movementType' => $movementType,
    ] = makeMovementDomain();

    $load = Movement::factory()->create([
        'scope_id' => $scope->id,
        'movement_type_id' => $movementType->id,
        'to_inventory_location_id' => $locationA->id,
        'to_inventory_position_id' => $positionA->id,
    ]);
    $item = MovementItem::query()->create([
        'scope_id' => $scope->id,
        'movement_id' => $load->id,
        'inventory_id' => $inventory->id,
        'stock' => 1,
    ]);

    $otherScope = Scope::factory()->create();
    $otherInventory = Inventory::factory()->create(['scope_id' => $otherScope->id, 'product_id' => $inventory->product_id]);
    $otherStock = Stock::factory()->create(['scope_id' => $otherScope->id, 'inventory_id' => $otherInventory->id]);

    $this->actingAs(User::factory()->create());
    activateFilamentTenant($scope, [InventoryResource::class, StockResource::class, MovementItemResource::class]);

    $newGroup = ProductGroup::query()->create(['name' => 'New group']);
    $inventory->product->update(['name' => 'Renamed product', 'product_group_id' => $newGroup->id]);

    $stock = Stock::withoutGlobalScopes()->find($item->incoming_stock_id);
    $otherStock = Stock::withoutGlobalScopes()->find($otherStock->id);

    expect($stock->product_group_id)->toBe($newGroup->id)
        ->and($stock->inventory_summary)->toContain('Renamed product')
        ->and(MovementItem::withoutGlobalScopes()->find($item->id)->inventory_summary)->toContain('Renamed product')
        ->and($otherStock->product_group_id)->toBe($newGroup->id)
        ->and($otherStock->inventory_summary)->toContain('Renamed product')
        ->and(Inventory::withoutGlobalScopes()->find($otherInventory->id)->summary)->toContain('Renamed product');
});

it('moves the stock when the destination of an existing movement changes', function () {
    [
        'scope' => $scope,
        'inventory' => $inventory,
        'positionA' => $positionA,
        'positionB' => $positionB,
        'movementType' => $movementType,
    ] = makeMovementDomain();

    $load = Movement::factory()->create([
        'scope_id' => $scope->id,
        'movement_type_id' => $movementType->id,
        'to_inventory_position_id' => $positionA->id,
    ]);
    $item = MovementItem::query()->create([
        'scope_id' => $scope->id,
        'movement_id' => $load->id,
        'inventory_id' => $inventory->id,
        'stock' => 4,
    ]);
    $stockAtA = Stock::query()->find($item->incoming_stock_id);

    $load->update(['to_inventory_position_id' => $positionB->id]);

    $item->refresh();

    expect($stockAtA->fresh()->stock)->toBe(0)
        ->and(Stock::query()->find($item->incoming_stock_id))
        ->inventory_position_id->toBe($positionB->id)
        ->stock->toBe(4)
        ->and($load->fresh()->to_inventory_location_id)->toBe($positionB->inventory_location_id);
});
