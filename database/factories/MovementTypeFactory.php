<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MovementTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->randomElement([
                'Purchase', 'Transfer', 'Disposal', 'Return', 'Loan',
                'Maintenance', 'Repair', 'Replacement',
            ]),
            'chart'       => fake()->boolean(40),
            'chart_color' => fake()->hexColor(),
        ];
    }
}
