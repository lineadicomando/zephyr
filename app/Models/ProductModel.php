<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModel extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

    protected $fillable = [
        'product_brand_id',
        'name',
    ];

    /**
     * @return BelongsTo<ProductBrand, $this>
     */
    public function product_brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class);
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
            'products',
            'stocks',
        ];
    }
}
