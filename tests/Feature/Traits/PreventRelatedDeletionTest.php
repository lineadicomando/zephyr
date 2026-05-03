<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeInventoryForDeletionTest(): Inventory
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

    return Inventory::query()->create([
        'product_id' => $product->id,
        'description' => 'Deletion test inventory',
    ]);
}

it('blocks inventory deletion when related tasks exist', function () {
    $inventory = makeInventoryForDeletionTest();
    $task = Task::factory()->create();

    $inventory->tasks()->attach($task->id);

    expect($inventory->delete())->toBeFalse();
    expect(Inventory::query()->whereKey($inventory->id)->exists())->toBeTrue();
});

it('blocks task status deletion when related tasks exist', function () {
    $status = TaskStatus::factory()->create();

    Task::factory()->create([
        'task_status_id' => $status->id,
        'task_type_id' => TaskType::factory()->create()->id,
        'user_id' => User::factory()->create()->id,
    ]);

    expect($status->delete())->toBeFalse();
    expect(TaskStatus::query()->whereKey($status->id)->exists())->toBeTrue();
});

it('allows task status deletion when no related tasks exist', function () {
    $status = TaskStatus::factory()->create();

    expect($status->delete())->toBeTrue();
    expect(TaskStatus::query()->whereKey($status->id)->exists())->toBeFalse();
});
