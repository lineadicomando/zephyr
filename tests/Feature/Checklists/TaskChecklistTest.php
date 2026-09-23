<?php

use App\Enums\ChecklistOutcome;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Task;
use App\Models\TaskInventory;
use App\Models\TaskStatus;
use App\Services\Checklists\ChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function taskWithRequiredChecklist(): Task
{
    $template = ChecklistTemplate::factory()->create();
    ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create();

    $task = Task::factory()->create(['checklist_template_id' => $template->id]);
    $task->inventories()->attach(Inventory::factory()->create(['scope_id' => $task->scope_id]));

    return $task;
}

it('rejects the completed status while a required checklist is not filled', function () {
    $task = taskWithRequiredChecklist();
    $completedStatus = TaskStatus::factory()->asCompleted()->create();

    expect(fn () => $task->update(['task_status_id' => $completedStatus->id]))
        ->toThrow(ValidationException::class);

    expect($task->fresh()->task_status_id)->not->toBe($completedStatus->id);
});

it('accepts the completed status once the checklists are filled', function () {
    $task = taskWithRequiredChecklist();
    $completedStatus = TaskStatus::factory()->asCompleted()->create();
    $taskInventory = $task->task_inventories()->sole();
    $itemId = $task->checklist_template->items()->value('id');

    app(ChecklistService::class)->save($taskInventory, [$itemId => ['value' => ChecklistOutcome::Ok->value]]);
    $task->update(['task_status_id' => $completedStatus->id]);

    expect($task->fresh()->task_status_id)->toBe($completedStatus->id);
});

it('keeps the position of the inventory when it is added to the task', function () {
    $stock = Stock::factory()->create();
    $task = Task::factory()->create(['scope_id' => $stock->scope_id]);

    $task->inventories()->attach($stock->inventory_id);

    $taskInventory = TaskInventory::query()->sole();

    expect($taskInventory->inventory_position_id)->toBe($stock->inventory_position_id)
        ->and($taskInventory->position_path)->toBe($stock->inventory_position->path);
});
