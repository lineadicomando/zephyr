<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use BelongsToScope;
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'scope_id',
        'date',
        'movement_type_id',
        'from_inventory_location_id',
        'from_inventory_position_id',
        'to_inventory_location_id',
        'to_inventory_position_id',
        'description',
        'note',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Movement $movement) => $movement->onSaving());
        static::saved(fn (Movement $movement) => $movement->onSaved());
        static::deleting(fn (Movement $movement) => $movement->onDeleted());
    }

    public function onSaving()
    {
        if ($this->to_inventory_position_id && !$this->to_inventory_location_id) {
            $this->to_inventory_location_id = $this->to_inventory_position?->inventory_location_id;
        }
        if ($this->from_inventory_position_id && !$this->from_inventory_location_id) {
            $this->from_inventory_location_id = $this->from_inventory_position?->inventory_location_id;
        }
    }

    public function onSaved()
    {
        $this->movement_items->each(function (MovementItem $movementItem) {
            $movementItem->save();
        });
    }

    public function onDeleted()
    {
        $this->movement_items->each(function (MovementItem $movementItem) {
            $movementItem->delete();
        });
    }

    public function movement_type()
    {
        return $this->belongsTo(MovementType::class);
    }

    public function movement_items()
    {
        return $this->hasMany(MovementItem::class);
    }

    public function preventDeletionBy()
    {
        return [];
    }

    public function from_inventory_location()
    {
        return $this->belongsTo(InventoryLocation::class, 'from_inventory_location_id');
    }

    public function from_inventory_position()
    {
        return $this->belongsTo(InventoryPosition::class, 'from_inventory_position_id');
    }

    public function to_inventory_location()
    {
        return $this->belongsTo(InventoryLocation::class, 'to_inventory_location_id');
    }

    public function to_inventory_position()
    {
        return $this->belongsTo(InventoryPosition::class, 'to_inventory_position_id');
        // return $this->belongsTo(InventoryPosition::class, 'to_inventory_position_id')->where('default', false);
    }
}
