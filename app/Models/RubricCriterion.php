<?php

namespace App\Models;

use Database\Factories\RubricCriterionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rubric_id', 'name', 'description', 'weight', 'order'])]
class RubricCriterion extends Model
{
    /** @use HasFactory<RubricCriterionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'order' => 'integer',
        ];
    }

    /**
     * Get the rubric this criterion belongs to.
     *
     * @return BelongsTo<Rubric, $this>
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    /**
     * Get the 4 performance levels for this criterion.
     *
     * @return HasMany<RubricLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(RubricLevel::class)->orderBy('level_number');
    }

    /**
     * Whether default levels should be automatically created upon model creation.
     */
    public static bool $autoCreateLevels = true;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (RubricCriterion $criterion) {
            if (static::$autoCreateLevels) {
                $criterion->createDefaultLevels();
            }
        });
    }

    /**
     * Create the standard 4 performance levels for this criterion if not already present.
     */
    public function createDefaultLevels(): void
    {
        if ($this->levels()->exists()) {
            return;
        }

        $defaultLevels = [
            [
                'level_number' => 1,
                'name' => 'Perlu Bimbingan',
                'score' => 25.00,
                'description' => 'Siswa belum memenuhi kriteria dan memerlukan bimbingan intensif.',
            ],
            [
                'level_number' => 2,
                'name' => 'Cukup',
                'score' => 50.00,
                'description' => 'Siswa cukup memenuhi kriteria dengan beberapa kekurangan.',
            ],
            [
                'level_number' => 3,
                'name' => 'Baik',
                'score' => 75.00,
                'description' => 'Siswa memenuhi kriteria dengan baik dan konsisten.',
            ],
            [
                'level_number' => 4,
                'name' => 'Sangat Baik',
                'score' => 100.00,
                'description' => 'Siswa melampaui kriteria dengan kualitas yang sangat baik.',
            ],
        ];

        foreach ($defaultLevels as $levelData) {
            $this->levels()->create($levelData);
        }
    }

    /**
     * Get all student rubric scores recorded for this criterion.
     *
     * @return HasMany<StudentRubricScore, $this>
     */
    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentRubricScore::class);
    }
}
