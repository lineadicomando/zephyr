<?php

declare(strict_types=1);

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\ListInventories;
use App\Models\Inventory;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

it('shows only records for active scope', function (): void {
    $scopeA = Scope::factory()->create([
        'name' => 'Scope A',
        'slug' => 'scope-a',
        'type' => 'company',
        'is_active' => true,
    ]);

    $scopeB = Scope::factory()->create([
        'name' => 'Scope B',
        'slug' => 'scope-b',
        'type' => 'school',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:Inventory', 'View:Inventory']);
    $user->scopes()->attach([$scopeA->id, $scopeB->id]);

    $inventoryA = Inventory::factory()->create(['scope_id' => $scopeA->id]);
    $inventoryB = Inventory::factory()->create(['scope_id' => $scopeB->id]);

    $this->actingAs($user);
    activateFilamentTenant($scopeA, [InventoryResource::class]);

    Livewire::test(ListInventories::class)
        ->assertCanSeeTableRecords([$inventoryA])
        ->assertCanNotSeeTableRecords([$inventoryB]);
});
