<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function reorder_order_items()
    {
        return $this->hasMany(ReorderOrderItem::class);
    }
}
