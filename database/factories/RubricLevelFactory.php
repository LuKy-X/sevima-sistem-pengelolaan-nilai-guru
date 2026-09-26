<?php

namespace Database\Factories;

use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RubricLevel>
 */
class RubricLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rubric_criterion_id' => RubricCriterion::factory(),
            'level_number' => 1,
            'name' => 'Perlu Bimbingan',
            'score' => 25.00,
            'description' => fake()->sentence(),
        ];
    }
}
