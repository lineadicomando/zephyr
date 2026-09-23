<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\HasDbCheck;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Stock extends Model
{
    use BelongsToScope;
    use HasDbCheck;
    use HasFactory;
    use PreventRelatedDeletion;

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

    /**
     * Movement and order history must not lose its stock: the reorder rule
     * is only configuration and is deleted together with the stock.
     *
     * @return list<string>
     */
    public function preventDeletionBy(): array
    {
        return [
            'incoming_movement_items',
            'outcoming_movement_items',
            'reorder_order_items',
        ];
    }

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

    /**
     * Recompute the stock from the movement items. A change that would make the
     * stock negative (or more negative than it already is) is rejected, unless
     * $allowNegative is true (used to repair the stored values).
     *
     * The stock row is locked first, so concurrent recalculations of the same
     * stock run one after the other; with the READ COMMITTED isolation level
     * each one sees the movement items committed by the previous one.
     *
     * @throws ValidationException
     */
    public function updateStockByMovementItems(bool $allowNegative = false): void
    {
        $storedStock = (int) DB::table('stocks')->where('id', $this->id)->lockForUpdate()->value('stock');

        $incomingStockTotal = MovementItem::where('incoming_stock_id', $this->id)->sum('stock');
        $outcomingStockTotal = MovementItem::where('outcoming_stock_id', $this->id)->sum('stock');
        $stock = $incomingStockTotal - $outcomingStockTotal;

        if (! $allowNegative && $stock < 0 && $stock < $storedStock) {
            throw ValidationException::withMessages([
                'stock' => __('Insufficient availability, impossible to proceed'),
            ]);
        }

        $this->update([
            'stock' => $stock,
        ]);
    }

    public static function dbCheck(bool $output = false): void
    {
        $count = 0;
        self::query()->chunkById(200, function (Collection $stocks) use (&$count) {
            $stocks->each(fn (Stock $stock) => $stock->updateStockByMovementItems(allowNegative: true));
            $count += $stocks->count();
        });
        if ($output) {
            self::info('Check Stock: '.$count.': OK');
        }
    }

    /**
     * @return BelongsTo<ProductGroup, $this>
     */
    public function product_group(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    /**
     * @return BelongsTo<ProductType, $this>
     */
    public function product_type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * @return BelongsTo<ProductBrand, $this>
     */
    public function product_brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class);
    }

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product_model(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function inventory_location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    /**
     * @return BelongsTo<InventoryPosition, $this>
     */
    public function inventory_position(): BelongsTo
    {
        return $this->belongsTo(InventoryPosition::class);
    }

    /**
     * @return HasMany<MovementItem, $this>
     */
    public function incoming_movement_items(): HasMany
    {
        return $this->hasMany(MovementItem::class, 'incoming_stock_id');
    }

    /**
     * @return HasMany<MovementItem, $this>
     */
    public function outcoming_movement_items(): HasMany
    {
        return $this->hasMany(MovementItem::class, 'outcoming_stock_id');
    }

    /**
     * @return HasOne<Reorder, $this>
     */
    public function reorder(): HasOne
    {
        return $this->hasOne(Reorder::class);
    }

    /**
     * @return HasMany<ReorderOrderItem, $this>
     */
    public function reorder_order_items(): HasMany
    {
        return $this->hasMany(ReorderOrderItem::class);
    }
}
