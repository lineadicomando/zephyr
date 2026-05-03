<?php

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
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeMovementDomain(): array
{
    $suffix = (string) str()->uuid();

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

    $locationA = InventoryLocation::query()->create(['name' => "A {$suffix}"]);
    $locationB = InventoryLocation::query()->create(['name' => "B {$suffix}"]);

    $positionA = InventoryPosition::query()->create([
        'inventory_location_id' => $locationA->id,
        'name' => "P-A {$suffix}",
    ]);

    $positionB = InventoryPosition::query()->create([
        'inventory_location_id' => $locationB->id,
        'name' => "P-B {$suffix}",
    ]);

    $inventory = Inventory::query()->create(['product_id' => $product->id]);

    $movementType = MovementType::query()->create([
        'name' => "Move {$suffix}",
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    return compact('inventory', 'locationA', 'locationB', 'positionA', 'positionB', 'movementType');
}

it('recomputes stock totals when movement item is created updated and deleted', function () {
    [
        'inventory' => $inventory,
        'locationA' => $locationA,
        'locationB' => $locationB,
        'positionA' => $positionA,
        'positionB' => $positionB,
        'movementType' => $movementType,
    ] = makeMovementDomain();

    $movement = Movement::query()->create([
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_location_id' => $locationA->id,
        'from_inventory_position_id' => $positionA->id,
        'to_inventory_location_id' => $locationB->id,
        'to_inventory_position_id' => $positionB->id,
        'description' => 'Transfer test',
    ]);

    $item = MovementItem::query()->create([
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
        ->and((int) $outgoing->stock)->toBe(-5);

    $item->update(['stock' => 8]);

    $incoming->refresh();
    $outgoing->refresh();

    expect((int) $incoming->stock)->toBe(8)
        ->and((int) $outgoing->stock)->toBe(-8);

    $item->delete();

    $incoming->refresh();
    $outgoing->refresh();

    expect((int) $incoming->stock)->toBe(0)
        ->and((int) $outgoing->stock)->toBe(0);
});
