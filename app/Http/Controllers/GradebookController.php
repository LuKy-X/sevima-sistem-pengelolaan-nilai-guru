<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradebookRequest;
use App\Http\Requests\UpdateGradebookRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GradebookController extends Controller
{
    /**
     * Display a listing of the teacher's gradebooks.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Gradebook::class);

        $gradebooks = Auth::user()
            ->gradebooks()
            ->with(['classroom', 'subject', 'academicYear'])
            ->latest()
            ->paginate(10);

        return view('gradebooks.index', compact('gradebooks'));
    }

    /**
     * Show the form for creating a new gradebook.
     */
    public function create(): View
    {
        Gate::authorize('create', Gradebook::class);

        $classrooms = Classroom::orderBy('level')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('is_active')->orderBy('name')->get();

        return view('gradebooks.create', compact('classrooms', 'subjects', 'academicYears'));
    }

    /**
     * Store a newly created gradebook in storage.
     */
    public function store(StoreGradebookRequest $request): RedirectResponse
    {
        Gate::authorize('create', Gradebook::class);

        $validated = $request->validated();

        if (empty($validated['title'])) {
            $classroom = Classroom::find($validated['classroom_id']);
            $subject = Subject::find($validated['subject_id']);
            $year = AcademicYear::find($validated['academic_year_id']);

            $validated['title'] = "Buku Nilai {$subject?->name} - {$classroom?->name} ({$year?->name} ".ucfirst($validated['semester']).')';
        }

        $gradebook = Auth::user()->gradebooks()->create($validated);

        return redirect()
            ->route('gradebooks.show', $gradebook)
            ->with('status', 'Buku nilai berhasil dibuat.');
    }

    /**
     * Display the specified gradebook information.
     */
    public function show(Gradebook $gradebook): View
    {
        Gate::authorize('view', $gradebook);

        $gradebook->load(['classroom', 'subject', 'academicYear', 'assessmentColumns.scores']);

        $students = $gradebook->getEnrolledStudents();

        $scoresMatrix = [];
        foreach ($gradebook->assessmentColumns as $column) {
            foreach ($column->scores as $score) {
                $scoresMatrix[$score->student_id][$column->id] = $score->score;
            }
        }

        $studentAverages = [];
        foreach ($students as $student) {
            $studentScores = [];
            foreach ($gradebook->assessmentColumns as $column) {
                if (isset($scoresMatrix[$student->id][$column->id]) && $scoresMatrix[$student->id][$column->id] !== null) {
                    $studentScores[] = (float) $scoresMatrix[$student->id][$column->id];
                }
            }

            $studentAverages[$student->id] = count($studentScores) > 0
                ? round(array_sum($studentScores) / count($studentScores), 2)
                : null;
        }

        return view('gradebooks.show', compact('gradebook', 'students', 'scoresMatrix', 'studentAverages'));
    }

    /**
     * Show the form for editing the specified gradebook.
     */
    public function edit(Gradebook $gradebook): View
    {
        Gate::authorize('update', $gradebook);

        $classrooms = Classroom::orderBy('level')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('is_active')->orderBy('name')->get();

        return view('gradebooks.edit', compact('gradebook', 'classrooms', 'subjects', 'academicYears'));
    }

    /**
     * Update the specified gradebook in storage.
     */
    public function update(UpdateGradebookRequest $request, Gradebook $gradebook): RedirectResponse
    {
        Gate::authorize('update', $gradebook);

        $gradebook->update($request->validated());

        return redirect()
            ->route('gradebooks.show', $gradebook)
            ->with('status', 'Buku nilai berhasil diperbarui.');
    }

    /**
     * Remove the specified gradebook from storage.
     */
    public function destroy(Gradebook $gradebook): RedirectResponse
    {
        Gate::authorize('delete', $gradebook);

        $gradebook->delete();

        return redirect()
            ->route('gradebooks.index')
            ->with('status', 'Buku nilai berhasil dihapus.');
    }
}
