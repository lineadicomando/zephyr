<?php

namespace App\Models;

use App\Models\ProductBrand;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use PreventRelatedDeletion;
    use HasFactory;

    protected $fillable = [
        'product_brand_id',
        'name',
    ];

    public function product_brand()
    {
        return $this->belongsTo(ProductBrand::class);
    }

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
        return [
            'products',
            'stocks',
        ];
    }
}
