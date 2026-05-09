<?php

use App\Filament\Widgets\CurrentScopeWidget;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

it('shows current scope and type in dashboard widget', function () {
    $scope = Scope::factory()->create([
        'name' => 'Scope Widget',
        'slug' => 'scope-widget',
        'type' => 'company',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->scopes()->attach($scope);

    $this->actingAs($user);
    activateFilamentTenant($scope);

    Livewire::test(CurrentScopeWidget::class)
        ->assertSee('Scope Widget')
        ->assertSee('Type: company')
        ->assertSee('scope-widget');
});
