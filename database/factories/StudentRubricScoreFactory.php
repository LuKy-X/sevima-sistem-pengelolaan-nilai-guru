<?php

namespace Database\Factories;

use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use App\Models\Student;
use App\Models\StudentRubricScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRubricScore>
 */
class StudentRubricScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'rubric_criterion_id' => RubricCriterion::factory(),
            'rubric_level_id' => RubricLevel::factory(),
        ];
    }
}
