<?php

use App\Models\Inventory;
use App\Models\Task;
use App\Models\TaskInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Checklist of the inventory in a new task, completed at $completedAt
 * (null when still to do).
 */
function inventoryChecklist(Inventory $inventory, ?string $completedAt, bool $hasAnomalies = false): TaskInventory
{
    $task = Task::factory()->create(['scope_id' => $inventory->scope_id]);
    $task->inventories()->attach($inventory);

    $taskInventory = TaskInventory::query()->where('task_id', $task->id)->sole();
    $taskInventory->update(['completed_at' => $completedAt, 'has_anomalies' => $hasAnomalies]);

    return $taskInventory;
}

it('keeps the anomalies open until a later checklist completes without anomalies', function () {
    $inventory = Inventory::factory()->create();
    $anomalousChecklist = inventoryChecklist($inventory, '2026-09-01 10:00', hasAnomalies: true);

    expect(TaskInventory::query()->withOpenAnomalies()->pluck('id')->all())->toBe([$anomalousChecklist->id]);

    inventoryChecklist($inventory, null);

    expect(TaskInventory::query()->withOpenAnomalies()->pluck('id')->all())->toBe([$anomalousChecklist->id]);

    inventoryChecklist($inventory, '2026-10-01 10:00');

    expect(TaskInventory::query()->withOpenAnomalies()->exists())->toBeFalse();
});

it('reports the anomalies of the latest checklist even after older ones without anomalies', function () {
    $inventory = Inventory::factory()->create();
    inventoryChecklist($inventory, '2026-09-01 10:00');
    $anomalousChecklist = inventoryChecklist($inventory, '2026-10-01 10:00', hasAnomalies: true);

    expect(TaskInventory::query()->withOpenAnomalies()->pluck('id')->all())->toBe([$anomalousChecklist->id]);
});
