<?php

use App\Filament\Resources\MovementResource\Widgets\MovementChart;
use App\Filament\Resources\TaskResource\Widgets\TaskChart;
use App\Filament\Widgets\CurrentScopeWidget;
use App\Filament\Widgets\StatsOverview;
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

it('shows dashboard widgets only to users with their shield permission', function (string $widget) {
    $scope = Scope::factory()->create(['is_active' => true]);

    $withoutPermission = User::factory()->create();
    $withoutPermission->syncRoles([]);
    $withoutPermission->scopes()->attach($scope);

    $withPreset = User::factory()->create();
    $withPreset->syncRoles(['user']);
    $withPreset->scopes()->attach($scope);

    $this->actingAs($withoutPermission);
    expect($widget::canView())->toBeFalse();

    $this->actingAs($withPreset);
    expect($widget::canView())->toBeTrue();
})->with([
    CurrentScopeWidget::class,
    StatsOverview::class,
    MovementChart::class,
    TaskChart::class,
]);

it('charts the movements of the last twelve months on the dashboard', function () {
    $scope = Scope::factory()->create(['is_active' => true]);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->scopes()->attach($scope);

    $this->actingAs($user);
    activateFilamentTenant($scope);

    $stats = (new ReflectionMethod(StatsOverview::class, 'getStats'))->invoke(new StatsOverview);

    expect($stats[0]->getChart())->toHaveCount(13);
});
