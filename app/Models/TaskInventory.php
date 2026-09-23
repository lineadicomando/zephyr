<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Inventory of a task, with the checklist filled for it.
 */
class TaskInventory extends Pivot
{
    protected $table = 'task_inventory';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'task_id',
        'inventory_id',
        'inventory_position_id',
        'position_path',
        'note',
        'photos',
        'has_anomalies',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'photos' => 'array',
        'has_anomalies' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Pivot columns to load with the task and inventory relationships.
     *
     * @var list<string>
     */
    public const PIVOT_COLUMNS = [
        'id',
        'inventory_position_id',
        'position_path',
        'note',
        'photos',
        'has_anomalies',
        'completed_at',
        'completed_by',
    ];

    protected static function booted(): void
    {
        static::creating(fn (TaskInventory $taskInventory) => $taskInventory->onCreating());
    }

    /**
     * Keep the position of the inventory when it is added to the task: the
     * checklist history must tell where the inventory was.
     */
    public function onCreating(): void
    {
        if ($this->inventory_position_id !== null) {
            return;
        }

        $stock = Stock::withoutGlobalScopes()
            ->with('inventory_position')
            ->where('inventory_id', $this->inventory_id)
            ->where('stock', '<>', 0)
            ->orderBy('id')
            ->first();

        $this->inventory_position_id = $stock?->inventory_position_id;
        $this->position_path = $stock?->inventory_position?->path;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * @return BelongsTo<InventoryPosition, $this>
     */
    public function inventory_position(): BelongsTo
    {
        return $this->belongsTo(InventoryPosition::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completed_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return HasMany<ChecklistResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(ChecklistResult::class, 'task_inventory_id')->orderBy('sort');
    }
}
