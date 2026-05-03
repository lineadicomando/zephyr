<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\ScopesRelationManager;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolesAndPermissionsSeeder())->run();
});

it('hides detach action when enforcement is enabled and user has only one scope', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Only Scope',
        'slug' => 'only-scope',
        'type' => 'company',
        'is_active' => true,
    ]);

    $owner = User::factory()->create();
    $owner->assignRole('super_admin');
    $owner->scopes()->sync([$scope->id]);

    $this->actingAs($owner);

    Livewire::test(ScopesRelationManager::class, [
        'ownerRecord' => $owner,
        'pageClass' => EditUser::class,
    ])
        ->assertTableActionHidden('detach', $scope);
});

it('shows detach action when enforcement is enabled and user has multiple scopes', function (): void {
    $scopeA = Scope::query()->create([
        'name' => 'Scope A',
        'slug' => 'scope-a-detach',
        'type' => 'company',
        'is_active' => true,
    ]);
    $scopeB = Scope::query()->create([
        'name' => 'Scope B',
        'slug' => 'scope-b-detach',
        'type' => 'school',
        'is_active' => true,
    ]);

    $owner = User::factory()->create();
    $owner->assignRole('super_admin');
    $owner->scopes()->sync([$scopeA->id, $scopeB->id]);

    $this->actingAs($owner);

    Livewire::test(ScopesRelationManager::class, [
        'ownerRecord' => $owner,
        'pageClass' => EditUser::class,
    ])
        ->assertTableActionVisible('detach', $scopeA)
        ->assertTableActionVisible('detach', $scopeB);
});
