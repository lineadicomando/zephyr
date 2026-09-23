<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductType;
use App\Models\Stock;
use App\Models\User;
use App\Policies\StockPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

function makeStockInScope(int $scopeId): Stock
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

    $locationId = DB::table('inventory_locations')->insertGetId([
        'scope_id' => $scopeId,
        'name' => 'Location '.str()->uuid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $positionId = DB::table('inventory_positions')->insertGetId([
        'scope_id' => $scopeId,
        'inventory_location_id' => $locationId,
        'name' => 'Position '.str()->uuid(),
        'path' => 'location/position',
        'default' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stockId = DB::table('stocks')->insertGetId([
        'scope_id' => $scopeId,
        'inventory_id' => $inventoryId,
        'inventory_position_id' => $positionId,
        'path' => '1',
        'stock' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Stock::query()->withoutGlobalScopes()->findOrFail($stockId);
}

it('denies update when stock belongs to another scope', function (): void {
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
    $user->givePermissionTo('Update:Stock');

    DB::table('scope_user')->insert([
        'scope_id' => $scopeAId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stockInOtherScope = makeStockInScope($scopeBId);

    $policy = new StockPolicy;

    expect($policy->update($user, $stockInOtherScope))->toBeFalse();
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
    $user->givePermissionTo('Update:Stock');

    $stock = makeStockInScope($scopeId);

    $policy = new StockPolicy;

    expect($policy->update($user, $stock))->toBeFalse();
});
