<?php

namespace App\Models;

use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBrand extends Model
{
    use HasFactory;
    use PreventRelatedDeletion;

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
