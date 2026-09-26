<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRubricRequest;
use App\Http\Requests\UpdateRubricRequest;
use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use App\Models\Rubric;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RubricController extends Controller
{
    /**
     * Display the rubric management and scoring interface for an assessment column.
     */
    public function show(Gradebook $gradebook, AssessmentColumn $column): View
    {
        abort_if($column->gradebook_id !== $gradebook->id, 404);
        Gate::authorize('view', $gradebook);

        // Ensure rubric exists or initialize with default title and description
        $rubric = $column->rubric()->firstOrCreate(
            ['assessment_column_id' => $column->id],
            [
                'name' => 'Rubrik Nilai '.$column->name,
                'description' => 'Isi form berikut untuk memanajemen kolom tugas atau remidi pada buku nilai',
            ]
        );

        $rubric->load(['criteria.levels', 'criteria.studentScores']);

        $students = $gradebook->classroom->students()
            ->wherePivot('academic_year_id', $gradebook->academic_year_id)
            ->orderBy('name')
            ->get();

        $studentSummaries = [];
        foreach ($students as $student) {
            $studentSummaries[$student->id] = $rubric->getStudentScoreSummary($student->id);
        }

        return view('rubrics.show', compact('gradebook', 'column', 'rubric', 'students', 'studentSummaries'));
    }

    /**
     * Store a newly created rubric for an assessment column.
     */
    public function store(StoreRubricRequest $request, Gradebook $gradebook, AssessmentColumn $column): JsonResponse|RedirectResponse
    {
        abort_if($column->gradebook_id !== $gradebook->id, 404);
        Gate::authorize('update', $gradebook);

        $rubric = $column->rubric()->create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rubrik berhasil dibuat.',
                'rubric' => $rubric,
            ], 201);
        }

        return back()->with('status', 'Rubrik berhasil dibuat.');
    }

    /**
     * Update the specified rubric.
     */
    public function update(
        UpdateRubricRequest $request,
        Gradebook $gradebook,
        AssessmentColumn $column,
        Rubric $rubric
    ): JsonResponse|RedirectResponse {
        abort_if($column->gradebook_id !== $gradebook->id || $rubric->assessment_column_id !== $column->id, 404);
        Gate::authorize('update', $gradebook);

        $validated = $request->validated();

        DB::transaction(function () use ($rubric, $validated) {
            $rubric->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $rubric->description,
            ]);

            if (! empty($validated['criteria']) && is_array($validated['criteria'])) {
                foreach ($validated['criteria'] as $criterionData) {
                    if (isset($criterionData['id'])) {
                        $rubric->criteria()->where('id', $criterionData['id'])->update([
                            'name' => $criterionData['name'],
                            'weight' => (float) $criterionData['weight'],
                        ]);
                    }
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rubrik berhasil diperbarui.',
                'rubric' => $rubric->fresh(['criteria.levels']),
            ]);
        }

        return back()->with('status', 'Rubrik berhasil diperbarui.');
    }

    /**
     * Delete the specified rubric.
     */
    public function destroy(Gradebook $gradebook, AssessmentColumn $column, Rubric $rubric): JsonResponse|RedirectResponse
    {
        abort_if($column->gradebook_id !== $gradebook->id || $rubric->assessment_column_id !== $column->id, 404);
        Gate::authorize('update', $gradebook);

        $rubric->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rubrik berhasil dihapus.',
            ]);
        }

        return back()->with('status', 'Rubrik berhasil dihapus.');
    }
}
