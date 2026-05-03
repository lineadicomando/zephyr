<?php

namespace Database\Factories;

use App\Models\ProductBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_brand_id' => ProductBrand::factory(),
            'name'             => fake()->unique()->bothify('Model-###??'),
        ];
    }
}
