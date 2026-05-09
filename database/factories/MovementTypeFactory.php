<?php

namespace Database\Factories;

use App\Models\Scope;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope_id' => Scope::factory(),
            'name' => fake()->unique()->randomElement([
                'Purchase', 'Transfer', 'Disposal', 'Return', 'Loan',
                'Maintenance', 'Repair', 'Replacement',
            ]),
            'chart' => fake()->boolean(40),
            'chart_color' => fake()->hexColor(),
        ];
    }
}
