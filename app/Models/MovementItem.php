<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovementItem extends Model
{
    use BelongsToScope;
    use HasFactory;

    protected $oldOutcomingStockId = null;

    protected $oldIncomingStockId = null;

    protected $fillable = [
        'scope_id',
        'movement_id',
        'inventory_id',
        'outcoming_stock_id',
        'incoming_stock_id',
        'inventory_summary',
        'stock',
    ];

    public function isLast(): bool
    {
        $count = self::where('id', '>', $this->id)->where('inventory_id', $this->inventory_id)->count();
        if ($count == 0) {
            return true;
        }

        return false;
    }

    /**
     * Save in a transaction: the stock update after saving may reject the change.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    /**
     * Delete in a transaction: the stock update after deleting may reject the change.
     */
    public function delete(): ?bool
    {
        return DB::transaction(fn (): ?bool => parent::delete());
    }

    protected static function booted(): void
    {
        static::saving(fn (MovementItem $movementItem) => $movementItem->onSaving());
        static::saved(fn (MovementItem $movementItem) => $movementItem->onSaved());
        static::deleted(fn (MovementItem $movementItem) => $movementItem->onDeleted());
    }

    // public static function findStock(Int $inventoryId, Int|Null $positionId)
    // {
    //     $stockId = self::findStockId($inventoryId, $positionId);
    //     if (empty($stockId)) {
    //         return null;
    //     }
    //     return Stock::find($stockId);
    // }

    public static function findStockId(int $inventoryId, ?int $positionId, ?int $scopeId = null)
    {
        $stockRecord = [
            'inventory_id' => $inventoryId,
            // 'inventory_location_id' => $locationId,
            'inventory_position_id' => $positionId,
        ];

        if ($scopeId !== null) {
            $stockRecord['scope_id'] = $scopeId;
        }

        $stock = Stock::firstOrCreate($stockRecord);
        if ($stock) {
            return $stock->id;
        }

        return null;
    }

    public function syncStocks()
    {
        $scopeId = $this->scope_id ?? $this->movement?->scope_id ?? null;

        $outcomingStockId = null;
        if (! empty($this->movement->from_inventory_location_id)) {
            $outcomingStockId = self::findStockId(
                inventoryId: $this->inventory_id,
                positionId: $this->movement->from_inventory_position_id,
                scopeId: $scopeId
            );
        }
        if (! empty($this->outcoming_stock_id) && $outcomingStockId != $this->outcoming_stock_id) {
            $this->oldOutcomingStockId = $this->outcoming_stock_id;
        }
        $this->outcoming_stock_id = $outcomingStockId;

        $incomingStockId = null;
        if (! empty($this->movement->to_inventory_location_id)) {
            $incomingStockId = self::findStockId(
                inventoryId: $this->inventory_id,
                positionId: $this->movement->to_inventory_position_id,
                scopeId: $scopeId
            );
        }
        if (! empty($this->incoming_stock_id) && $incomingStockId != $this->incoming_stock_id) {
            $this->oldIncomingStockId = $this->incoming_stock_id;
        }
        $this->incoming_stock_id = $incomingStockId;
    }

    public function syncSummary()
    {
        $this->inventory_summary = $this->inventory?->summary ?? '';
    }

    public function onSaving()
    {
        $this->syncStocks();
        $this->syncSummary();
    }

    public function onSaved()
    {
        $this->updateStock();

        return true;
    }

    public function onDeleted()
    {
        $this->updateStock();

        return true;
    }

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function updateStock()
    {
        if (! empty($this->incoming_stock)) {
            $this->incoming_stock->updateStockByMovementItems();
        }
        if (! empty($this->outcoming_stock)) {
            $this->outcoming_stock->updateStockByMovementItems();
        }
        if (! empty($this->oldIncomingStockId)) {
            $stock = Stock::find($this->oldIncomingStockId);
            if ($stock) {
                $stock->updateStockByMovementItems();
            }
        }
        if (! empty($this->oldOutcomingStockId)) {
            $stock = Stock::find($this->oldOutcomingStockId);
            if ($stock) {
                $stock->updateStockByMovementItems();
            }
        }
    }

    public function incoming_stock()
    {
        return $this->belongsTo(Stock::class, 'incoming_stock_id');
    }

    public function outcoming_stock()
    {
        return $this->belongsTo(Stock::class, 'outcoming_stock_id');
    }
}
