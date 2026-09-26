<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $level = fake()->randomElement([10, 11, 12]);
        $major = fake()->randomElement(['RPL', 'TKJ', 'MM', 'AKL']);
        $section = fake()->unique()->numberBetween(1, 99);

        return [
            'name' => "{$level} {$major} {$section}",
            'level' => $level,
        ];
    }
}
