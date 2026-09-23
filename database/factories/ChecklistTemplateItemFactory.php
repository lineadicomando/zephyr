<?php

namespace Database\Factories;

use App\Enums\ChecklistResponseType;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChecklistTemplateItem> */
class ChecklistTemplateItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'checklist_template_id' => ChecklistTemplate::factory(),
            'sort' => fake()->numberBetween(0, 20),
            'label' => fake()->sentence(3),
            'response_type' => ChecklistResponseType::Outcome,
            'is_required' => true,
        ];
    }

    public function optional(): static
    {
        return $this->state(fn () => ['is_required' => false]);
    }

    public function number(?float $min = null, ?float $max = null, ?string $unit = null): static
    {
        return $this->state(fn () => [
            'response_type' => ChecklistResponseType::Number,
            'min' => $min,
            'max' => $max,
            'unit' => $unit,
        ]);
    }

    /**
     * @param  list<string>  $options
     */
    public function choice(array $options): static
    {
        return $this->state(fn () => [
            'response_type' => ChecklistResponseType::Choice,
            'options' => $options,
        ]);
    }

    public function text(): static
    {
        return $this->state(fn () => ['response_type' => ChecklistResponseType::Text]);
    }
}
