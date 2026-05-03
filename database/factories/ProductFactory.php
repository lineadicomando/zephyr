<?php

namespace Database\Factories;

use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $brand = ProductBrand::factory()->create();

        return [
            'product_group_id' => ProductGroup::factory(),
            'product_type_id'  => ProductType::factory(),
            'product_brand_id' => $brand->id,
            'product_model_id' => ProductModel::factory()->for($brand, 'product_brand'),
            'code'             => fake()->optional(0.7)->bothify('??-###-??'),
            'name'             => fake()->words(3, true),
            'note'             => fake()->optional(0.3)->sentence(),
        ];
    }
}
