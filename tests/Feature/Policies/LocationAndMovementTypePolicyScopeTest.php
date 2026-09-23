<?php

use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\MovementType;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->ownScope = Scope::factory()->create(['is_active' => true]);
    $this->foreignScope = Scope::factory()->create(['is_active' => true]);

    $this->admin = User::factory()->create();
    $this->admin->syncRoles(['admin']);
    $this->admin->scopes()->sync([$this->ownScope->id]);
});

it('allows managing locations, positions and movement types only in the scopes of the user', function (Closure $makeRecord) {
    $ownRecord = $makeRecord($this->ownScope);
    $foreignRecord = $makeRecord($this->foreignScope);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($this->admin->can($ability, $ownRecord))->toBeTrue()
            ->and($this->admin->can($ability, $foreignRecord))->toBeFalse();
    }
})->with([
    'location' => [fn (Scope $scope) => InventoryLocation::factory()->create(['scope_id' => $scope->id])],
    'position' => [fn (Scope $scope) => InventoryPosition::factory()->create([
        'scope_id' => $scope->id,
        'inventory_location_id' => InventoryLocation::factory()->create(['scope_id' => $scope->id])->id,
    ])],
    'movement type' => [fn (Scope $scope) => MovementType::factory()->create(['scope_id' => $scope->id])],
]);
