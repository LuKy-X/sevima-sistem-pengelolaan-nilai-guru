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
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Export the gradebook to a spreadsheet (CSV) formatted like a standard school gradebook.
     */
    public function export(Gradebook $gradebook): StreamedResponse
    {
        Gate::authorize('view', $gradebook);

        $gradebook->load([
            'classroom',
            'subject',
            'academicYear',
            'user',
            'assessmentColumns.scores',
            'assessmentColumns.rubric',
        ]);

        $students = $gradebook->getEnrolledStudents();
        $columns = $gradebook->assessmentColumns;

        $scoresMatrix = [];
        foreach ($columns as $column) {
            foreach ($column->scores as $score) {
                $scoresMatrix[$score->student_id][$column->id] = $score->score;
            }
        }

        $studentFinalGrades = [];
        foreach ($students as $student) {
            $weightedSum = 0.0;
            $availableWeightSum = 0.0;
            $completedCount = 0;

            foreach ($columns as $column) {
                if (isset($scoresMatrix[$student->id][$column->id]) && $scoresMatrix[$student->id][$column->id] !== null) {
                    $rawScore = (float) $scoresMatrix[$student->id][$column->id];
                    $completedCount++;

                    $maxScore = (float) ($column->max_score > 0 ? $column->max_score : 100.0);
                    $weight = (float) $column->weight;
                    $normalizedScore = ($rawScore / $maxScore) * 100.0;

                    $weightedSum += ($normalizedScore * $weight);
                    $availableWeightSum += $weight;
                }
            }

            $finalScore = $availableWeightSum > 0 ? round($weightedSum / $availableWeightSum, 2) : null;
            $isComplete = ($completedCount === $columns->count()) && ($columns->count() > 0);

            $studentFinalGrades[$student->id] = [
                'final_score' => $finalScore,
                'status' => $isComplete ? 'Lengkap' : ($availableWeightSum > 0 ? 'Sementara ('.round($availableWeightSum).'%)' : 'Belum Ada Nilai'),
            ];
        }

        $sanitizedClass = Str::slug($gradebook->classroom->name);
        $sanitizedSubject = Str::slug($gradebook->subject->name);
        $sanitizedYear = Str::slug($gradebook->academicYear->name);
        $filename = "buku-nilai-{$sanitizedClass}-{$sanitizedSubject}-{$sanitizedYear}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($gradebook, $students, $columns, $scoresMatrix, $studentFinalGrades) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Metadata Header (Standard School Gradebook Format)
            fputcsv($handle, ['BUKU NILAI HASIL BELAJAR SISWA']);
            fputcsv($handle, ['Judul Buku Nilai', $gradebook->title ?? $gradebook->subject->name]);
            fputcsv($handle, ['Mata Pelajaran', $gradebook->subject->name]);
            fputcsv($handle, ['Kelas', $gradebook->classroom->name]);
            fputcsv($handle, ['Tahun Ajaran / Semester', "{$gradebook->academicYear->name} - Semester ".ucfirst($gradebook->semester)]);
            fputcsv($handle, ['Guru Pengampu', $gradebook->user->name.($gradebook->user->nip ? " (NIP: {$gradebook->user->nip})" : '')]);
            fputcsv($handle, ['Tanggal Unduh', now()->format('d/m/Y H:i')]);
            fputcsv($handle, []);

            // Table Header Row
            $headerRow = ['No', 'NISN', 'Nama Siswa'];
            foreach ($columns as $col) {
                $rubricTag = $col->rubric ? ' [Rubrik]' : '';
                $headerRow[] = "{$col->name}{$rubricTag} (Bobot: ".number_format($col->weight, 0).'% | Maks: '.number_format($col->max_score, 0).')';
            }
            $headerRow[] = 'Nilai Akhir (Berbobot)';
            $headerRow[] = 'Status';
            fputcsv($handle, $headerRow);

            // Student Data Rows
            $columnScoresCollector = [];
            $allFinalScores = [];

            foreach ($students as $idx => $student) {
                $row = [
                    $idx + 1,
                    $student->nisn ?? '-',
                    $student->name,
                ];

                foreach ($columns as $col) {
                    $score = $scoresMatrix[$student->id][$col->id] ?? null;
                    if ($score !== null) {
                        $row[] = number_format((float) $score, 2);
                        $columnScoresCollector[$col->id][] = (float) $score;
                    } else {
                        $row[] = '-';
                    }
                }

                $final = $studentFinalGrades[$student->id]['final_score'] ?? null;
                if ($final !== null) {
                    $row[] = number_format($final, 2);
                    $allFinalScores[] = $final;
                } else {
                    $row[] = '-';
                }

                $row[] = $studentFinalGrades[$student->id]['status'] ?? '-';
                fputcsv($handle, $row);
            }

            // Class Average Row
            $avgRow = ['Rata-rata Kelas', '', ''];
            foreach ($columns as $col) {
                $colVals = $columnScoresCollector[$col->id] ?? [];
                if (count($colVals) > 0) {
                    $avgRow[] = number_format(array_sum($colVals) / count($colVals), 2);
                } else {
                    $avgRow[] = '-';
                }
            }
            if (count($allFinalScores) > 0) {
                $avgRow[] = number_format(array_sum($allFinalScores) / count($allFinalScores), 2);
            } else {
                $avgRow[] = '-';
            }
            $avgRow[] = '';
            fputcsv($handle, $avgRow);

            // Official Signature Block at Bottom
            fputcsv($handle, []);
            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', 'Mengetahui,']);
            fputcsv($handle, ['', '', '', 'Guru Mata Pelajaran']);
            fputcsv($handle, []);
            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', $gradebook->user->name]);
            fputcsv($handle, ['', '', '', 'NIP: '.($gradebook->user->nip ?? '-')]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Display the specified gradebook information.
     */
    public function show(Gradebook $gradebook): View
    {
        Gate::authorize('view', $gradebook);

        $gradebook->load(['classroom', 'subject', 'academicYear', 'assessmentColumns.scores', 'assessmentColumns.rubric']);

        $students = $gradebook->getEnrolledStudents();

        $scoresMatrix = [];
        foreach ($gradebook->assessmentColumns as $column) {
            foreach ($column->scores as $score) {
                $scoresMatrix[$score->student_id][$column->id] = $score->score;
            }
        }

        $studentAverages = [];
        $studentFinalGrades = [];
        $columns = $gradebook->assessmentColumns;
        $totalConfiguredWeight = (float) $columns->sum('weight');

        foreach ($students as $student) {
            $studentScores = [];
            $weightedSum = 0.0;
            $availableWeightSum = 0.0;
            $completedCount = 0;

            foreach ($columns as $column) {
                if (isset($scoresMatrix[$student->id][$column->id]) && $scoresMatrix[$student->id][$column->id] !== null) {
                    $rawScore = (float) $scoresMatrix[$student->id][$column->id];
                    $studentScores[] = $rawScore;
                    $completedCount++;

                    $maxScore = (float) ($column->max_score > 0 ? $column->max_score : 100.0);
                    $weight = (float) $column->weight;
                    $normalizedScore = ($rawScore / $maxScore) * 100.0;

                    $weightedSum += ($normalizedScore * $weight);
                    $availableWeightSum += $weight;
                }
            }

            $finalScore = $availableWeightSum > 0 ? round($weightedSum / $availableWeightSum, 2) : null;
            $arithmeticAvg = count($studentScores) > 0 ? round(array_sum($studentScores) / count($studentScores), 2) : null;

            $studentFinalGrades[$student->id] = [
                'final_score' => $finalScore,
                'is_complete' => ($completedCount === $columns->count()) && ($columns->count() > 0),
                'completed_count' => $completedCount,
                'total_columns' => $columns->count(),
                'completed_weight' => round($availableWeightSum, 2),
                'total_weight' => round($totalConfiguredWeight, 2),
            ];

            $studentAverages[$student->id] = $arithmeticAvg;
        }

        return view('gradebooks.show', compact('gradebook', 'students', 'scoresMatrix', 'studentFinalGrades', 'studentAverages', 'totalConfiguredWeight'));
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
