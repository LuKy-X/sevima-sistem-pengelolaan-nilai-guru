<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gradebook>
 */
class GradebookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'semester' => fake()->randomElement(['ganjil', 'genap']),
            'title' => fake()->optional()->words(3, true),
        ];
    }
}
