<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentColumnRequest;
use App\Http\Requests\UpdateAssessmentColumnRequest;
use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentColumnController extends Controller
{
    /**
     * Show the form for creating a new assessment column.
     */
    public function create(Gradebook $gradebook): View
    {
        Gate::authorize('create', [AssessmentColumn::class, $gradebook]);

        $nextOrder = ((int) $gradebook->assessmentColumns()->max('order')) + 1;

        return view('assessment_columns.create', compact('gradebook', 'nextOrder'));
    }

    /**
     * Store a newly created assessment column in storage.
     */
    public function store(StoreAssessmentColumnRequest $request, Gradebook $gradebook): RedirectResponse
    {
        Gate::authorize('create', [AssessmentColumn::class, $gradebook]);

        $validated = $request->validated();

        if (empty($validated['order'])) {
            $validated['order'] = ((int) $gradebook->assessmentColumns()->max('order')) + 1;
        }

        $gradebook->assessmentColumns()->create($validated);

        return redirect()
            ->route('gradebooks.show', $gradebook)
            ->with('status', 'Kolom penilaian berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified assessment column.
     */
    public function edit(Gradebook $gradebook, AssessmentColumn $column): View
    {
        abort_if($column->gradebook_id !== $gradebook->id, 404);

        Gate::authorize('update', $column);

        return view('assessment_columns.edit', compact('gradebook', 'column'));
    }

    /**
     * Update the specified assessment column in storage.
     */
    public function update(UpdateAssessmentColumnRequest $request, Gradebook $gradebook, AssessmentColumn $column): RedirectResponse
    {
        abort_if($column->gradebook_id !== $gradebook->id, 404);

        Gate::authorize('update', $column);

        $column->update($request->validated());

        return redirect()
            ->route('gradebooks.show', $gradebook)
            ->with('status', 'Kolom penilaian berhasil diperbarui.');
    }

    /**
     * Remove the specified assessment column from storage.
     */
    public function destroy(Gradebook $gradebook, AssessmentColumn $column): RedirectResponse
    {
        abort_if($column->gradebook_id !== $gradebook->id, 404);

        Gate::authorize('delete', $column);

        $column->delete();

        return redirect()
            ->route('gradebooks.show', $gradebook)
            ->with('status', 'Kolom penilaian berhasil dihapus.');
    }
}
