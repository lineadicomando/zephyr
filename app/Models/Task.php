<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Services\Checklists\ChecklistService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @property-read int|null $task_inventories_count
 * @property-read int|null $completed_checklists_count checklists completed, loaded by the task list
 * @property-read int|null $anomalous_checklists_count checklists with anomalies, loaded by the task list
 */
class Task extends Model
{
    use BelongsToScope;
    use HasFactory;

    protected $fillable = [
        'scope_id',
        'starts_at',
        'ends_at',
        'all_day',
        'task_type_id',
        'task_status_id',
        'checklist_template_id',
        'user_id',
        'description',
        'note',
    ];

    protected static function booted()
    {
        static::saving(fn (Task $task) => $task->onSaving());
    }

    public function onSaving()
    {
        if (empty($this->user_id)) {
            $this->user_id = Auth::user()?->id;
        }

        $this->ensureChecklistsCompletedBeforeClosing();
    }

    /**
     * A task cannot move to the completed status while a checklist with
     * required items is not filled.
     *
     * @throws ValidationException
     */
    public function ensureChecklistsCompletedBeforeClosing(): void
    {
        if (! $this->isDirty('task_status_id') || ! TaskStatus::query()->whereKey($this->task_status_id)->where('completed', true)->exists()) {
            return;
        }

        if (app(ChecklistService::class)->hasIncompleteChecklists($this)) {
            throw ValidationException::withMessages([
                'task_status_id' => __('The task cannot be completed until every checklist with required items is filled.'),
            ]);
        }
    }

    /**
     * @return BelongsToMany<Inventory, $this, TaskInventory>
     */
    public function inventories(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'task_inventory')
            ->using(TaskInventory::class)
            ->withPivot(TaskInventory::PIVOT_COLUMNS)
            ->withTimestamps();
    }

    /**
     * @return HasMany<TaskInventory, $this>
     */
    public function task_inventories(): HasMany
    {
        return $this->hasMany(TaskInventory::class);
    }

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function checklist_template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    /**
     * @return BelongsTo<TaskType, $this>
     */
    public function task_type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    /**
     * @return BelongsTo<TaskStatus, $this>
     */
    public function task_status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
