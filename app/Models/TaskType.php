<?php

namespace App\Models;

use App\Models\Task;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    use PreventRelatedDeletion;
    use HasFactory;


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
