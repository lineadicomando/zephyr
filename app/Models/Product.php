<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'product_group_id',
        'product_type_id',
        'product_brand_id',
        'product_model_id',
        'code',
        'name',
        'note',
    ];

    protected static function booted(): void
    {
        // static::saving(fn (Product $product) => $product->onSaving());
        static::saved(fn (Product $product) => $product->onSaved());
    }

    // public function onSaving()
    // {
    // }

    /**
     * Products are shared by every scope: refresh the copies of the product
     * data in all of them, bypassing the panel tenant scope. The inventory
     * summaries are rebuilt first, since stocks and movement items copy them.
     */
    public function onSaved(): void
    {
        Stock::withoutGlobalScopes()->where('product_id', $this->id)->update([
            'product_group_id' => $this->product_group_id,
            'product_type_id' => $this->product_type_id,
            'product_brand_id' => $this->product_brand_id,
            'product_model_id' => $this->product_model_id,
        ]);

        Inventory::withoutGlobalScopes()->where('product_id', $this->id)->chunkById(200, function (Collection $inventories) {
            $inventories->each(function (Inventory $inventory) {
                $inventory->syncSummary(true);

                Stock::withoutGlobalScopes()->where('inventory_id', $inventory->id)->update(['inventory_summary' => $inventory->summary]);
                MovementItem::withoutGlobalScopes()->where('inventory_id', $inventory->id)->update(['inventory_summary' => $inventory->summary]);
            });
        });
    }

    public function preventDeletionBy()
    {
        return [
            'inventories',
            'stocks',
        ];
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
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
}
