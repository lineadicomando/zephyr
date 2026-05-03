<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->unique()->word(),
            'color'     => fake()->randomElement(['info', 'primary', 'warning', 'success', 'danger']),
            'icon'      => null,
            'order'     => fake()->numberBetween(1, 10),
            'default'   => false,
            'completed' => false,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(fn () => ['default' => true, 'completed' => false]);
    }

    public function asCompleted(): static
    {
        return $this->state(fn () => ['completed' => true, 'default' => false]);
    }
}
