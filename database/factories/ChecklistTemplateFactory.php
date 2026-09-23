<?php

namespace Database\Factories;

use App\Models\ChecklistTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChecklistTemplate> */
class ChecklistTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->optional(0.5)->sentence(),
            'is_active' => true,
        ];
    }
}
