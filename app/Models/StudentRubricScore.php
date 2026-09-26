<?php

namespace App\Models;

use Database\Factories\StudentRubricScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'rubric_criterion_id', 'rubric_level_id', 'score'])]
class StudentRubricScore extends Model
{
    /** @use HasFactory<StudentRubricScoreFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    /**
     * Get the student this rubric score belongs to.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the rubric criterion this score evaluates.
     *
     * @return BelongsTo<RubricCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    /**
     * Get the selected rubric performance level.
     *
     * @return BelongsTo<RubricLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(RubricLevel::class, 'rubric_level_id');
    }
}
