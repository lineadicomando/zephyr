<?php

namespace App\Models;

use App\Models\Inventory;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Concerns\BelongsToScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function inventories()
    {
        return $this->belongsToMany(Inventory::class, 'task_inventory')->withTimestamps();
    }


    public function task_type()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function task_status()
    {
        return $this->belongsTo(TaskStatus::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
