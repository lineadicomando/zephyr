<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder)->run();
});

it('forbids users without assigned scopes', function (): void {
    $scope = Scope::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo(['view_any_inventory', 'view_inventory']);
    $user->scopes()->detach();

    $this->actingAs($user)
        ->get("/{$scope->slug}/inventories")
        ->assertNotFound();
});

it('redirects users without scopes when accessing a tenant url', function (): void {
    $scope = Scope::factory()->create();

    Inventory::factory()->create(['scope_id' => $scope->id]);

    $user = User::factory()->create();
    $user->givePermissionTo(['view_any_inventory', 'view_inventory']);
    $user->scopes()->detach();

    $this->actingAs($user)
        ->get("/{$scope->slug}/inventories")
        ->assertNotFound();
});
