<?php

namespace App\Models;

use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['assessment_column_id', 'name', 'description'])]
class Rubric extends Model
{
    /** @use HasFactory<RubricFactory> */
    use HasFactory;

    /**
     * Get the assessment column this rubric belongs to.
     *
     * @return BelongsTo<AssessmentColumn, $this>
     */
    public function assessmentColumn(): BelongsTo
    {
        return $this->belongsTo(AssessmentColumn::class);
    }

    /**
     * Get all criteria belonging to this rubric.
     *
     * @return HasMany<RubricCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('order');
    }

    /**
     * Calculate total configured weight of all criteria in this rubric.
     */
    public function totalWeight(): float
    {
        return round((float) $this->criteria()->sum('weight'), 2);
    }

    /**
     * Check if the rubric is fully configured (total criteria weight equals 100%).
     */
    public function isConfigured(): bool
    {
        return abs($this->totalWeight() - 100.00) < 0.001;
    }

    /**
     * Calculate the dynamic rubric score for a student based on selected levels and criterion weights.
     *
     * Formula:
     * criterion contribution = selected level score * (criterion weight / 100)
     * rubric final score = sum of criterion contributions (scaled proportionally if partially evaluated)
     */
    public function calculateStudentScore(int $studentId): ?float
    {
        $summary = $this->getStudentScoreSummary($studentId);

        return $summary['final_score'];
    }

    /**
     * Get detailed rubric evaluation summary and completion metrics for a student.
     *
     * @return array{
     *     final_score: float|null,
     *     completed_weight: float,
     *     total_weight: float,
     *     completion_percentage: float,
     *     is_complete: bool,
     *     evaluated_count: int,
     *     total_criteria: int,
     *     breakdown: array<int, array{criterion_id: int, criterion_name: string, criterion_weight: float, selected_level_id: int|null, selected_level_number: int|null, selected_level_name: string|null, level_score: float|null, score: float|null, actual_score: float|null, contribution: float|null}>
     * }
     */
    public function getStudentScoreSummary(int $studentId): array
    {
        $criteria = $this->criteria()
            ->with(['studentScores' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId)->with('level');
            }])
            ->get();

        if ($criteria->isEmpty()) {
            return [
                'final_score' => null,
                'completed_weight' => 0.0,
                'total_weight' => 0.0,
                'completion_percentage' => 0.0,
                'is_complete' => false,
                'evaluated_count' => 0,
                'total_criteria' => 0,
                'breakdown' => [],
            ];
        }

        $totalConfiguredWeight = (float) $criteria->sum('weight');
        $weightedSum = 0.0;
        $availableWeight = 0.0;
        $evaluatedCount = 0;
        $breakdown = [];

        foreach ($criteria as $criterion) {
            $studentScore = $criterion->studentScores->first();
            $level = $studentScore?->level;

            $criterionScore = null;
            $contribution = null;

            // Prioritize student_rubric_scores.score (actual teacher numeric score)
            if ($studentScore !== null && $studentScore->score !== null) {
                $criterionScore = (float) $studentScore->score;
            } elseif ($level !== null && $level->score !== null) {
                $criterionScore = (float) $level->score;
            }

            if ($criterionScore !== null) {
                $evaluatedCount++;
                $weight = (float) $criterion->weight;

                $contribution = $criterionScore * ($weight / 100.00);
                $weightedSum += $contribution;
                $availableWeight += $weight;
            }

            $breakdown[$criterion->id] = [
                'criterion_id' => $criterion->id,
                'criterion_name' => $criterion->name,
                'criterion_weight' => (float) $criterion->weight,
                'selected_level_id' => $level?->id,
                'selected_level_number' => $level?->level_number,
                'selected_level_name' => $level?->name,
                'level_score' => $level?->score !== null ? (float) $level->score : null,
                'score' => $criterionScore,
                'actual_score' => $studentScore?->score !== null ? (float) $studentScore->score : null,
                'contribution' => $contribution !== null ? round($contribution, 2) : null,
            ];
        }

        $finalScore = null;
        if ($availableWeight > 0) {
            // Proportional final score based on evaluated criteria weights
            $proportionalScore = ($weightedSum / ($availableWeight / 100.00));

            // Scale to parent assessment column max_score if parent max_score != 100
            $parentMax = (float) ($this->assessmentColumn?->max_score ?? 100.00);
            if ($parentMax > 0 && abs($parentMax - 100.00) > 0.001) {
                $finalScore = round(($proportionalScore / 100.00) * $parentMax, 2);
            } else {
                $finalScore = round($proportionalScore, 2);
            }
        }

        $completionPercentage = $totalConfiguredWeight > 0
            ? round(($availableWeight / $totalConfiguredWeight) * 100.00, 1)
            : 0.0;

        $isComplete = ($evaluatedCount === $criteria->count()) && ($totalConfiguredWeight > 0) && ($availableWeight >= ($totalConfiguredWeight - 0.001));

        return [
            'final_score' => $finalScore,
            'completed_weight' => round($availableWeight, 2),
            'total_weight' => round($totalConfiguredWeight, 2),
            'completion_percentage' => $completionPercentage,
            'is_complete' => $isComplete,
            'evaluated_count' => $evaluatedCount,
            'total_criteria' => $criteria->count(),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Synchronize the calculated rubric score to the parent assessment column Score.
     */
    public function syncStudentScore(int $studentId): ?Score
    {
        $finalScore = $this->calculateStudentScore($studentId);

        if ($finalScore !== null) {
            return Score::updateOrCreate(
                [
                    'assessment_column_id' => $this->assessment_column_id,
                    'student_id' => $studentId,
                ],
                [
                    'score' => $finalScore,
                ]
            );
        }

        Score::where('assessment_column_id', $this->assessment_column_id)
            ->where('student_id', $studentId)
            ->delete();

        return null;
    }
}
