<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
    use BelongsToScope;
    use HasFactory;
    use PreventRelatedDeletion;

    protected static function booted(): void
    {
        static::saved(fn (InventoryLocation $inventoryLocation) => $inventoryLocation->onSaved());
        // The default position belongs to the location: it goes with it.
        static::deleted(fn (InventoryLocation $inventoryLocation) => $inventoryLocation->inventory_positions()->where('default', true)->delete());
    }

    public function onSaved()
    {
        $this->defaultPosition();
        $inventoryPositions = $this->inventory_positions();
        $inventoryPositions->each(fn (InventoryPosition $inventoryPosition) => $inventoryPosition->save());
    }

    public function defaultPosition()
    {
        return $this->inventory_positions()->firstOrCreate([
            'scope_id' => $this->scope_id,
            'name' => 'default',
            'default' => true,
        ]);
    }

    protected $fillable = [
        'scope_id',
        'name',
    ];

    public function inventory_positions()
    {
        return $this->hasMany(InventoryPosition::class);
    }

    /**
     * Positions created by the users: the default position, created with the
     * location, does not prevent its deletion.
     */
    public function custom_positions()
    {
        return $this->inventory_positions()->where('default', false);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function from_movements()
    {
        return $this->hasMany(Movement::class, 'from_inventory_location_id');
    }

    public function to_movements()
    {
        return $this->hasMany(Movement::class, 'to_inventory_location_id');
    }

    public function preventDeletionBy()
    {
        return [
            'custom_positions',
            'stocks',
            'from_movements',
            'to_movements',
        ];
    }
}
