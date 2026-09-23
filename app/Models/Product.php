<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * data in all of them, bypassing the panel tenant scope.
     */
    public function onSaved(): void
    {
        Inventory::withoutGlobalScopes()->where('product_id', $this->id)->chunkById(200, function (Collection $inventories) {
            $inventories->each(fn (Inventory $inventory) => $inventory->syncCopies());
        });
    }

    public function preventDeletionBy()
    {
        return [
            'inventories',
            'stocks',
        ];
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Tags inherited by the inventories of the product.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
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
}
