<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    use PreventRelatedDeletion;
    use HasFactory;

    protected $fillable = [
        'default',
        'completed',
        'icon',
        'color',
        'name',
    ];

    protected static function booted()
    {
        static::saved(fn (TaskStatus $taskStatus) => $taskStatus->onSaved());
    }

    public function onSaved()
    {

        if ($this->default) {
            self::where('id', '<>', $this->id)->update(['default' => false]);
        }

        if ($this->completed) {
            self::where('id', '<>', $this->id)->update(['completed' => false]);
        }
    }

    public static function getDefault(): TaskStatus|Null
    {
        return self::where('default', true)->first();
    }

    public static function getDefaultId(): Int|Null
    {
        $status = self::getDefault();
        if (!$status) {
            return null;
        }
        return (int) $status->id;
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function preventDeletionBy()
    {
        return ['tasks'];
    }

    public function permission_entities()
    {
        return $this->morphMany(PermissionEntity::class, 'entity');
    }
}
