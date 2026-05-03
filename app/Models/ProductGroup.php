<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Stock;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductGroup extends Model
{
    use PreventRelatedDeletion;
    use HasFactory, SoftDeletes;

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
        return [
            'products',
            'stocks',
        ];
    }
}
