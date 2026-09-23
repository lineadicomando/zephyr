<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Reorder extends Model
{
    use BelongsToScope;
    use HasFactory;

    protected $fillable = [
        'scope_id',
        'stock_id',
        'reorder_point',
        'reorder_quantity',
        'last_reorder_date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Reorder $reorder) {
            if ((int) $reorder->reorder_point <= 0) {
                throw ValidationException::withMessages([
                    'reorder_point' => __('Reorder point must be greater than zero.'),
                ]);
            }

            if (! is_null($reorder->reorder_quantity) && (int) $reorder->reorder_quantity <= 0) {
                throw ValidationException::withMessages([
                    'reorder_quantity' => __('Reorder quantity must be greater than zero.'),
                ]);
            }
        });
    }

    /**
     * @return BelongsTo<Stock, $this>
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * @return HasMany<ReorderOrderItem, $this>
     */
    public function reorder_order_items(): HasMany
    {
        return $this->hasMany(ReorderOrderItem::class);
    }
}
