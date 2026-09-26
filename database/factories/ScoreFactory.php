<?php

namespace Database\Factories;

use App\Models\AssessmentColumn;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
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
            'student_id' => Student::factory(),
            'score' => fake()->randomFloat(2, 60, 100),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
