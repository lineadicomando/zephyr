<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Models\Movement;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementType extends Model
{
    use BelongsToScope;
    use PreventRelatedDeletion;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'scope_id',
        'chart',
        'chart_color',
        'name',
    ];

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function preventDeletionBy()
    {
        return ['movements'];
    }
}
