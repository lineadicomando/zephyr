<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<ChecklistTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<TaskType, $this>
     */
    public function task_types(): HasMany
    {
        return $this->hasMany(TaskType::class);
    }

    public function preventDeletionBy()
    {
        return [
            'tasks',
            'task_types',
        ];
    }
}
