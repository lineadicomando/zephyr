<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class InventoryPosition extends Model
{
    use BelongsToScope;
    use HasFactory;
    use PreventRelatedDeletion;

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

        $this->path = ($this->inventory_location?->name ?? '').($this->default ? '' : ' \ '.$this->name);
        // \Illuminate\Support\Facades\Log::debug($this->path);
        // \Illuminate\Support\Facades\Log::debug('InventoryPosition::onSaving>>');
    }

    /**
     * Refresh the location and path copied by the stocks of the position with
     * a bulk update (the stock path is "<position path>: <quantity>").
     */
    public function onSaved()
    {
        $connection = $this->getConnection();
        $path = $connection->getPdo()->quote($this->path);

        Stock::withoutGlobalScopes()->where('inventory_position_id', $this->id)->update([
            'inventory_location_id' => $this->inventory_location_id,
            'path' => DB::raw($connection->getDriverName() === 'sqlite'
                ? "{$path} || ': ' || COALESCE(stock, 0)"
                : "CONCAT({$path}, ': ', COALESCE(stock, 0))"),
        ]);
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function inventory_location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * @return HasMany<Movement, $this>
     */
    public function from_movements(): HasMany
    {
        return $this->hasMany(Movement::class, 'from_inventory_position_id');
    }

    /**
     * @return HasMany<Movement, $this>
     */
    public function to_movements(): HasMany
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
