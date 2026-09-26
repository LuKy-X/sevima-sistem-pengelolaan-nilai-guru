<?php

namespace App\Models;

use Database\Factories\GradebookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Get students enrolled in this gradebook's classroom and academic year.
     *
     * @return Collection<int, Student>
     */
    public function getEnrolledStudents()
    {
        $students = $this->classroom
            ?->students()
            ->wherePivot('academic_year_id', $this->academic_year_id)
            ->orderBy('name')
            ->get();

        if (($students === null || $students->isEmpty()) && $this->classroom) {
            $students = $this->classroom->students()->orderBy('name')->get();
        }

        return $students ?? new Collection;
    }

    /**
     * Calculate the average score for a student across all dynamic assessment columns in this gradebook.
     */
    public function calculateStudentAverage(int $studentId): ?float
    {
        $scores = $this->scores()
            ->where('student_id', $studentId)
            ->whereNotNull('score')
            ->pluck('score');

        if ($scores->isEmpty()) {
            return null;
        }

        return round((float) $scores->average(), 2);
    }

    /**
     * Calculate the weighted final score for a student in this gradebook.
     */
    public function calculateStudentFinalGrade(int $studentId): ?float
    {
        $summary = $this->getStudentGradeSummary($studentId);

        return $summary['final_score'];
    }

    /**
     * Calculate weighted final grade and detailed progress summary for a student.
     *
     * @return array{
     *     final_score: float|null,
     *     is_complete: bool,
     *     completed_count: int,
     *     total_columns: int,
     *     completed_weight: float,
     *     total_weight: float,
     *     breakdown: array<int, array{column_id: int, column_name: string, raw_score: float|null, max_score: float, normalized_score: float|null, weight: float, weighted_score: float|null}>
     * }
     */
    public function getStudentGradeSummary(int $studentId): array
    {
        $columns = $this->assessmentColumns;
        if ($columns->isEmpty()) {
            return [
                'final_score' => null,
                'is_complete' => true,
                'completed_count' => 0,
                'total_columns' => 0,
                'completed_weight' => 0.0,
                'total_weight' => 0.0,
                'breakdown' => [],
            ];
        }

        $scores = $this->scores()
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('assessment_column_id');

        $weightedSum = 0.0;
        $availableWeightSum = 0.0;
        $totalConfiguredWeight = (float) $columns->sum('weight');
        $completedCount = 0;
        $breakdown = [];

        foreach ($columns as $column) {
            $scoreModel = $scores->get($column->id);
            $rawScore = $scoreModel?->score !== null ? (float) $scoreModel->score : null;
            $weight = (float) $column->weight;
            $maxScore = (float) ($column->max_score > 0 ? $column->max_score : 100.0);

            $normalizedScore = null;
            $weightedScore = null;

            // Note: 0 is a valid score, not treated as missing
            if ($rawScore !== null) {
                $completedCount++;
                $normalizedScore = ($rawScore / $maxScore) * 100.0;
                $weightedScore = $normalizedScore * ($weight / 100.0);

                $weightedSum += ($normalizedScore * $weight);
                $availableWeightSum += $weight;
            }

            $breakdown[$column->id] = [
                'column_id' => $column->id,
                'column_name' => $column->name,
                'raw_score' => $rawScore,
                'max_score' => $maxScore,
                'normalized_score' => $normalizedScore !== null ? round($normalizedScore, 2) : null,
                'weight' => $weight,
                'weighted_score' => $weightedScore !== null ? round($weightedScore, 2) : null,
            ];
        }

        $finalScore = null;
        if ($availableWeightSum > 0) {
            // Proportional weighted average based on available assessments
            $finalScore = round($weightedSum / $availableWeightSum, 2);
        }

        $isComplete = ($completedCount === $columns->count()) && ($columns->count() > 0);

        return [
            'final_score' => $finalScore,
            'is_complete' => $isComplete,
            'completed_count' => $completedCount,
            'total_columns' => $columns->count(),
            'completed_weight' => round($availableWeightSum, 2),
            'total_weight' => round($totalConfiguredWeight, 2),
            'breakdown' => $breakdown,
        ];
    }
}
