<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductModel;
use App\Models\Stock;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ProductBrand extends Model
{
    use PreventRelatedDeletion;
    use HasFactory;
    protected $fillable = [
        'name',
    ];

    public function product_models()
    {
        return $this->hasMany(ProductModel::class);
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
            'product_models',
            'products',
            'stocks',
        ];
    }

}
