<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\CreateInventory;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Models\Scope;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);
});

function selectOptionUser(object $test, string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([$role]);
    $user->scopes()->attach($test->scope);

    return $user;
}

it('hides global catalog option actions from scope admins', function () {
    $this->actingAs(selectOptionUser($this, 'admin'));
    activateFilamentTenant($this->scope, [InventoryResource::class, TaskResource::class]);

    Livewire::test(CreateInventory::class)
        ->assertActionDoesNotExist(TestAction::make('createOption')->schemaComponent('product_id'))
        ->assertActionDoesNotExist(TestAction::make('editOption')->schemaComponent('product_id'));

    Livewire::test(CreateTask::class)
        ->assertActionDoesNotExist(TestAction::make('createOption')->schemaComponent('task_type_id'))
        ->assertActionDoesNotExist(TestAction::make('editOption')->schemaComponent('task_type_id'))
        ->assertActionDoesNotExist(TestAction::make('createOption')->schemaComponent('task_status_id'))
        ->assertActionDoesNotExist(TestAction::make('editOption')->schemaComponent('task_status_id'));
});

it('shows global catalog option actions to super admins', function () {
    $this->actingAs(selectOptionUser($this, 'super_admin'));
    activateFilamentTenant($this->scope, [InventoryResource::class, TaskResource::class]);

    Livewire::test(CreateInventory::class)
        ->assertActionExists(TestAction::make('createOption')->schemaComponent('product_id'));

    Livewire::test(CreateTask::class)
        ->assertActionExists(TestAction::make('createOption')->schemaComponent('task_type_id'))
        ->assertActionExists(TestAction::make('createOption')->schemaComponent('task_status_id'));
});
