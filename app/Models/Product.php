<?php

namespace App\Models;

use App\Models\Inventory;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

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

    public function onSaved()
    {
        $this->stocks()->each(function (Stock $stock) {
            $stock->update();
        });

        $this->inventories()->each(function (Inventory $inventory) {
            $inventory->syncSummary(true);
        });
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
