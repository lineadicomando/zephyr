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
use App\Models\Scope;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeStockForReorderValidation(): Stock
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

    $location = InventoryLocation::factory()->create(['scope_id' => $scope->id, 'name' => "L {$suffix}"]);
    $position = InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => $location->id,
        'name' => "P {$suffix}",
    ]);

    $inventory = Inventory::factory()->create(['scope_id' => $scope->id, 'product_id' => $product->id]);

    return Stock::factory()->create([
        'scope_id' => $scope->id,
        'inventory_id' => $inventory->id,
        'inventory_position_id' => $position->id,
        'stock' => 0,
    ]);
}

it('requires reorder point to be greater than zero', function () {
    $stock = makeStockForReorderValidation();

    expect(function () use ($stock) {
        Reorder::query()->create([
            'scope_id' => $stock->scope_id,
            'stock_id' => $stock->id,
            'reorder_point' => 0,
            'reorder_quantity' => 1,
        ]);
    })->toThrow(ValidationException::class);
});

it('requires reorder quantity to be greater than zero when provided', function () {
    $stock = makeStockForReorderValidation();

    expect(function () use ($stock) {
        Reorder::query()->create([
            'scope_id' => $stock->scope_id,
            'stock_id' => $stock->id,
            'reorder_point' => 2,
            'reorder_quantity' => 0,
        ]);
    })->toThrow(ValidationException::class);
});

it('allows null reorder quantity', function () {
    $stock = makeStockForReorderValidation();

    $rule = Reorder::query()->create([
        'scope_id' => $stock->scope_id,
        'stock_id' => $stock->id,
        'reorder_point' => 2,
        'reorder_quantity' => null,
    ]);

    expect($rule->exists)->toBeTrue()
        ->and($rule->reorder_quantity)->toBeNull();
});
