<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskType extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'name',
        'chart',
        'chart_color',
    ];

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function preventDeletionBy()
    {
        return ['tasks'];
    }
}
