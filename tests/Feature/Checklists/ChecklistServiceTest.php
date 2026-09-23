<?php

use App\Enums\ChecklistOutcome;
use App\Models\ChecklistResult;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Inventory;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskInventory;
use App\Models\User;
use App\Services\Checklists\ChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function checklistTaskInventory(ChecklistTemplate $template, ?Inventory $inventory = null): TaskInventory
{
    $task = Task::factory()->create(['checklist_template_id' => $template->id]);
    $inventory ??= Inventory::factory()->create(['scope_id' => $task->scope_id]);
    $task->inventories()->attach($inventory);

    return TaskInventory::query()->where('task_id', $task->id)->where('inventory_id', $inventory->id)->firstOrFail();
}

it('applies the items with tags only to the inventories having a tag, directly or through the product', function () {
    $lamp = Tag::factory()->create(['name' => 'lamp']);
    $template = ChecklistTemplate::factory()->create();
    $everyInventoryItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create();
    $lampItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->number(unit: 'h')->create();
    $lampItem->tags()->attach($lamp);

    $laserInventory = Inventory::factory()->create();
    $taggedInventory = Inventory::factory()->create();
    $taggedInventory->tags()->attach($lamp);
    $inheritingInventory = Inventory::factory()->create();
    $inheritingInventory->product->tags()->attach($lamp);

    $service = app(ChecklistService::class);

    expect($service->applicableItems($template, $laserInventory)->modelKeys())->toBe([$everyInventoryItem->id])
        ->and($service->applicableItems($template, $taggedInventory)->modelKeys())->toEqualCanonicalizing([$everyInventoryItem->id, $lampItem->id])
        ->and($service->applicableItems($template, $inheritingInventory)->modelKeys())->toEqualCanonicalizing([$everyInventoryItem->id, $lampItem->id]);
});

it('flags KO outcomes and numbers out of range as anomalies', function (array $answer, bool $isAnomaly) {
    $template = ChecklistTemplate::factory()->create();
    $outcomeItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create();
    $numberItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->number(min: 0, max: 3000, unit: 'h')->create();
    $taskInventory = checklistTaskInventory($template);

    $taskInventory = app(ChecklistService::class)->save($taskInventory, [
        $outcomeItem->id => ['value' => $answer['outcome']],
        $numberItem->id => ['value' => $answer['number']],
    ]);

    expect($taskInventory->has_anomalies)->toBe($isAnomaly)
        ->and($taskInventory->results()->where('is_anomaly', true)->exists())->toBe($isAnomaly);
})->with([
    'every answer in range' => [['outcome' => ChecklistOutcome::Ok->value, 'number' => '1200'], false],
    'not applicable outcome' => [['outcome' => ChecklistOutcome::NotApplicable->value, 'number' => '3000'], false],
    'KO outcome' => [['outcome' => ChecklistOutcome::Ko->value, 'number' => '1200'], true],
    'number above the maximum' => [['outcome' => ChecklistOutcome::Ok->value, 'number' => '3000.5'], true],
    'number below the minimum' => [['outcome' => ChecklistOutcome::Ok->value, 'number' => '-1'], true],
]);

it('completes the checklist when every required item has an answer', function () {
    $user = User::factory()->create();
    $template = ChecklistTemplate::factory()->create();
    $requiredItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create();
    ChecklistTemplateItem::factory()->for($template, 'checklist_template')->text()->optional()->create();
    $taskInventory = checklistTaskInventory($template);

    $taskInventory = app(ChecklistService::class)->save($taskInventory, [
        $requiredItem->id => ['value' => ChecklistOutcome::Ok->value],
    ], userId: $user->id);

    expect($taskInventory->isCompleted())->toBeTrue()
        ->and($taskInventory->completed_by)->toBe($user->id);
});

it('leaves the checklist to do when a required item has no answer', function () {
    $template = ChecklistTemplate::factory()->create();
    ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create();
    $optionalItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->text()->optional()->create();
    $taskInventory = checklistTaskInventory($template);

    $taskInventory = app(ChecklistService::class)->save($taskInventory, [
        $optionalItem->id => ['value' => 'Scratched case'],
    ]);

    expect($taskInventory->isCompleted())->toBeFalse()
        ->and($taskInventory->completed_by)->toBeNull();
});

it('keeps the answers readable after the template item is changed or deleted', function () {
    $template = ChecklistTemplate::factory()->create();
    $item = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->number(unit: 'h')->create(['label' => 'Lamp hours']);
    $taskInventory = checklistTaskInventory($template);
    app(ChecklistService::class)->save($taskInventory, [$item->id => ['value' => '1500']]);

    $item->update(['label' => 'Light source hours', 'unit' => 'hours']);
    $item->delete();

    $result = ChecklistResult::query()->sole();

    expect($result->label)->toBe('Lamp hours')
        ->and($result->formattedValue())->toBe('1500 h')
        ->and($result->checklist_template_item_id)->toBeNull();
});

it('reports incomplete checklists only for the inventories with applicable required items', function () {
    $lamp = Tag::factory()->create();
    $template = ChecklistTemplate::factory()->create();
    $lampItem = ChecklistTemplateItem::factory()->for($template, 'checklist_template')->create();
    $lampItem->tags()->attach($lamp);
    ChecklistTemplateItem::factory()->for($template, 'checklist_template')->text()->optional()->create();

    $task = Task::factory()->create(['checklist_template_id' => $template->id]);
    $laserInventory = Inventory::factory()->create(['scope_id' => $task->scope_id]);
    $task->inventories()->attach($laserInventory);

    $service = app(ChecklistService::class);

    expect($service->hasIncompleteChecklists($task))->toBeFalse();

    $lampInventory = Inventory::factory()->create(['scope_id' => $task->scope_id]);
    $lampInventory->tags()->attach($lamp);
    $task->inventories()->attach($lampInventory);

    expect($service->hasIncompleteChecklists($task))->toBeTrue();
});
