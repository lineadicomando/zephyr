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

it('shows only records for active scope', function (): void {
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
});
