<?php

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Filament\Resources\TaskResource\RelationManagers\InventoriesRelationManager;
use App\Filament\Widgets\ChecklistAnomaliesWidget;
use App\Models\ChecklistTemplate;
use App\Models\Inventory;
use App\Models\Scope;
use App\Models\Task;
use App\Models\TaskInventory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);
});

function anomaliesUser(object $test, string $role): User
{
    $user = User::factory()->create();
    $user->scopes()->attach($test->scope);
    setPermissionsTeamId($test->scope->id);
    $user->assignRole($role);

    return $user;
}

/**
 * Checklist task of the user with one inventory per state: 'to_do', 'ok'
 * or 'anomalies'.
 *
 * @return array<string, TaskInventory>
 */
function checklistTaskWithStates(Scope $scope, User $user, array $states): array
{
    $task = Task::factory()->create([
        'scope_id' => $scope->id,
        'user_id' => $user->id,
        'checklist_template_id' => ChecklistTemplate::factory()->create()->id,
    ]);

    return collect($states)->mapWithKeys(function (string $state) use ($scope, $task): array {
        $task->inventories()->attach(Inventory::factory()->create(['scope_id' => $scope->id]));
        $taskInventory = TaskInventory::query()->where('task_id', $task->id)->latest('id')->first();
        $taskInventory->update([
            'completed_at' => $state === 'to_do' ? null : now(),
            'has_anomalies' => $state === 'anomalies',
        ]);

        return [$state => $taskInventory];
    })->all();
}

it('lists the open anomalies of the scope on the dashboard', function () {
    $admin = anomaliesUser($this, 'admin');
    $checklists = checklistTaskWithStates($this->scope, $admin, ['ok', 'anomalies']);
    $otherScopeChecklists = checklistTaskWithStates(Scope::factory()->create(), $admin, ['anomalies']);
    $this->actingAs($admin);
    activateFilamentTenant($this->scope);

    Livewire::test(ChecklistAnomaliesWidget::class)
        ->assertCanSeeTableRecords([$checklists['anomalies']])
        ->assertCanNotSeeTableRecords([$checklists['ok'], $otherScopeChecklists['anomalies']]);
});

it('lists to the technicians only the anomalies of their tasks', function () {
    $technician = anomaliesUser($this, 'user');
    $ownChecklists = checklistTaskWithStates($this->scope, $technician, ['anomalies']);
    $colleagueChecklists = checklistTaskWithStates($this->scope, anomaliesUser($this, 'user'), ['anomalies']);
    $this->actingAs($technician);
    activateFilamentTenant($this->scope);

    Livewire::test(ChecklistAnomaliesWidget::class)
        ->assertCanSeeTableRecords([$ownChecklists['anomalies']])
        ->assertCanNotSeeTableRecords([$colleagueChecklists['anomalies']]);
});

it('filters the tasks by the state of their checklists', function (string $filter, array $expectedTasks) {
    $admin = anomaliesUser($this, 'admin');
    $tasks = [
        'anomalies' => checklistTaskWithStates($this->scope, $admin, ['ok', 'anomalies'])['ok']->task,
        'to_do' => checklistTaskWithStates($this->scope, $admin, ['ok', 'to_do'])['ok']->task,
        'completed' => checklistTaskWithStates($this->scope, $admin, ['ok', 'ok'])['ok']->task,
    ];
    $this->actingAs($admin);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(ListTasks::class)
        ->filterTable('checklist', $filter)
        ->assertCanSeeTableRecords(collect($tasks)->only($expectedTasks)->values())
        ->assertCanNotSeeTableRecords(collect($tasks)->except($expectedTasks)->values());
})->with([
    'with anomalies' => ['anomalies', ['anomalies']],
    'to fill' => ['to_do', ['to_do']],
    'all filled, with or without anomalies' => ['completed', ['anomalies', 'completed']],
]);

it('filters the inventories of a task by the state of their checklist', function (string $state) {
    $admin = anomaliesUser($this, 'admin');
    $checklists = checklistTaskWithStates($this->scope, $admin, ['to_do', 'ok', 'anomalies']);
    $inventories = collect($checklists)->map(fn (TaskInventory $taskInventory): Inventory => $taskInventory->inventory);
    $this->actingAs($admin);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(InventoriesRelationManager::class, ['ownerRecord' => $checklists['ok']->task, 'pageClass' => EditTask::class])
        ->filterTable('checklist', $state)
        ->assertCanSeeTableRecords([$inventories[$state]])
        ->assertCanNotSeeTableRecords($inventories->except($state)->values());
})->with(['to_do', 'ok', 'anomalies']);

it('filters the inventories with anomalies found by their latest checklist', function () {
    $admin = anomaliesUser($this, 'admin');
    $checklists = checklistTaskWithStates($this->scope, $admin, ['ok', 'anomalies']);
    $this->actingAs($admin);
    activateFilamentTenant($this->scope, [InventoryResource::class]);

    Livewire::test(ListInventories::class)
        ->filterTable('open_anomalies')
        ->assertCanSeeTableRecords([$checklists['anomalies']->inventory])
        ->assertCanNotSeeTableRecords([$checklists['ok']->inventory]);
});
