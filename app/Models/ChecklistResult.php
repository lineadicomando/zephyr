<?php

namespace App\Models;

use App\Enums\ChecklistOutcome;
use App\Enums\ChecklistResponseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistResult extends Model
{
    protected $fillable = [
        'task_inventory_id',
        'checklist_template_item_id',
        'sort',
        'label',
        'response_type',
        'unit',
        'is_required',
        'value',
        'note',
        'photos',
        'is_anomaly',
    ];

    protected $casts = [
        'response_type' => ChecklistResponseType::class,
        'is_required' => 'boolean',
        'photos' => 'array',
        'is_anomaly' => 'boolean',
    ];

    /**
     * @return BelongsTo<TaskInventory, $this>
     */
    public function task_inventory(): BelongsTo
    {
        return $this->belongsTo(TaskInventory::class, 'task_inventory_id');
    }

    /**
     * @return BelongsTo<ChecklistTemplateItem, $this>
     */
    public function checklist_template_item(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateItem::class);
    }

    /**
     * Value as shown to the users: outcome label, number with its unit.
     */
    public function formattedValue(): string
    {
        if (blank($this->value)) {
            return '';
        }

        return match ($this->response_type) {
            ChecklistResponseType::Outcome => ChecklistOutcome::tryFrom($this->value)?->getLabel() ?? $this->value,
            ChecklistResponseType::Number => trim("{$this->value} {$this->unit}"),
            default => $this->value,
        };
    }
}
