<?php

namespace App\Services\Checklists;

use App\Models\ChecklistResult;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Inventory;
use App\Models\Task;
use App\Models\TaskInventory;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ChecklistService
{
    /**
     * Items of the template that apply to the inventory, according to the
     * tags of the inventory and of its product.
     *
     * @return Collection<int, ChecklistTemplateItem>
     */
    public function applicableItems(?ChecklistTemplate $template, Inventory $inventory): Collection
    {
        if ($template === null) {
            return new Collection;
        }

        $template->loadMissing('items.tags');
        $inventory->loadMissing('tags', 'product.tags');
        $inventoryTagIds = $inventory->effectiveTagIds();

        return $template->items
            ->filter(fn (ChecklistTemplateItem $item): bool => $item->appliesToTags($inventoryTagIds))
            ->values();
    }

    /**
     * Save the checklist of an inventory of a task. The item data are copied
     * in the results, which are flagged as anomalies when needed. The
     * checklist is completed when every required item has an answer.
     *
     * @param  array<int, array{value?: mixed, note?: ?string, photos?: ?array<string>}>  $answers  answers by template item id
     * @param  array{note?: ?string, photos?: ?array<string>}  $general  note and photos of the whole checklist
     */
    public function save(TaskInventory $taskInventory, array $answers, array $general = [], ?int $userId = null): TaskInventory
    {
        return DB::transaction(function () use ($taskInventory, $answers, $general, $userId): TaskInventory {
            $taskInventory->loadMissing('task.checklist_template', 'inventory');
            $items = $this->applicableItems($taskInventory->task->checklist_template, $taskInventory->inventory);

            $hasAnomalies = false;
            $isComplete = true;

            foreach ($items as $item) {
                $answer = $answers[$item->id] ?? [];
                $value = $answer['value'] ?? null;
                $value = $value instanceof BackedEnum ? $value->value : $value;
                $value = blank($value) ? null : (string) $value;
                $isAnomaly = $item->isAnomaly($value);

                $hasAnomalies = $hasAnomalies || $isAnomaly;
                $isComplete = $isComplete && ! ($item->is_required && $value === null);

                ChecklistResult::query()->updateOrCreate([
                    'task_inventory_id' => $taskInventory->id,
                    'checklist_template_item_id' => $item->id,
                ], [
                    'sort' => $item->sort,
                    'label' => $item->label,
                    'response_type' => $item->response_type,
                    'unit' => $item->unit,
                    'is_required' => $item->is_required,
                    'value' => $value,
                    'note' => $answer['note'] ?? null,
                    'photos' => array_values($answer['photos'] ?? []) ?: null,
                    'is_anomaly' => $isAnomaly,
                ]);
            }

            // Answers of the items that no longer apply, e.g. after changing
            // the template of the task. The answers of deleted items are kept.
            $taskInventory->results()->whereNotIn('checklist_template_item_id', $items->modelKeys())->delete();

            $taskInventory->update([
                'note' => $general['note'] ?? null,
                'photos' => array_values($general['photos'] ?? []) ?: null,
                'has_anomalies' => $hasAnomalies,
                'completed_at' => $isComplete ? now() : null,
                'completed_by' => $isComplete ? $userId : null,
            ]);

            return $taskInventory->unsetRelation('results');
        });
    }

    /**
     * Whether an inventory of the task has a checklist with required items
     * that is not completed.
     */
    public function hasIncompleteChecklists(Task $task): bool
    {
        $template = ChecklistTemplate::query()->with('items.tags')->find($task->checklist_template_id);

        if ($template === null || ! $template->items->contains('is_required', true) || ! $task->exists) {
            return false;
        }

        return $task->task_inventories()
            ->whereNull('completed_at')
            ->with('inventory.tags', 'inventory.product.tags')
            ->get()
            ->contains(fn (TaskInventory $taskInventory): bool => $this->applicableItems($template, $taskInventory->inventory)
                ->contains('is_required', true));
    }
}
