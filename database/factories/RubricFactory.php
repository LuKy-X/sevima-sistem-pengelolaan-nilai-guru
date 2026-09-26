<?php

namespace Database\Factories;

use App\Models\AssessmentColumn;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rubric>
 */
class RubricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_column_id' => AssessmentColumn::factory(),
            'name' => 'Rubrik Penilaian '.fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }
}
