<?php

use App\Filament\Resources\MovementItemResource;
use App\Filament\Resources\MovementItemResource\Pages\ListMovementItems;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('exports the movement items to an Excel file', function () {
    (new RolesAndPermissionsSeeder)->run();
    $scope = Scope::factory()->create(['is_active' => true]);
    $admin = User::factory()->inScope($scope)->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
    activateFilamentTenant($scope, [MovementItemResource::class]);

    Livewire::test(ListMovementItems::class)
        ->callAction('table')
        ->assertFileDownloaded();
});
