<?php

use App\Models\InventoryLocation;
use App\Models\Movement;
use App\Models\Reorder;
use App\Models\ReorderOrder;
use App\Models\Scope;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->ownScope = Scope::factory()->create(['is_active' => true]);
    $this->foreignScope = Scope::factory()->create(['is_active' => true]);

    $this->admin = User::factory()->inScope($this->ownScope)->create();
    $this->admin->assignRole('admin');
});

it('does not open records of another scope by changing the id in the url', function (string $path, Closure $makeRecord, array $pages) {
    $ownRecord = $makeRecord($this->ownScope);
    $foreignRecord = $makeRecord($this->foreignScope);

    $this->actingAs($this->admin);

    foreach ($pages as $page) {
        $this->get("/{$this->ownScope->slug}/{$path}/{$ownRecord->getKey()}/{$page}")->assertOk();
        $this->get("/{$this->ownScope->slug}/{$path}/{$foreignRecord->getKey()}/{$page}")->assertNotFound();
    }
})->with([
    'movement' => ['movements', fn (Scope $scope): Model => Movement::factory()->create(['scope_id' => $scope->id]), ['view', 'edit']],
    'reorder' => ['reorders', fn (Scope $scope): Model => Reorder::factory()->create(['scope_id' => $scope->id]), ['view', 'edit']],
    'reorder order' => ['reorder-orders', fn (Scope $scope): Model => ReorderOrder::query()->create(['scope_id' => $scope->id, 'status' => ReorderOrder::STATUS_DRAFT]), ['view', 'edit']],
    'location' => ['inventory-locations', fn (Scope $scope): Model => InventoryLocation::factory()->create(['scope_id' => $scope->id]), ['view', 'edit']],
    'task' => ['tasks', fn (Scope $scope): Model => Task::factory()->create(['scope_id' => $scope->id]), ['view', 'edit']],
]);
