<?php

use App\Enums\ChecklistOutcome;
use App\Enums\ChecklistResponseType;
use App\Filament\Resources\ChecklistTemplateResource\Pages\CreateChecklistTemplate;
use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Pages\EditInventory;
use App\Filament\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Resources\InventoryResource\RelationManagers\ChecklistsRelationManager;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\TaskResource\RelationManagers\InventoriesRelationManager;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Inventory;
use App\Models\Scope;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskInventory;
use App\Models\TaskStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();

    $this->scope = Scope::factory()->create(['is_active' => true]);
});

function checklistTechnician(object $test): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('ViewAny:Task', 'View:Task', 'Update:Task', 'ViewAny:Inventory', 'View:Inventory');
    $user->scopes()->attach($test->scope);

    return $user;
}

/**
 * Task of the technician with a projector checklist: a required outcome
 * and an optional reading of the lamp hours.
 */
function projectorChecklistTask(object $test, User $technician): Task
{
    $template = ChecklistTemplate::factory()->create();
    ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create(['label' => 'Remote control', 'sort' => 1]);
    ChecklistTemplateItem::factory()->for($template, 'checklist_template')->number(max: 3000, unit: 'h')->optional()->create(['label' => 'Lamp hours', 'sort' => 2]);

    $task = Task::factory()->create([
        'scope_id' => $test->scope->id,
        'user_id' => $technician->id,
        'checklist_template_id' => $template->id,
    ]);
    $task->inventories()->attach(Inventory::factory()->create([
        'scope_id' => $test->scope->id,
        'inventory_number' => 'PRJ-001',
    ]));

    return $task;
}

function checklistItemKey(Task $task, string $label): string
{
    return 'item_'.$task->checklist_template->items()->where('label', $label)->value('id');
}

it('lets the assigned technician fill the checklist of an inventory', function () {
    $technician = checklistTechnician($this);
    $task = projectorChecklistTask($this, $technician);
    $inventory = $task->inventories()->sole();
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(InventoriesRelationManager::class, ['ownerRecord' => $task, 'pageClass' => EditTask::class])
        ->callAction(TestAction::make('fillChecklist')->table($inventory), [
            'items' => [
                checklistItemKey($task, 'Remote control') => ['value' => ChecklistOutcome::Ok->value],
                checklistItemKey($task, 'Lamp hours') => ['value' => '3200'],
            ],
            'note' => 'Filters cleaned',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified(__('Checklist saved with anomalies'));

    $taskInventory = TaskInventory::query()->with('results')->sole();

    expect($taskInventory->isCompleted())->toBeTrue()
        ->and($taskInventory->completed_by)->toBe($technician->id)
        ->and($taskInventory->has_anomalies)->toBeTrue()
        ->and($taskInventory->note)->toBe('Filters cleaned')
        ->and($taskInventory->results->pluck('value', 'label')->all())->toBe(['Remote control' => 'ok', 'Lamp hours' => '3200']);
});

it('requires the answers of the required items', function () {
    $technician = checklistTechnician($this);
    $task = projectorChecklistTask($this, $technician);
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(InventoriesRelationManager::class, ['ownerRecord' => $task, 'pageClass' => EditTask::class])
        ->callAction(TestAction::make('fillChecklist')->table($task->inventories()->sole()), [
            'items' => [checklistItemKey($task, 'Lamp hours') => ['value' => '1200']],
        ])
        ->assertHasFormErrors(['items.'.checklistItemKey($task, 'Remote control').'.value' => 'required']);

    expect(TaskInventory::query()->sole()->isCompleted())->toBeFalse();
});

it('hides the checklist actions from the technicians not assigned to the task', function () {
    $task = projectorChecklistTask($this, checklistTechnician($this));
    $colleague = checklistTechnician($this);
    $this->actingAs($colleague);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(InventoriesRelationManager::class, ['ownerRecord' => $task, 'pageClass' => EditTask::class])
        ->assertActionHidden(TestAction::make('fillChecklist')->table($task->inventories()->sole()))
        ->assertActionHidden(TestAction::make('scanChecklist')->table());
});

it('opens the checklist of the scanned inventory', function () {
    $technician = checklistTechnician($this);
    $task = projectorChecklistTask($this, $technician);
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(InventoriesRelationManager::class, ['ownerRecord' => $task, 'pageClass' => EditTask::class])
        ->callAction(TestAction::make('scanChecklist')->table(), ['code' => 'PRJ-001'])
        ->assertActionMounted(TestAction::make('fillChecklist')->table($task->inventories()->sole()));
});

it('notifies when the scanned code does not match an inventory of the task', function () {
    $technician = checklistTechnician($this);
    $task = projectorChecklistTask($this, $technician);
    Inventory::factory()->create(['scope_id' => $this->scope->id, 'inventory_number' => 'PRJ-002']);
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(InventoriesRelationManager::class, ['ownerRecord' => $task, 'pageClass' => EditTask::class])
        ->callAction(TestAction::make('scanChecklist')->table(), ['code' => 'PRJ-002'])
        ->assertNotified(__('No inventory of the task matches the code.'))
        ->assertActionNotMounted(TestAction::make('fillChecklist')->table($task->inventories()->sole()));
});

it('rejects the completed status in the task form while a checklist is to do', function () {
    $technician = checklistTechnician($this);
    $task = projectorChecklistTask($this, $technician);
    $completedStatus = TaskStatus::factory()->asCompleted()->create();
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [TaskResource::class]);

    Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
        ->fillForm(['task_status_id' => $completedStatus->id])
        ->call('save')
        ->assertHasFormErrors(['task_status_id']);

    expect($task->fresh()->task_status_id)->not->toBe($completedStatus->id);
});

it('creates a checklist template with its items', function () {
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $superAdmin->scopes()->attach($this->scope);
    $lamp = Tag::factory()->create();
    $this->actingAs($superAdmin);
    activateFilamentTenant($this->scope);
    Repeater::fake();

    Livewire::test(CreateChecklistTemplate::class)
        ->fillForm([
            'name' => 'Projector maintenance',
            'items' => [
                ['label' => 'Visual check', 'response_type' => ChecklistResponseType::Outcome->value, 'is_required' => true, 'tags' => []],
                ['label' => 'Lamp hours', 'response_type' => ChecklistResponseType::Number->value, 'unit' => 'h', 'max' => 3000, 'is_required' => false, 'tags' => [$lamp->id]],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $items = ChecklistTemplate::query()->where('name', 'Projector maintenance')->sole()->items()->with('tags')->get();

    expect($items->pluck('label')->all())->toBe(['Visual check', 'Lamp hours'])
        ->and($items->last()->max)->toBe(3000.0)
        ->and($items->last()->tags->modelKeys())->toBe([$lamp->id]);
});

it('filters the inventories by their own tags and by the tags of their product', function () {
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $superAdmin->scopes()->attach($this->scope);
    $lamp = Tag::factory()->create();
    $tagged = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $tagged->tags()->attach($lamp);
    $inheriting = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $inheriting->product->tags()->attach($lamp);
    $untagged = Inventory::factory()->create(['scope_id' => $this->scope->id]);
    $this->actingAs($superAdmin);
    activateFilamentTenant($this->scope, [InventoryResource::class]);

    Livewire::test(ListInventories::class)
        ->filterTable('tags', [$lamp->id])
        ->assertCanSeeTableRecords([$tagged, $inheriting])
        ->assertCanNotSeeTableRecords([$untagged]);
});

it('lists to the technicians only the checklists of their own tasks', function () {
    $technician = checklistTechnician($this);
    $ownTask = projectorChecklistTask($this, $technician);
    $inventory = $ownTask->inventories()->sole();
    $colleagueTask = Task::factory()->create([
        'scope_id' => $this->scope->id,
        'user_id' => checklistTechnician($this)->id,
        'checklist_template_id' => $ownTask->checklist_template_id,
    ]);
    $colleagueTask->inventories()->attach($inventory);
    $this->actingAs($technician);
    activateFilamentTenant($this->scope, [InventoryResource::class]);

    Livewire::test(ChecklistsRelationManager::class, ['ownerRecord' => $inventory, 'pageClass' => EditInventory::class])
        ->assertCanSeeTableRecords(TaskInventory::query()->where('task_id', $ownTask->id)->get())
        ->assertCanNotSeeTableRecords(TaskInventory::query()->where('task_id', $colleagueTask->id)->get());
});
