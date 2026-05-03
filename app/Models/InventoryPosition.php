<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryPosition extends Model
{
    use BelongsToScope;
    use PreventRelatedDeletion;
    use HasFactory;

    protected $fillable = [
        'scope_id',
        'inventory_location_id',
        'default',
        'name',
    ];

    protected static function booted(): void
    {
        static::saving(fn (InventoryPosition $inventoryPosition) => $inventoryPosition->onSaving());
        static::saved(fn (InventoryPosition $inventoryPosition) => $inventoryPosition->onSaved());
    }

    public function onSaving()
    {
        // \Illuminate\Support\Facades\Log::debug('<<InventoryPosition::onSaving');
        if ($this->inventory_location_id && empty($this->scope_id)) {
            $this->scope_id = InventoryLocation::query()
                ->whereKey($this->inventory_location_id)
                ->value('scope_id');
        }

        $this->path = ($this->inventory_location?->name ?? '') . ($this->default ? '' :  ' \ ' . $this->name);
        // \Illuminate\Support\Facades\Log::debug($this->path);
        // \Illuminate\Support\Facades\Log::debug('InventoryPosition::onSaving>>');
    }

    public function onSaved()
    {
        // \Illuminate\Support\Facades\Log::debug('<<InventoryPosition::onSaved');
        $stocks = Stock::where('inventory_position_id', $this->id)->get();
        $stocks->each(function (Stock $stock) {
            $stock->save();
        });
        // \Illuminate\Support\Facades\Log::debug('InventoryPosition::onSaved>>');
    }

    public function inventory_location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function from_movements()
    {
        return $this->hasMany(Movement::class, 'from_inventory_position_id');
    }

    public function to_movements()
    {
        return $this->hasMany(Movement::class, 'to_inventory_position_id');
    }

    public function preventDeletionBy()
    {
        return [
            'stocks',
            'from_movements',
            'to_movements',
        ];
    }
}
