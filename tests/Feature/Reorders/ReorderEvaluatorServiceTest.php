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
use App\Models\Stock;
use App\Services\Reorders\ReorderEvaluatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeRuleWithStockLevel(int $stockQty, int $point): Reorder
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
        'reorder_quantity' => 1,
    ]);
}

it('classifies rules into critical warning and ok buckets', function () {
    $critical = makeRuleWithStockLevel(1, 2);
    $warning = makeRuleWithStockLevel(2, 2);
    $ok = makeRuleWithStockLevel(3, 2);

    $service = app(ReorderEvaluatorService::class);

    $criticalIds = $service->critical()->pluck('reorders.id')->all();
    $warningIds = $service->warning()->pluck('reorders.id')->all();
    $okIds = $service->ok()->pluck('reorders.id')->all();

    expect($criticalIds)->toContain($critical->id)->not->toContain($warning->id, $ok->id)
        ->and($warningIds)->toContain($warning->id)->not->toContain($critical->id, $ok->id)
        ->and($okIds)->toContain($ok->id)->not->toContain($critical->id, $warning->id);
});
