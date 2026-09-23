<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

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
    }

    /**
     * @return BelongsToMany<Inventory, $this>
     */
    public function inventories(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'task_inventory')->withTimestamps();
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
