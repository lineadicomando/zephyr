<?php

use App\Enums\ChecklistGrouping;
use App\Models\ChecklistTemplate;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryPosition;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Scope;
use App\Models\Stock;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Services\Checklists\ChecklistPlanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inventoryAtPosition(InventoryPosition $position): Inventory
{
    return Stock::factory()->create([
        'scope_id' => $position->scope_id,
        'inventory_position_id' => $position->id,
    ])->inventory;
}

it('selects the inventories matching every filter plus the ones picked one by one', function () {
    $scope = Scope::factory()->create();
    $lamp = Tag::factory()->create();
    $projector = ProductType::factory()->create();
    $lampProjector = Product::factory()->create(['product_type_id' => $projector->id]);
    $lampProjector->tags()->attach($lamp);

    $matching = Inventory::factory()->create(['scope_id' => $scope->id, 'product_id' => $lampProjector->id]);
    $taggedOtherType = Inventory::factory()->create(['scope_id' => $scope->id]);
    $taggedOtherType->tags()->attach($lamp);
    $picked = Inventory::factory()->create(['scope_id' => $scope->id]);
    Inventory::factory()->create(['scope_id' => Scope::factory()->create()->id, 'product_id' => $lampProjector->id]);

    $inventories = app(ChecklistPlanningService::class)->matchingInventories($scope->id, [
        'tag_ids' => [$lamp->id],
        'product_type_ids' => [$projector->id],
        'inventory_ids' => [$picked->id],
    ]);

    expect($inventories->modelKeys())->toEqualCanonicalizing([$matching->id, $picked->id]);
});

it('selects the inventories stocked in a location', function () {
    $position = InventoryPosition::factory()->create();
    $inLocation = inventoryAtPosition($position);
    inventoryAtPosition(InventoryPosition::factory()->create(['scope_id' => $position->scope_id]));

    $inventories = app(ChecklistPlanningService::class)->matchingInventories($position->scope_id, [
        'location_ids' => [$position->inventory_location_id],
    ]);

    expect($inventories->modelKeys())->toBe([$inLocation->id]);
});

it('selects no inventory without filters', function () {
    Inventory::factory()->create();

    expect(app(ChecklistPlanningService::class)->matchingInventories(Scope::query()->value('id'), []))->toBeEmpty();
});

it('splits the inventories by location, position or in a single group', function (ChecklistGrouping $grouping, array $expectedGroups) {
    $scope = Scope::factory()->create();
    $lab = InventoryLocation::factory()->create(['scope_id' => $scope->id, 'name' => 'Lab']);
    $desk = InventoryPosition::factory()->create(['scope_id' => $scope->id, 'inventory_location_id' => $lab->id, 'name' => 'Desk']);
    $wall = InventoryPosition::factory()->create(['scope_id' => $scope->id, 'inventory_location_id' => $lab->id, 'name' => 'Wall']);
    $inventories = collect([
        'desk' => inventoryAtPosition($desk),
        'wall' => inventoryAtPosition($wall),
        'unstocked' => Inventory::factory()->create(['scope_id' => $scope->id]),
    ]);

    $groups = app(ChecklistPlanningService::class)->groupInventories(
        Inventory::query()->whereKey($inventories->map->id->all())->get(),
        $grouping,
    );

    $expected = collect($expectedGroups)
        ->map(fn (array $names, string $label): array => [
            'label' => $label === 'none' ? __('Without position') : $label,
            'inventory_ids' => collect($names)->map(fn (string $name): int => $inventories[$name]->id)->sort()->values()->all(),
        ])
        ->values()
        ->all();

    expect(collect($groups)->map(fn (array $group): array => [...$group, 'inventory_ids' => collect($group['inventory_ids'])->sort()->values()->all()])->all())
        ->toEqualCanonicalizing($expected);
})->with([
    'by location' => [ChecklistGrouping::Location, ['Lab' => ['desk', 'wall'], 'none' => ['unstocked']]],
    'by position' => [ChecklistGrouping::Position, ['Lab \ Desk' => ['desk'], 'Lab \ Wall' => ['wall'], 'none' => ['unstocked']]],
    'single task' => [ChecklistGrouping::Single, ['' => ['desk', 'wall', 'unstocked']]],
]);

it('creates a task for every group with inventories and a technician', function () {
    $scope = Scope::factory()->create();
    $template = ChecklistTemplate::factory()->create();
    $status = TaskStatus::factory()->asDefault()->create();
    $firstTechnician = User::factory()->create();
    $secondTechnician = User::factory()->create();
    [$labInventory, $libraryInventory] = Inventory::factory()->count(2)->create(['scope_id' => $scope->id]);

    $tasks = app(ChecklistPlanningService::class)->createTasks($scope->id, [
        'task_type_id' => TaskType::factory()->create()->id,
        'task_status_id' => $status->id,
        'checklist_template_id' => $template->id,
        'starts_at' => '2026-10-05 08:00',
        'description' => 'PC check',
    ], [
        ['label' => 'Lab', 'inventory_ids' => [$labInventory->id], 'user_id' => $firstTechnician->id],
        ['label' => 'Library', 'inventory_ids' => [$libraryInventory->id], 'user_id' => $secondTechnician->id],
        ['label' => 'Gym', 'inventory_ids' => [], 'user_id' => $firstTechnician->id],
    ]);

    expect($tasks)->toHaveCount(2)
        ->and($tasks->pluck('description')->all())->toBe(['PC check - Lab', 'PC check - Library'])
        ->and($tasks->pluck('user_id')->all())->toBe([$firstTechnician->id, $secondTechnician->id])
        ->and($tasks->every(fn (Task $task): bool => $task->scope_id === $scope->id && $task->checklist_template_id === $template->id))->toBeTrue()
        ->and($tasks->first()->inventories()->pluck('inventories.id')->all())->toBe([$labInventory->id]);
});

it('keeps the description of a single planned task', function () {
    $scope = Scope::factory()->create();

    $tasks = app(ChecklistPlanningService::class)->createTasks($scope->id, [
        'task_type_id' => TaskType::factory()->create()->id,
        'task_status_id' => TaskStatus::factory()->create()->id,
        'checklist_template_id' => ChecklistTemplate::factory()->create()->id,
        'starts_at' => '2026-10-05 08:00',
        'description' => 'PC check',
    ], [
        ['label' => 'Lab', 'inventory_ids' => [Inventory::factory()->create(['scope_id' => $scope->id])->id], 'user_id' => User::factory()->create()->id],
    ]);

    expect($tasks->sole()->description)->toBe('PC check');
});

it('reschedules a copy of the task with its inventories in the default status', function () {
    $completedStatus = TaskStatus::factory()->asCompleted()->create();
    $defaultStatus = TaskStatus::factory()->asDefault()->create();
    $task = Task::factory()->create([
        'checklist_template_id' => ChecklistTemplate::factory()->create()->id,
        'task_status_id' => $completedStatus->id,
        'note' => 'Filters every six months',
    ]);
    $inventories = Inventory::factory()->count(2)->create(['scope_id' => $task->scope_id]);
    $task->inventories()->attach($inventories);
    $technician = User::factory()->create();

    $copy = app(ChecklistPlanningService::class)->reschedule($task, [
        'starts_at' => '2027-03-01 09:00',
        'user_id' => $technician->id,
        'description' => 'Projector maintenance - March',
    ]);

    expect($copy->isNot($task))->toBeTrue()
        ->and($copy->only(['scope_id', 'task_type_id', 'checklist_template_id', 'note']))->toBe($task->only(['scope_id', 'task_type_id', 'checklist_template_id', 'note']))
        ->and($copy->task_status_id)->toBe($defaultStatus->id)
        ->and($copy->user_id)->toBe($technician->id)
        ->and((string) $copy->fresh()->starts_at)->toStartWith('2027-03-01 09:00')
        ->and($copy->inventories()->pluck('inventories.id')->all())->toEqualCanonicalizing($inventories->modelKeys())
        ->and($task->fresh()->task_status_id)->toBe($completedStatus->id);
});
