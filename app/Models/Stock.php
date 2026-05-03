<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\HasDbCheck;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use BelongsToScope;
    use HasDbCheck;
    use HasFactory;

    protected $fillable = [
        'scope_id',
        'product_group_id',
        'product_type_id',
        'product_brand_id',
        'product_model_id',
        'product_id',
        'inventory_id',
        'inventory_location_id',
        'inventory_position_id',
        'inventory_summary',
        'stock',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Stock $stock) => $stock->onSaving());
        // static::saved(fn (Stock $stock) => $stock->onSaved());
    }

    public static function findAvailability(?int $inventoryId, ?int $positionId)
    {
        if (! $inventoryId) {
            return 0;
        }
        $query = self::where('inventory_id', $inventoryId)
            ->where('inventory_position_id', $positionId);

        return $query->value('stock');
    }

    // public function onSaved()
    // {
    // }

    public function onSaving()
    {
        // \Illuminate\Support\Facades\Log::debug('<<Stock::onSaving');
        $this->product_id = $this->inventory->product_id;
        $this->product_group_id = $this->inventory->product->product_group_id;
        $this->product_type_id = $this->inventory->product->product_type_id;
        $this->product_brand_id = $this->inventory->product->product_brand_id;
        $this->product_model_id = $this->inventory->product->product_model_id;
        $this->inventory_summary = $this->inventory->summary;
        $this->path = $this->stock;
        if (! empty($this->inventory_position?->name)) {
            $this->inventory_location_id = $this->inventory_position->inventory_location_id;
            $this->path = $this->inventory_position->path.': '.$this->stock;
            // \Illuminate\Support\Facades\Log::debug($this->path);
        }
        // \Illuminate\Support\Facades\Log::debug('Stock::onSaving>>');
    }

    // public function actualizePath(): void {
    //     if (!empty($this->inventory_position?->name)) {
    //         $this->inventory_location_id = $this->inventory_position->inventory_location_id;
    //         $this->path = $this->inventory_position->path . ': ' . $this->stock;
    //     }
    // }

    // public static function syncRelationsByPositionId(int $positionId): void {
    //     $stocks = self::where('inventory_position_id',$positionId)->get();
    //     foreach ($stocks as $stock) {
    //         $stock->save();
    //     }
    // }

    public function updateStockByMovementItems()
    {
        $incomingStockTotal = MovementItem::where('incoming_stock_id', $this->id)->sum('stock');
        $outcomingStockTotal = MovementItem::where('outcoming_stock_id', $this->id)->sum('stock');
        $stock = $incomingStockTotal - $outcomingStockTotal;
        // if ($stock != 0) {
        $this->update([
            'stock' => $stock,
        ]);
        // } else {
        //     $this->delete();
        // }
    }

    public static function dbCheck(bool $output = false): void
    {
        $stocks = self::all();
        $count = 0;
        $stocks->each(function (Stock $stock) use (&$count) {
            $stock->updateStockByMovementItems();
            $count++;
        });
        if ($output) {
            self::info('Check Stock: '.$count.': OK');
        }
    }

    public function product_group()
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function product_type()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function product_brand()
    {
        return $this->belongsTo(ProductBrand::class);
    }

    public function product_model()
    {
        return $this->belongsTo(ProductModel::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function inventory_location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function inventory_position()
    {
        return $this->belongsTo(InventoryPosition::class);
    }

    public function incoming_movement_items()
    {
        return $this->hasMany(MovementItem::class, 'incoming_stock_id');
    }

    public function outcoming_movement_items()
    {
        return $this->hasMany(MovementItem::class, 'outcoming_stock_id');
    }

    public function reorder()
    {
        return $this->hasOne(Reorder::class);
    }
}
