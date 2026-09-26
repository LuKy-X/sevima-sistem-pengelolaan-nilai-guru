<?php

namespace App\Models;

use Database\Factories\RubricLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rubric_criterion_id', 'level_number', 'name', 'score', 'description'])]
class RubricLevel extends Model
{
    /** @use HasFactory<RubricLevelFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
            'score' => 'float',
        ];
    }

    /**
     * Get the criterion this performance level belongs to.
     *
     * @return BelongsTo<RubricCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    /**
     * Get all student rubric scores referencing this level.
     *
     * @return HasMany<StudentRubricScore, $this>
     */
    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentRubricScore::class);
    }
}
