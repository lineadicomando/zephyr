<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->randomElement([
                'Maintenance', 'Installation', 'Inspection',
                'Repair', 'Replacement', 'Configuration', 'Upgrade',
            ]),
            'chart'       => fake()->boolean(60),
            'chart_color' => fake()->hexColor(),
        ];
    }
}
