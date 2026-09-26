<?php

namespace Database\Factories;

use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentColumn>
 */
class AssessmentColumnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gradebook_id' => Gradebook::factory(),
            'name' => fake()->randomElement(['UH 1', 'UH 2', 'Tugas 1', 'Tugas Kelompok', 'Project Web', 'UTS', 'PAS']),
            'type' => fake()->randomElement(['uh', 'tugas', 'project', 'ujian']),
            'weight' => fake()->randomElement([10.00, 15.00, 20.00, 25.00, 30.00]),
            'max_score' => 100.00,
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
