<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use HasFactory, SoftDeletes;
    use PreventRelatedDeletion;

    protected $fillable = [
        'name',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function preventDeletionBy()
    {
        return ['products', 'stocks'];
    }

    protected static function booted(): void
    {
        static::saved(fn (ProductType $productType) => $productType->onSaved());
    }

    public function onSaved()
    {
        $products = Product::where('product_type_id', $this->id)->get();
        $products->each(function (Product $product) {
            $product->inventories()->each(function (Inventory $inventory) {
                $inventory->syncSummary(true);
            });
        });
    }
}
