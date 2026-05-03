<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReorderOrder extends Model
{
    use BelongsToScope;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'scope_id',
        'status',
        'notes',
        'requested_at',
        'ordered_at',
        'received_at',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReorderOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_REQUESTED,
            self::STATUS_ORDERED,
            self::STATUS_RECEIVED,
            self::STATUS_CANCELLED,
        ];
    }
}
