<?php

namespace App\Models;

use Database\Factories\ScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_column_id', 'student_id', 'score', 'notes'])]
class Score extends Model
{
    /** @use HasFactory<ScoreFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    /**
     * Get the assessment column this score belongs to.
     *
     * @return BelongsTo<AssessmentColumn, $this>
     */
    public function assessmentColumn(): BelongsTo
    {
        return $this->belongsTo(AssessmentColumn::class);
    }

    /**
     * Get the student this score belongs to.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
