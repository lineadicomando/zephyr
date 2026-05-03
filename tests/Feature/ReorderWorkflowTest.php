<?php

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\Reorder;
use App\Models\ReorderOrder;
use App\Models\Stock;
use App\Services\Reorders\ReorderOrderService;
use App\Services\Reorders\ReorderProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStockWithReorder(int $stockQty, int $point, ?int $qty = null): Reorder
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
    $location = InventoryLocation::query()->create(['name' => "L {$suffix}"]);
    $position = InventoryPosition::query()->create([
        'inventory_location_id' => $location->id,
        'path' => "L/P {$suffix}",
        'name' => "P {$suffix}",
    ]);
    $inventory = Inventory::query()->create(['product_id' => $product->id]);
    $stock = Stock::query()->create([
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $position->id,
        'stock' => $stockQty,
    ]);

    return Reorder::query()->create([
        'stock_id' => $stock->id,
        'reorder_point' => $point,
        'reorder_quantity' => $qty,
    ]);
}

it('generates proposal from critical rules only', function () {
    makeStockWithReorder(1, 2, 5); // critical
    makeStockWithReorder(2, 2, 4); // warning

    $order = app(ReorderProposalService::class)->createDraftFromCritical();

    expect($order->status)->toBe(ReorderOrder::STATUS_DRAFT)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->suggested_qty)->toBe(5);
});

it('enforces reorder order state transitions and updates last reorder date', function () {
    $rule = makeStockWithReorder(0, 2, 3);
    $order = app(ReorderProposalService::class)->createDraftFromCritical();

    $service = app(ReorderOrderService::class);
    $order = $service->request($order);
    $order = $service->markOrdered($order);
    $order = $service->markReceived($order);

    expect($order->status)->toBe(ReorderOrder::STATUS_RECEIVED);

    $rule->refresh();
    expect($rule->last_reorder_date)->not->toBeNull();
});

it('prevents duplicate reorder rules for the same stock', function () {
    $rule = makeStockWithReorder(3, 2, 5);

    Reorder::query()->create([
        'stock_id' => $rule->stock_id,
        'reorder_point' => 4,
        'reorder_quantity' => 3,
    ]);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);
