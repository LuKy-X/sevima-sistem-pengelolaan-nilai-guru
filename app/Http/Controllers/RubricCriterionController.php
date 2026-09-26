<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRubricCriterionRequest;
use App\Http\Requests\UpdateRubricCriterionRequest;
use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RubricCriterionController extends Controller
{
    /**
     * Store a newly created criterion in the rubric.
     */
    public function store(
        StoreRubricCriterionRequest $request,
        Gradebook $gradebook,
        AssessmentColumn $column,
        Rubric $rubric
    ): JsonResponse|RedirectResponse {
        abort_if($column->gradebook_id !== $gradebook->id || $rubric->assessment_column_id !== $column->id, 404);
        Gate::authorize('update', $gradebook);

        $validated = $request->validated();

        if (empty($validated['order'])) {
            $validated['order'] = ((int) $rubric->criteria()->max('order')) + 1;
        }

        $criterion = $rubric->criteria()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kriteria rubrik berhasil ditambahkan.',
                'criterion' => $criterion->load('levels'),
            ], 201);
        }

        return back()->with('status', 'Kriteria rubrik berhasil ditambahkan.');
    }

    /**
     * Update the specified criterion in the rubric.
     */
    public function update(
        UpdateRubricCriterionRequest $request,
        Gradebook $gradebook,
        AssessmentColumn $column,
        Rubric $rubric,
        RubricCriterion $criterion
    ): JsonResponse|RedirectResponse {
        abort_if(
            $column->gradebook_id !== $gradebook->id ||
            $rubric->assessment_column_id !== $column->id ||
            $criterion->rubric_id !== $rubric->id,
            404
        );
        Gate::authorize('update', $gradebook);

        $criterion->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kriteria rubrik berhasil diperbarui.',
                'criterion' => $criterion,
            ]);
        }

        return back()->with('status', 'Kriteria rubrik berhasil diperbarui.');
    }

    /**
     * Delete the specified criterion from the rubric.
     */
    public function destroy(
        Gradebook $gradebook,
        AssessmentColumn $column,
        Rubric $rubric,
        RubricCriterion $criterion
    ): JsonResponse|RedirectResponse {
        abort_if(
            $column->gradebook_id !== $gradebook->id ||
            $rubric->assessment_column_id !== $column->id ||
            $criterion->rubric_id !== $rubric->id,
            404
        );
        Gate::authorize('update', $gradebook);

        $criterion->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kriteria rubrik berhasil dihapus.',
            ]);
        }

        return back()->with('status', 'Kriteria rubrik berhasil dihapus.');
    }
}
