<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderOrderItem extends Model
{
    use BelongsToScope;
    use HasFactory;

    protected $fillable = [
        'scope_id',
        'reorder_order_id',
        'stock_id',
        'reorder_id',
        'current_stock',
        'reorder_point',
        'suggested_qty',
        'ordered_qty',
        'received_qty',
        'last_reorder_date',
    ];

    protected $casts = [
        'last_reorder_date' => 'datetime',
    ];

    public function reorderOrder(): BelongsTo
    {
        return $this->belongsTo(ReorderOrder::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function reorder(): BelongsTo
    {
        return $this->belongsTo(Reorder::class);
    }
}
