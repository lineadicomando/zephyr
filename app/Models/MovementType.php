<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementType extends Model
{
    use BelongsToScope;
    use HasFactory, SoftDeletes;
    use PreventRelatedDeletion;

    protected $fillable = [
        'scope_id',
        'chart',
        'chart_color',
        'name',
    ];

    /**
     * @return HasMany<Movement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function preventDeletionBy()
    {
        return ['movements'];
    }
}
