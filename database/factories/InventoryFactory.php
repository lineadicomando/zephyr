<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id'    => Product::factory(),
            'serial_number' => fake()->boolean(80) ? fake()->unique()->bothify('SN-####-???-####') : null,
            'mac_address'   => fake()->optional(0.3)->macAddress(),
            'description'   => fake()->optional(0.6)->sentence(4),
            'note'          => fake()->optional(0.2)->sentence(),
        ];
    }

    public function withMac(): static
    {
        return $this->state(fn () => ['mac_address' => fake()->macAddress()]);
    }
}
