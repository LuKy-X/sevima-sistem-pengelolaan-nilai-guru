<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRubricScoreRequest;
use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\RubricLevel;
use App\Models\StudentRubricScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentRubricScoreController extends Controller
{
    /**
     * Store student rubric evaluations and sync results to assessment column scores.
     */
    public function store(
        StoreStudentRubricScoreRequest $request,
        Gradebook $gradebook,
        AssessmentColumn $column,
        Rubric $rubric
    ): JsonResponse|RedirectResponse {
        abort_if($column->gradebook_id !== $gradebook->id || $rubric->assessment_column_id !== $column->id, 404);
        Gate::authorize('update', $gradebook);

        $submittedScores = $request->input('scores', []);

        DB::transaction(function () use ($submittedScores, $rubric) {
            $levelsCache = RubricLevel::whereIn('rubric_criterion_id', $rubric->criteria->pluck('id'))
                ->get()
                ->keyBy('id');

            foreach ($submittedScores as $studentId => $criteriaScores) {
                $studentId = (int) $studentId;

                if (! is_array($criteriaScores)) {
                    continue;
                }

                foreach ($criteriaScores as $criterionId => $scoreData) {
                    $criterionId = (int) $criterionId;

                    $score = null;
                    $levelId = null;

                    if (is_array($scoreData)) {
                        $rawScore = $scoreData['score'] ?? null;
                        $rawLevel = $scoreData['level_id'] ?? null;

                        $score = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;
                        $levelId = ($rawLevel !== null && $rawLevel !== '') ? (int) $rawLevel : null;

                        // If teacher picked a level but didn't enter custom score, default score to level's score
                        if ($score === null && $levelId !== null) {
                            $score = $levelsCache->get($levelId)?->score !== null ? (float) $levelsCache->get($levelId)->score : null;
                        }
                    } elseif ($scoreData !== null && $scoreData !== '') {
                        // Backward compatibility for scalar values (level id or raw numeric score)
                        $matchedLevel = $levelsCache->get($scoreData);
                        if ($matchedLevel) {
                            $levelId = $matchedLevel->id;
                            $score = (float) $matchedLevel->score;
                        } elseif (is_numeric($scoreData)) {
                            $score = (float) $scoreData;
                            $levelId = null;
                        }
                    }

                    if ($score === null && $levelId === null) {
                        StudentRubricScore::where('student_id', $studentId)
                            ->where('rubric_criterion_id', $criterionId)
                            ->delete();
                    } else {
                        StudentRubricScore::updateOrCreate(
                            [
                                'student_id' => $studentId,
                                'rubric_criterion_id' => $criterionId,
                            ],
                            [
                                'rubric_level_id' => $levelId,
                                'score' => $score,
                            ]
                        );
                    }
                }

                // Synchronize student's calculated rubric score to parent assessment column Score
                $rubric->syncStudentScore($studentId);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Nilai rubrik siswa berhasil disimpan dan disinkronkan.',
            ]);
        }

        return back()->with('status', 'Nilai rubrik siswa berhasil disimpan dan disinkronkan.');
    }
}
