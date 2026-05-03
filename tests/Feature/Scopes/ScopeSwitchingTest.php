<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Filament\Resources\InventoryResource\Pages\ListInventories;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder())->run();
});

it('switches active scope and updates visible dataset', function (): void {
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
    $user->givePermissionTo(['view_any_inventory', 'view_inventory']);

    DB::table('scope_user')->insert([
        ['scope_id' => $scopeAId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ['scope_id' => $scopeBId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $inventoryA = Inventory::factory()->create(['scope_id' => $scopeAId]);
    $inventoryB = Inventory::factory()->create(['scope_id' => $scopeBId]);

    $this->actingAs($user)
        ->withSession(['active_scope_id' => $scopeAId]);

    Livewire::test(ListInventories::class)
        ->assertCanSeeTableRecords([$inventoryA])
        ->assertCanNotSeeTableRecords([$inventoryB]);

    $this->withSession(['active_scope_id' => $scopeBId]);

    Livewire::test(ListInventories::class)
        ->assertCanSeeTableRecords([$inventoryB])
        ->assertCanNotSeeTableRecords([$inventoryA]);
});

it('switches active scope via web endpoint only for allowed active scopes', function (): void {
    $scopeAId = DB::table('scopes')->insertGetId([
        'name' => 'Scope A',
        'slug' => 'scope-a-web',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $scopeBId = DB::table('scopes')->insertGetId([
        'name' => 'Scope B',
        'slug' => 'scope-b-web',
        'type' => 'school',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $scopeInactiveId = DB::table('scopes')->insertGetId([
        'name' => 'Scope Inactive',
        'slug' => 'scope-inactive-web',
        'type' => 'team',
        'is_active' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create();

    DB::table('scope_user')->insert([
        ['scope_id' => $scopeAId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ['scope_id' => $scopeBId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ['scope_id' => $scopeInactiveId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->actingAs($user)
        ->from('/inventories')
        ->withSession(['active_scope_id' => $scopeAId])
        ->post("/scopes/{$scopeBId}/switch")
        ->assertRedirect('/inventories');

    $this->assertEquals($scopeBId, session('active_scope_id'));

    $this->actingAs($user)
        ->withSession(['active_scope_id' => $scopeBId])
        ->post("/scopes/{$scopeInactiveId}/switch")
        ->assertForbidden();
});
