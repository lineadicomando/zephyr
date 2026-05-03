<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Models\User;
use App\Policies\InventoryPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder())->run();
});

it('denies update when record belongs to another scope', function (): void {
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
    $user->givePermissionTo('update_inventory');

    DB::table('scope_user')->insert([
        'scope_id' => $scopeAId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $inventoryInOtherScope = Inventory::factory()->create(['scope_id' => $scopeBId]);

    $policy = new InventoryPolicy();

    expect($policy->update($user, $inventoryInOtherScope))->toBeFalse();
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
    $user->givePermissionTo('update_inventory');

    $inventory = Inventory::factory()->create(['scope_id' => $scopeId]);

    $policy = new InventoryPolicy();

    expect($policy->update($user, $inventory))->toBeFalse();
});
