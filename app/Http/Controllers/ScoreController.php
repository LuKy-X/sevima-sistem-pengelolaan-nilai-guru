<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchStoreScoreRequest;
use App\Models\Gradebook;
use App\Models\Score;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ScoreController extends Controller
{
    /**
     * Store or batch update scores for a gradebook.
     */
    public function store(BatchStoreScoreRequest $request, Gradebook $gradebook): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $gradebook);

        $normalized = $request->normalizedScores();

        $gradebook->loadMissing('assessmentColumns.rubric');
        $rubricColumnIds = $gradebook->assessmentColumns
            ->filter(fn ($col) => $col->rubric !== null)
            ->pluck('id')
            ->all();

        $toUpsert = [];
        $toDelete = [];

        foreach ($normalized as $item) {
            $columnId = (int) $item['assessment_column_id'];
            $studentId = (int) $item['student_id'];
            $scoreVal = $item['score'];

            // Assessment columns managed by a rubric cannot be overwritten directly via the gradebook score matrix
            if (in_array($columnId, $rubricColumnIds, true)) {
                continue;
            }

            if ($scoreVal === null || $scoreVal === '') {
                $toDelete[] = [
                    'assessment_column_id' => $columnId,
                    'student_id' => $studentId,
                ];
            } else {
                $toUpsert[] = [
                    'assessment_column_id' => $columnId,
                    'student_id' => $studentId,
                    'score' => (float) $scoreVal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($toUpsert, $toDelete) {
            if (! empty($toUpsert)) {
                Score::upsert(
                    $toUpsert,
                    ['assessment_column_id', 'student_id'],
                    ['score', 'updated_at']
                );
            }

            if (! empty($toDelete)) {
                foreach ($toDelete as $del) {
                    Score::where('assessment_column_id', $del['assessment_column_id'])
                        ->where('student_id', $del['student_id'])
                        ->delete();
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Nilai berhasil disimpan.',
            ]);
        }

        return redirect()
            ->route('gradebooks.show', $gradebook)
            ->with('status', 'Nilai berhasil disimpan.');
    }

    /**
     * Alias for store method to handle batch saving endpoint cleanly.
     */
    public function batchStore(BatchStoreScoreRequest $request, Gradebook $gradebook): JsonResponse|RedirectResponse
    {
        return $this->store($request, $gradebook);
    }
}
