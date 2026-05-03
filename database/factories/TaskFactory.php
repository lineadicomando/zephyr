<?php

namespace Database\Factories;

use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-3 months', '+1 month');
        $hasEnd   = fake()->boolean(50);

        return [
            'starts_at'      => $startsAt,
            'ends_at'        => $hasEnd ? fake()->dateTimeBetween($startsAt, '+2 months') : null,
            'all_day'        => fake()->boolean(20),
            'task_type_id'   => TaskType::factory(),
            'task_status_id' => TaskStatus::factory(),
            'user_id'        => User::factory(),
            'description'    => fake()->sentence(6),
            'note'           => fake()->optional(0.3)->sentence(),
        ];
    }

    public function completed(TaskStatus $status): static
    {
        return $this->state(fn () => ['task_status_id' => $status->id]);
    }
}
