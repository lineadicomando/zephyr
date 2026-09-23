<?php

use App\Filament\Resources\ScopeResource\Pages\CreateScope;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

it('allows super admin to manage scopes pages', function () {
    $scope = Scope::factory()->create([
        'name' => 'Scope UX',
        'slug' => 'scope-ux',
        'type' => 'company',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->scopes()->attach($scope);

    $this->actingAs($user);

    $slug = $scope->slug;
    $this->get("/{$slug}/scopes")->assertOk();
    $this->get("/{$slug}/scopes/create")->assertOk();
    $this->get("/{$slug}/scopes/{$scope->id}/view")->assertOk();
    $this->get("/{$slug}/scopes/{$scope->id}/edit")->assertOk();
});

it('prevents edit for read-only users while allowing scope view', function () {
    Permission::query()->firstOrCreate(['name' => 'view_any_scope', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'view_scope', 'guard_name' => 'web']);

    $scope = Scope::factory()->create([
        'name' => 'Scope ReadOnly',
        'slug' => 'scope-read-only',
        'type' => 'school',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->givePermissionTo('view_any_scope');
    $user->givePermissionTo('view_scope');
    $user->scopes()->attach($scope);

    $this->actingAs($user);

    $slug = $scope->slug;
    $this->get("/{$slug}/scopes")->assertOk();
    $this->get("/{$slug}/scopes/{$scope->id}/view")->assertOk();
    $this->get("/{$slug}/scopes/{$scope->id}/edit")->assertForbidden();
});

it('user can access their assigned tenants but not others', function () {
    $scopeA = Scope::factory()->create([
        'name' => 'Scope Topbar A',
        'slug' => 'scope-topbar-a',
        'type' => 'company',
        'is_active' => true,
    ]);
    $scopeB = Scope::factory()->create([
        'name' => 'Scope Topbar B',
        'slug' => 'scope-topbar-b',
        'type' => 'school',
        'is_active' => true,
    ]);
    $scopeOther = Scope::factory()->create([
        'name' => 'Scope Other',
        'slug' => 'scope-other',
        'type' => 'company',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->scopes()->attach([$scopeA->id, $scopeB->id]);

    $panel = Filament::getPanel('app');

    expect($user->canAccessTenant($scopeA))->toBeTrue()
        ->and($user->canAccessTenant($scopeB))->toBeTrue()
        ->and($user->canAccessTenant($scopeOther))->toBeFalse();

    $tenants = $user->getTenants($panel);
    expect($tenants->pluck('id')->all())
        ->toContain($scopeA->id)
        ->toContain($scopeB->id)
        ->not->toContain($scopeOther->id);
});

it('accepts only scope slugs that can be used in the tenant url', function (string $slug, bool $isValid) {
    $scope = Scope::factory()->create(['is_active' => true]);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->scopes()->attach($scope);

    $this->actingAs($user);
    activateFilamentTenant($scope);

    $component = Livewire::test(CreateScope::class)
        ->fillForm(['name' => 'New scope', 'slug' => $slug, 'type' => 'company'])
        ->call('create');

    $isValid
        ? $component->assertHasNoFormErrors(['slug'])
        : $component->assertHasFormErrors(['slug' => 'regex']);
})->with([
    'reserved api' => ['api', false],
    'uppercase' => ['Branch', false],
    'api prefix' => ['api-team', true],
    'lowercase with dash' => ['new-branch', true],
]);
