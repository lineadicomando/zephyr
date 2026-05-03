<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder())->run();
});

it('forbids users without assigned scopes', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['view_any_inventory', 'view_inventory']);
    $user->scopes()->detach();

    $this->actingAs($user)
        ->get('/inventories')
        ->assertForbidden();
});

it('returns empty scoped queries without active scope', function (): void {
    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope A',
        'slug' => 'scope-a',
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Inventory::factory()->create(['scope_id' => $scopeId]);

    $user = User::factory()->create();
    $user->givePermissionTo(['view_any_inventory', 'view_inventory']);
    $user->scopes()->sync([$scopeId]);

    $this->actingAs($user);

    expect(Inventory::query()->count())->toBe(0);
});
