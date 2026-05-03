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
use App\Models\User;
use App\Services\Reorders\ReorderOrderService;
use App\Services\Reorders\ReorderProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeRuleForOrderFlow(int $stockQty = 0, int $point = 2, ?int $qty = 3): Reorder
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
        'reorder_quantity' => $qty,
    ]);
}

it('cancels reorder orders from allowed states', function (string $initialStatus) {
    $rule = makeRuleForOrderFlow();
    $actor = User::factory()->create();

    $order = app(ReorderProposalService::class)->createDraftFromCritical($actor->id);

    if ($initialStatus === ReorderOrder::STATUS_REQUESTED) {
        $order = app(ReorderOrderService::class)->request($order, $actor->id);
    }

    if ($initialStatus === ReorderOrder::STATUS_ORDERED) {
        $service = app(ReorderOrderService::class);
        $order = $service->request($order, $actor->id);
        $order = $service->markOrdered($order, $actor->id);
    }

    $cancelled = app(ReorderOrderService::class)->cancel($order, $actor->id);

    expect($cancelled->status)->toBe(ReorderOrder::STATUS_CANCELLED)
        ->and($cancelled->cancelled_at)->not->toBeNull()
        ->and($cancelled->updated_by)->toBe($actor->id);

    $rule->refresh();
    expect($rule->last_reorder_date)->toBeNull();
})->with([
    ReorderOrder::STATUS_DRAFT,
    ReorderOrder::STATUS_REQUESTED,
    ReorderOrder::STATUS_ORDERED,
]);

it('rejects invalid state transitions', function () {
    makeRuleForOrderFlow();

    $service = app(ReorderOrderService::class);
    $order = app(ReorderProposalService::class)->createDraftFromCritical();

    expect(fn () => $service->markOrdered($order))->toThrow(ValidationException::class);
    expect(fn () => $service->markReceived($order))->toThrow(ValidationException::class);

    $order = $service->request($order);
    expect(fn () => $service->request($order))->toThrow(ValidationException::class);

    $order = $service->markOrdered($order);
    $order = $service->markReceived($order);

    expect(fn () => $service->cancel($order))->toThrow(ValidationException::class)
        ->and(fn () => $service->request($order))->toThrow(ValidationException::class);
});
