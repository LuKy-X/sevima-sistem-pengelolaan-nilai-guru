<?php

namespace Database\Factories;

use App\Models\Rubric;
use App\Models\RubricCriterion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<RubricCriterion>
 */
class RubricCriterionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rubric_id' => Rubric::factory(),
            'name' => fake()->randomElement(['Kualitas Kode', 'Fungsionalitas', 'UI/UX', 'Presentasi', 'Kerapian', 'Ketepatan Waktu']),
            'description' => fake()->sentence(),
            'weight' => 25.00,
            'order' => fake()->numberBetween(1, 5),
        ];
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return Collection<int, RubricCriterion>|RubricCriterion
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $previous = RubricCriterion::$autoCreateLevels;
        RubricCriterion::$autoCreateLevels = false;

        try {
            return parent::create($attributes, $parent);
        } finally {
            RubricCriterion::$autoCreateLevels = $previous;
        }
    }

    /**
     * Indicate that the criterion should be created with default levels.
     */
    public function withLevels(): static
    {
        return $this->afterCreating(function (RubricCriterion $criterion) {
            $criterion->createDefaultLevels();
        });
    }
}
