<?php

declare(strict_types=1);

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\ListInventories;
use App\Models\Inventory;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

it('switches active tenant and updates visible dataset', function (): void {
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
    $user->givePermissionTo(['view_any_inventory', 'view_inventory']);
    $user->scopes()->attach([$scopeA->id, $scopeB->id]);

    $inventoryA = Inventory::factory()->create(['scope_id' => $scopeA->id]);
    $inventoryB = Inventory::factory()->create(['scope_id' => $scopeB->id]);

    $this->actingAs($user);
    activateFilamentTenant($scopeA, [InventoryResource::class]);

    Livewire::test(ListInventories::class)
        ->assertCanSeeTableRecords([$inventoryA])
        ->assertCanNotSeeTableRecords([$inventoryB]);

    Filament::setTenant($scopeB);
    Livewire::test(ListInventories::class)
        ->assertCanSeeTableRecords([$inventoryB])
        ->assertCanNotSeeTableRecords([$inventoryA]);
});

it('only allows access to active scopes the user belongs to', function (): void {
    $scopeA = Scope::factory()->create([
        'name' => 'Scope A',
        'slug' => 'scope-a-web',
        'type' => 'company',
        'is_active' => true,
    ]);

    $scopeB = Scope::factory()->create([
        'name' => 'Scope B',
        'slug' => 'scope-b-web',
        'type' => 'school',
        'is_active' => true,
    ]);

    $scopeInactive = Scope::factory()->create([
        'name' => 'Scope Inactive',
        'slug' => 'scope-inactive-web',
        'type' => 'team',
        'is_active' => false,
    ]);

    $user = User::factory()->create();
    $user->scopes()->attach([$scopeA->id, $scopeB->id, $scopeInactive->id]);

    // User can access scopes they belong to
    expect($user->canAccessTenant($scopeA))->toBeTrue()
        ->and($user->canAccessTenant($scopeB))->toBeTrue();

    // Inactive scopes are excluded from the tenant list returned to Filament
    $panel = Filament::getPanel('app');
    $tenants = $user->getTenants($panel);
    expect($tenants->pluck('id')->all())
        ->toContain($scopeA->id)
        ->toContain($scopeB->id)
        ->not->toContain($scopeInactive->id);

    // A scope not assigned to the user cannot be accessed
    $scopeOther = Scope::factory()->create([
        'name' => 'Scope Other',
        'slug' => 'scope-other-web',
        'type' => 'company',
        'is_active' => true,
    ]);
    expect($user->canAccessTenant($scopeOther))->toBeFalse();
});
