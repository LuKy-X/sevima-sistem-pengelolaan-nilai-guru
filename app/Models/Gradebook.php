<?php

namespace App\Models;

use Database\Factories\GradebookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['user_id', 'classroom_id', 'subject_id', 'academic_year_id', 'semester', 'title'])]
class Gradebook extends Model
{
    /** @use HasFactory<GradebookFactory> */
    use HasFactory;

    /**
     * Get the teacher / owner of this gradebook.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relation (teacher).
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the classroom associated with this gradebook.
     *
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the subject associated with this gradebook.
     *
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the academic year of this gradebook.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the dynamic assessment columns configured for this gradebook.
     *
     * @return HasMany<AssessmentColumn, $this>
     */
    public function assessmentColumns(): HasMany
    {
        return $this->hasMany(AssessmentColumn::class)->orderBy('order');
    }

    /**
     * Get all scores through the assessment columns.
     *
     * @return HasManyThrough<Score, AssessmentColumn, $this>
     */
    public function scores(): HasManyThrough
    {
        return $this->hasManyThrough(Score::class, AssessmentColumn::class);
    }
}
