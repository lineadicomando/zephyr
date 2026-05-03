<?php

declare(strict_types=1);

use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\MovementItem;
use App\Models\MovementType;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductType;
use App\Models\Stock;
use App\Models\User;
use App\Policies\MovementItemPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder())->run();
});

function makeMovementItemInScope(int $scopeId): MovementItem
{
    $group = ProductGroup::query()->create(['name' => 'Group '.str()->uuid()]);
    $type = ProductType::query()->create(['name' => 'Type '.str()->uuid()]);
    $product = Product::query()->create([
        'product_group_id' => $group->id,
        'product_type_id' => $type->id,
        'name' => 'Product '.str()->uuid(),
    ]);

    $inventoryId = DB::table('inventories')->insertGetId([
        'scope_id' => $scopeId,
        'inventory_number' => null,
        'product_id' => $product->id,
        'serial_number' => null,
        'mac_address' => null,
        'stock' => 0,
        'url' => null,
        'description' => 'Inventory '.str()->uuid(),
        'summary' => null,
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $location = InventoryLocation::query()->create(['name' => 'Location '.str()->uuid()]);
    $position = InventoryPosition::query()->create([
        'inventory_location_id' => $location->id,
        'name' => 'Position '.str()->uuid(),
    ]);

    $movementType = MovementType::query()->create([
        'name' => 'Move '.str()->uuid(),
        'chart' => false,
        'chart_color' => '#ffffff',
    ]);

    $movementId = DB::table('movements')->insertGetId([
        'scope_id' => $scopeId,
        'date' => now(),
        'movement_type_id' => $movementType->id,
        'from_inventory_location_id' => null,
        'from_inventory_position_id' => null,
        'to_inventory_location_id' => $location->id,
        'to_inventory_position_id' => $position->id,
        'description' => null,
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stockId = DB::table('stocks')->insertGetId([
        'scope_id' => $scopeId,
        'inventory_id' => $inventoryId,
        'inventory_position_id' => $position->id,
        'path' => '1',
        'stock' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $movementItemId = DB::table('movement_items')->insertGetId([
        'scope_id' => $scopeId,
        'movement_id' => $movementId,
        'inventory_id' => $inventoryId,
        'incoming_stock_id' => $stockId,
        'outcoming_stock_id' => null,
        'inventory_summary' => null,
        'stock' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return MovementItem::query()->withoutGlobalScopes()->findOrFail($movementItemId);
}

it('denies update when movement item belongs to another scope', function (): void {
    $scopeAId = DB::table('scopes')->insertGetId([
        'name' => 'Scope A',
        'slug' => 'scope-a',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $scopeBId = DB::table('scopes')->insertGetId([
        'name' => 'Scope B',
        'slug' => 'scope-b',
        'type' => 'school',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('update_movement_item');

    DB::table('scope_user')->insert([
        'scope_id' => $scopeAId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $movementItemInOtherScope = makeMovementItemInScope($scopeBId);

    $policy = new MovementItemPolicy();

    expect($policy->update($user, $movementItemInOtherScope))->toBeFalse();
});

it('denies update for users without assigned scopes', function (): void {
    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope A',
        'slug' => 'scope-a',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('update_movement_item');

    $movementItem = makeMovementItemInScope($scopeId);

    $policy = new MovementItemPolicy();

    expect($policy->update($user, $movementItem))->toBeFalse();
});
