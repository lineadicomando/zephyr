<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'name',
        'chart',
        'chart_color',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function preventDeletionBy()
    {
        return ['tasks'];
    }
}
