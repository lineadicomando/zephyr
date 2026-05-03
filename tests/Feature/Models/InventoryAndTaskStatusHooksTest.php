<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeProductForInventoryHooks(string $suffix): Product
{
    $brand = ProductBrand::query()->create(['name' => "Brand {$suffix}"]);
    $model = ProductModel::query()->create(['name' => "Model {$suffix}", 'product_brand_id' => $brand->id]);
    $type = ProductType::query()->create(['name' => "Type {$suffix}"]);
    $group = ProductGroup::query()->create(['name' => "Group {$suffix}"]);

    return Product::query()->create([
        'product_group_id' => $group->id,
        'product_type_id' => $type->id,
        'product_brand_id' => $brand->id,
        'product_model_id' => $model->id,
        'code' => "CODE-{$suffix}",
        'name' => "Product {$suffix}",
    ]);
}

it('auto assigns inventory number and syncs inventory summary on save', function () {
    putenv('INVENTORY_NUMBER_ZERO_FILL=6');

    $suffix = (string) str()->uuid();
    $product = makeProductForInventoryHooks($suffix);

    $inventory = Inventory::query()->create([
        'product_id' => $product->id,
        'serial_number' => 'SN-42',
        'description' => 'Desk station',
    ]);

    $inventory->refresh();

    expect($inventory->inventory_number)->toBe(str_pad((string) $inventory->id, 6, '0', STR_PAD_LEFT))
        ->and($inventory->summary)->toContain($inventory->inventory_number)
        ->and($inventory->summary)->toContain('CODE-')
        ->and($inventory->summary)->toContain('SN-42')
        ->and($inventory->summary)->toContain('Desk station');
});

it('keeps default and completed task status unique', function () {
    $first = TaskStatus::factory()->create(['default' => true, 'completed' => true]);
    $second = TaskStatus::factory()->create(['default' => true, 'completed' => true]);

    $first->refresh();
    $second->refresh();

    expect((int) $second->default)->toBe(1)
        ->and((int) $second->completed)->toBe(1)
        ->and((int) $first->default)->toBe(0)
        ->and((int) $first->completed)->toBe(0);
});
