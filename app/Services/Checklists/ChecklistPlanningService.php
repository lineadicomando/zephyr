<?php

namespace App\Services\Checklists;

use App\Enums\ChecklistGrouping;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Plan the checklists of many inventories at once, splitting them into
 * tasks assigned to different technicians, and reschedule a task.
 */
class ChecklistPlanningService
{
    /**
     * Inventories of the scope matching every filled criterion, plus the
     * inventories selected one by one.
     *
     * @param  array{tag_ids?: list<int|string>, product_group_ids?: list<int|string>, product_type_ids?: list<int|string>, product_model_ids?: list<int|string>, location_ids?: list<int|string>, inventory_ids?: list<int|string>}  $criteria
     * @return Collection<int, Inventory>
     */
    public function matchingInventories(int $scopeId, array $criteria): Collection
    {
        $tagIds = array_filter($criteria['tag_ids'] ?? []);
        $productGroupIds = array_filter($criteria['product_group_ids'] ?? []);
        $productTypeIds = array_filter($criteria['product_type_ids'] ?? []);
        $productModelIds = array_filter($criteria['product_model_ids'] ?? []);
        $locationIds = array_filter($criteria['location_ids'] ?? []);
        $inventoryIds = array_filter($criteria['inventory_ids'] ?? []);

        $hasCriteria = $tagIds !== [] || $productGroupIds !== [] || $productTypeIds !== [] || $productModelIds !== [] || $locationIds !== [];

        if (! $hasCriteria && $inventoryIds === []) {
            return new Collection;
        }

        return Inventory::withoutGlobalScopes()
            ->where('scope_id', $scopeId)
            ->where(function (Builder $query) use ($hasCriteria, $tagIds, $productGroupIds, $productTypeIds, $productModelIds, $locationIds, $inventoryIds): void {
                if ($hasCriteria) {
                    $query->orWhere(function (Builder $query) use ($tagIds, $productGroupIds, $productTypeIds, $productModelIds, $locationIds): void {
                        if ($tagIds !== []) {
                            $query->where(fn (Builder $query): Builder => $query
                                ->whereHas('tags', fn (Builder $tags): Builder => $tags->whereKey($tagIds))
                                ->orWhereHas('product.tags', fn (Builder $tags): Builder => $tags->whereKey($tagIds)));
                        }

                        if ($productGroupIds !== []) {
                            $query->whereHas('product', fn (Builder $products): Builder => $products->whereIn('product_group_id', $productGroupIds));
                        }

                        if ($productTypeIds !== []) {
                            $query->whereHas('product', fn (Builder $products): Builder => $products->whereIn('product_type_id', $productTypeIds));
                        }

                        if ($productModelIds !== []) {
                            $query->whereHas('product', fn (Builder $products): Builder => $products->whereIn('product_model_id', $productModelIds));
                        }

                        if ($locationIds !== []) {
                            $query->whereHas('non_zero_stocks', fn (Builder $stocks): Builder => $stocks->withoutGlobalScopes()->whereIn('inventory_location_id', $locationIds));
                        }
                    });
                }

                if ($inventoryIds !== []) {
                    $query->orWhereKey($inventoryIds);
                }
            })
            ->orderBy('inventory_number')
            ->get();
    }

    /**
     * Split the inventories by their current location or position. The
     * inventories without stock go to a group of their own.
     *
     * @param  Collection<int, Inventory>  $inventories
     * @return list<array{label: string, inventory_ids: list<int>}>
     */
    public function groupInventories(Collection $inventories, ChecklistGrouping $grouping): array
    {
        if ($inventories->isEmpty()) {
            return [];
        }

        if ($grouping === ChecklistGrouping::Single) {
            return [['label' => '', 'inventory_ids' => $inventories->modelKeys()]];
        }

        $inventories->load(['non_zero_stocks' => fn ($stocks) => $stocks->withoutGlobalScopes()->with('inventory_position.inventory_location')]);

        return $inventories
            ->groupBy(function (Inventory $inventory) use ($grouping): string {
                /** @var Stock|null $stock */
                $stock = $inventory->non_zero_stocks->sortBy('id')->first();
                $position = $stock?->inventory_position;

                return match (true) {
                    $position === null => __('Without position'),
                    $grouping === ChecklistGrouping::Location => (string) $position->inventory_location?->name,
                    default => (string) $position->path,
                };
            })
            ->sortKeys()
            ->map(fn (Collection $group, string $label): array => ['label' => $label, 'inventory_ids' => $group->modelKeys()])
            ->values()
            ->all();
    }

    /**
     * Create a task for every group with inventories and a technician. The
     * group label is appended to the description when there are more groups.
     *
     * @param  array{task_type_id: int|string, task_status_id: int|string, checklist_template_id: int|string, starts_at: mixed, ends_at?: mixed, description: string, note?: ?string}  $taskData
     * @param  list<array{label?: ?string, inventory_ids?: list<int|string>, user_id?: int|string|null}>  $groups
     * @return Collection<int, Task>
     */
    public function createTasks(int $scopeId, array $taskData, array $groups): Collection
    {
        $groups = array_values(array_filter(
            $groups,
            fn (array $group): bool => filled($group['inventory_ids'] ?? []) && filled($group['user_id'] ?? null),
        ));

        return DB::transaction(fn (): Collection => new Collection(array_map(function (array $group) use ($scopeId, $taskData, $groups): Task {
            $task = Task::query()->create([
                'scope_id' => $scopeId,
                'task_type_id' => $taskData['task_type_id'],
                'task_status_id' => $taskData['task_status_id'],
                'checklist_template_id' => $taskData['checklist_template_id'],
                'user_id' => $group['user_id'],
                'starts_at' => $taskData['starts_at'],
                'ends_at' => $taskData['ends_at'] ?? null,
                'description' => count($groups) > 1 && filled($group['label'] ?? null)
                    ? "{$taskData['description']} - {$group['label']}"
                    : $taskData['description'],
                'note' => $taskData['note'] ?? null,
            ]);

            $task->inventories()->attach($group['inventory_ids']);

            return $task;
        }, $groups)));
    }

    /**
     * Copy of the task with the same type, checklist template, note and
     * inventories, in the default status (the status of the task when there
     * is no default status). The inventory positions are taken
     * again, as the inventories may have moved.
     *
     * @param  array{starts_at: mixed, ends_at?: mixed, user_id: int|string, description: string}  $data
     */
    public function reschedule(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            $copy = Task::query()->create([
                'scope_id' => $task->scope_id,
                'task_type_id' => $task->task_type_id,
                'task_status_id' => TaskStatus::getDefaultId() ?? $task->task_status_id,
                'checklist_template_id' => $task->checklist_template_id,
                'user_id' => $data['user_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'all_day' => $task->all_day,
                'description' => $data['description'],
                'note' => $task->note,
            ]);

            $copy->inventories()->attach($task->inventories()->withoutGlobalScopes()->pluck('inventories.id'));

            return $copy;
        });
    }
}
