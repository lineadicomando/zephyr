<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBrand extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<ProductModel, $this>
     */
    public function product_models(): HasMany
    {
        return $this->hasMany(ProductModel::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function preventDeletionBy()
    {
        return [
            'product_models',
            'products',
            'stocks',
        ];
    }
}
