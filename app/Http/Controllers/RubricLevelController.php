<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRubricLevelRequest;
use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RubricLevelController extends Controller
{
    /**
     * Update the specified rubric level (name, score, description).
     */
    public function update(
        UpdateRubricLevelRequest $request,
        Gradebook $gradebook,
        AssessmentColumn $column,
        Rubric $rubric,
        RubricCriterion $criterion,
        RubricLevel $level
    ): JsonResponse|RedirectResponse {
        abort_if(
            $column->gradebook_id !== $gradebook->id ||
            $rubric->assessment_column_id !== $column->id ||
            $criterion->rubric_id !== $rubric->id ||
            $level->rubric_criterion_id !== $criterion->id,
            404
        );
        Gate::authorize('update', $gradebook);

        $level->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Level rubrik berhasil diperbarui.',
                'level' => $level,
            ]);
        }

        return back()->with('status', 'Level rubrik berhasil diperbarui.');
    }
}
