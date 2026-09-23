<?php

use App\Enums\ChecklistGrouping;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Models\ChecklistTemplate;
use App\Models\Inventory;
use App\Models\Scope;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);
});

function planningUser(object $test, string $role): User
{
    $user = User::factory()->create();
    $user->scopes()->attach($test->scope);
    setPermissionsTeamId($test->scope->id);
    $user->assignRole($role);

    return $user;
}

it('lets the admins plan the checklists of many inventories in more tasks', function () {
    $admin = planningUser($this, 'admin');
    $technician = planningUser($this, 'user');
    $template = ChecklistTemplate::factory()->create(['name' => 'PC check']);
    $status = TaskStatus::factory()->asDefault()->create();
    [$labInventory, $libraryInventory] = Inventory::factory()->count(2)->create(['scope_id' => $this->scope->id]);
    $this->actingAs($admin);
    activateFilamentTenant($this->scope, [TaskResource::class]);
    Repeater::fake();

    Livewire::test(ListTasks::class)
        ->callAction('planChecklists', [
            'checklist_template_id' => $template->id,
            'task_type_id' => TaskType::factory()->create()->id,
            'task_status_id' => $status->id,
            'starts_at' => '2026-10-05 08:00',
            'description' => 'PC check',
            'inventory_ids' => [$labInventory->id, $libraryInventory->id],
            'grouping' => ChecklistGrouping::Location->value,
            'groups' => [
                ['label' => 'Lab', 'available_ids' => [$labInventory->id], 'inventory_ids' => [$labInventory->id], 'user_id' => $technician->id],
                ['label' => 'Library', 'available_ids' => [$libraryInventory->id], 'inventory_ids' => [$libraryInventory->id], 'user_id' => $admin->id],
            ],
        ])
        ->assertHasNoFormErrors()
        ->assertNotified(trans_choice('{0} No task created|{1} :count task created|[2,*] :count tasks created', 2));

    $tasks = Task::query()->with('inventories')->orderBy('id')->get();

    expect($tasks->pluck('description')->all())->toBe(['PC check - Lab', 'PC check - Library'])
        ->and($tasks->pluck('user_id')->all())->toBe([$technician->id, $admin->id])
        ->and($tasks->first()->inventories->modelKeys())->toBe([$labInventory->id])
        ->and($tasks->every(fn (Task $task): bool => $task->checklist_template_id === $template->id))->toBeTrue();
});

it('hides the checklist planning from the technicians', function () {
    $this->actingAs(planningUser($this, 'user'));
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(ListTasks::class)->assertActionHidden('planChecklists');
});

it('reschedules the task and opens the new one', function () {
    $admin = planningUser($this, 'admin');
    $technician = planningUser($this, 'user');
    $task = Task::factory()->create(['scope_id' => $this->scope->id, 'user_id' => $admin->id]);
    $task->inventories()->attach(Inventory::factory()->create(['scope_id' => $this->scope->id]));
    $this->actingAs($admin);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    $component = Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
        ->callAction('reschedule', [
            'starts_at' => '2027-03-01 09:00',
            'user_id' => $technician->id,
            'description' => 'Rescheduled check',
        ])
        ->assertHasNoFormErrors();

    $copy = Task::query()->whereKeyNot($task->getKey())->sole();

    $component->assertRedirect(EditTask::getUrl(['record' => $copy]));

    expect($copy->user_id)->toBe($technician->id)
        ->and($copy->inventories()->pluck('inventories.id')->all())->toBe($task->inventories()->pluck('inventories.id')->all());
});

it('assigns the rescheduled task to the technician who reschedules it', function () {
    $technician = planningUser($this, 'user');
    $colleague = planningUser($this, 'user');
    $task = Task::factory()->create(['scope_id' => $this->scope->id, 'user_id' => $technician->id]);
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
        ->callAction('reschedule', [
            'starts_at' => '2027-03-01 09:00',
            'user_id' => $colleague->id,
            'description' => 'Rescheduled check',
        ])
        ->assertHasNoFormErrors();

    expect(Task::query()->whereKeyNot($task->getKey())->sole()->user_id)->toBe($technician->id);
});
