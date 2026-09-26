<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradebookRequest;
use App\Http\Requests\UpdateGradebookRequest;
use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $filename = "rekap-nilai-{$sanitizedClass}-{$sanitizedSubject}-{$sanitizedYear}.csv";

        $format = request()->query('format', 'xlsx');
        if ($format === 'csv') {
            return $this->exportCsv($gradebook, $students, $columns, $scoresMatrix, $studentFinalGrades);
        }

        return $this->exportXlsx($gradebook, $students, $columns, $scoresMatrix, $studentFinalGrades);
    }

    /**
     * Export gradebook as a styled Microsoft Excel (.xlsx) file.
     *
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, AssessmentColumn>  $columns
     * @param  array<int, array<int, float|string|null>>  $scoresMatrix
     * @param  array<int, array{final_score: float|null, status: string}>  $studentFinalGrades
     */
    protected function exportXlsx(
        Gradebook $gradebook,
        $students,
        $columns,
        array $scoresMatrix,
        array $studentFinalGrades
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai');
        $sheet->setShowGridLines(true);

        $totalCols = count($columns) + 3; // Col A (No), Col B (Nama), Assessment Columns, Last Col (Nilai Rata-rata)
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        // Row 1: Title (Merged A1 to Last Column)
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', 'REKAP NILAI SISWA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Rows 3-5: School & Gradebook Metadata
        $semesterStr = strtolower(trim((string) $gradebook->semester));
        $semesterRoman = in_array($semesterStr, ['ganjil', '1', 'i', 'satu']) ? 'I' : 'II';
        $schoolName = $gradebook->title ?? config('app.name', 'SDN 1 BRABOWAN');

        $sheet->setCellValue('A3', 'Kelas/ Semester');
        $sheet->setCellValue('B3', ': '.$gradebook->classroom->name.' / '.$semesterRoman);
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->setCellValue('D3', 'Nama Sekolah');
        $sheet->setCellValue('E3', ': '.$schoolName);
        $sheet->getStyle('D3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Tahun Ajaran');
        $sheet->setCellValue('B4', ': '.$gradebook->academicYear->name);
        $sheet->getStyle('A4')->getFont()->setBold(true);

        $sheet->setCellValue('D4', 'Guru Pengampu');
        $sheet->setCellValue('E4', ': '.$gradebook->user->name.($gradebook->user->nip ? ' (NIP: '.$gradebook->user->nip.')' : ''));
        $sheet->getStyle('D4')->getFont()->setBold(true);

        $sheet->setCellValue('A5', 'Mata Pelajaran');
        $sheet->setCellValue('B5', ': '.$gradebook->subject->name);
        $sheet->getStyle('A5')->getFont()->setBold(true);

        // Row 7: Table Headers
        $sheet->setCellValue('A7', 'No');
        $sheet->setCellValue('B7', 'Nama');

        $colIdx = 3;
        foreach ($columns as $col) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $rubricTag = $col->rubric ? ' [Rubrik]' : '';
            $weightTag = (float) $col->weight > 0 ? ' ('.rtrim(rtrim(number_format((float) $col->weight, 2, '.', ''), '0'), '.').'%)' : '';
            $sheet->setCellValue("{$colLetter}7", $col->name.$rubricTag.$weightTag);
            $colIdx++;
        }
        $sheet->setCellValue("{$lastColLetter}7", 'Nilai Rata-rata');

        // Style Table Headers
        $sheet->getRowDimension(7)->setRowHeight(26);
        $headerStyle = $sheet->getStyle("A7:{$lastColLetter}7");
        $headerStyle->getFont()->setBold(true)->setSize(10);
        $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9'); // Light slate gray background

        // Helper to format numeric score cleanly
        $formatScore = fn ($val) => ($val === null || $val === '' || $val === '-')
            ? null
            : ((float) $val == (int) $val ? (int) $val : round((float) $val, 2));

        // Data Rows starting at Row 8
        $currentRow = 8;
        $columnScoresCollector = [];
        $allFinalScores = [];

        foreach ($students as $idx => $student) {
            $sheet->setCellValue("A{$currentRow}", $idx + 1);
            $sheet->setCellValue("B{$currentRow}", $student->name);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $colIdx = 3;
            foreach ($columns as $col) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $score = $scoresMatrix[$student->id][$col->id] ?? null;
                $numScore = $formatScore($score);
                if ($numScore !== null) {
                    $sheet->setCellValue("{$colLetter}{$currentRow}", $numScore);
                    $columnScoresCollector[$col->id][] = (float) $numScore;
                }
                $sheet->getStyle("{$colLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $colIdx++;
            }

            $final = $studentFinalGrades[$student->id]['final_score'] ?? null;
            $numFinal = $formatScore($final);
            if ($numFinal !== null) {
                $sheet->setCellValue("{$lastColLetter}{$currentRow}", $numFinal);
                $allFinalScores[] = (float) $numFinal;
            }
            $sheet->getStyle("{$lastColLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$lastColLetter}{$currentRow}")->getFont()->setBold(true);

            $currentRow++;
        }

        // Class Average Row
        $avgRow = $currentRow;
        $sheet->mergeCells("A{$avgRow}:B{$avgRow}");
        $sheet->setCellValue("A{$avgRow}", 'Rata-rata Kelas');
        $sheet->getStyle("A{$avgRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$avgRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($avgRow)->setRowHeight(22);

        $colIdx = 3;
        foreach ($columns as $col) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $colVals = $columnScoresCollector[$col->id] ?? [];
            if (count($colVals) > 0) {
                $colAvg = round(array_sum($colVals) / count($colVals), 2);
                $sheet->setCellValue("{$colLetter}{$avgRow}", $colAvg);
            }
            $sheet->getStyle("{$colLetter}{$avgRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$colLetter}{$avgRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $colIdx++;
        }

        if (count($allFinalScores) > 0) {
            $classAvg = round(array_sum($allFinalScores) / count($allFinalScores), 2);
            $sheet->setCellValue("{$lastColLetter}{$avgRow}", $classAvg);
        }
        $sheet->getStyle("{$lastColLetter}{$avgRow}")->getFont()->setBold(true);
        $sheet->getStyle("{$lastColLetter}{$avgRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$avgRow}:{$lastColLetter}{$avgRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');

        // Apply Borders to Entire Table
        $tableRange = "A7:{$lastColLetter}{$avgRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF000000');

        // Official Signature Block at Bottom Right
        $sigRow = $avgRow + 3;
        $sigColIdx = max(3, $totalCols - 1);
        $sigColLetter = Coordinate::stringFromColumnIndex($sigColIdx);

        $sheet->setCellValue("{$sigColLetter}{$sigRow}", 'Mengetahui,');
        $sheet->setCellValue("{$sigColLetter}".($sigRow + 1), 'Guru Mata Pelajaran');
        $sheet->setCellValue("{$sigColLetter}".($sigRow + 4), $gradebook->user->name);
        $sheet->getStyle("{$sigColLetter}".($sigRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->setCellValue("{$sigColLetter}".($sigRow + 5), 'NIP: '.($gradebook->user->nip ?? '-'));

        // Auto-fit column widths
        for ($c = 1; $c <= $totalCols; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);
        $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(26);

        $sanitizedClass = Str::slug($gradebook->classroom->name);
        $sanitizedSubject = Str::slug($gradebook->subject->name);
        $sanitizedYear = Str::slug($gradebook->academicYear->name);
        $filename = "rekap-nilai-{$sanitizedClass}-{$sanitizedSubject}-{$sanitizedYear}.xlsx";

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ];

        return response()->stream(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, $headers);
    }

    /**
     * Export gradebook as a clean CSV file.
     *
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, AssessmentColumn>  $columns
     * @param  array<int, array<int, float|string|null>>  $scoresMatrix
     * @param  array<int, array{final_score: float|null, status: string}>  $studentFinalGrades
     */
    protected function exportCsv(
        Gradebook $gradebook,
        $students,
        $columns,
        array $scoresMatrix,
        array $studentFinalGrades
    ): StreamedResponse {
        $sanitizedClass = Str::slug($gradebook->classroom->name);
        $sanitizedSubject = Str::slug($gradebook->subject->name);
        $sanitizedYear = Str::slug($gradebook->academicYear->name);
        $filename = "rekap-nilai-{$sanitizedClass}-{$sanitizedSubject}-{$sanitizedYear}.csv";

        $delimiter = request()->query('delimiter', ',');
        if (! in_array($delimiter, [',', ';'])) {
            $delimiter = ',';
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($gradebook, $students, $columns, $scoresMatrix, $studentFinalGrades, $delimiter) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel / spreadsheet compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Title Header (Centered over sheet)
            fputcsv($handle, ['', '', 'REKAP NILAI SISWA'], $delimiter);
            fputcsv($handle, [], $delimiter);

            // Metadata Block (Authentic School Gradebook Layout)
            $semesterStr = strtolower(trim((string) $gradebook->semester));
            $semesterRoman = in_array($semesterStr, ['ganjil', '1', 'i', 'satu']) ? 'I' : 'II';
            $schoolName = $gradebook->title ?? config('app.name', 'SDN 1 BRABOWAN');

            fputcsv($handle, ['Kelas/ Semester', ': '.$gradebook->classroom->name.' / '.$semesterRoman, '', 'Nama Sekolah', ': '.$schoolName], $delimiter);
            fputcsv($handle, ['Tahun Ajaran', ': '.$gradebook->academicYear->name, '', 'Guru Pengampu', ': '.$gradebook->user->name.($gradebook->user->nip ? ' (NIP: '.$gradebook->user->nip.')' : '')], $delimiter);
            fputcsv($handle, ['Mata Pelajaran', ': '.$gradebook->subject->name], $delimiter);
            fputcsv($handle, [], $delimiter);

            // Table Header Row: No, Nama, Assessment Columns, Nilai Rata-rata
            $headerRow = ['No', 'Nama'];
            foreach ($columns as $col) {
                $rubricTag = $col->rubric ? ' [Rubrik]' : '';
                $weightTag = (float) $col->weight > 0 ? ' ('.rtrim(rtrim(number_format((float) $col->weight, 2, '.', ''), '0'), '.').'%)' : '';
                $headerRow[] = "{$col->name}{$rubricTag}{$weightTag}";
            }
            $headerRow[] = 'Nilai Rata-rata';
            fputcsv($handle, $headerRow, $delimiter);

            // Helper to format scores cleanly: whole numbers as integers, max 2 decimals, empty for null
            $formatScore = fn ($val) => ($val === null || $val === '' || $val === '-')
                ? ''
                : rtrim(rtrim(number_format((float) $val, 2, '.', ''), '0'), '.');

            // Student Data Rows
            $columnScoresCollector = [];
            $allFinalScores = [];

            foreach ($students as $idx => $student) {
                $row = [
                    $idx + 1,
                    $student->name,
                ];

                foreach ($columns as $col) {
                    $score = $scoresMatrix[$student->id][$col->id] ?? null;
                    $row[] = $formatScore($score);
                    if ($score !== null && $score !== '') {
                        $columnScoresCollector[$col->id][] = (float) $score;
                    }
                }

                $final = $studentFinalGrades[$student->id]['final_score'] ?? null;
                $row[] = $formatScore($final);
                if ($final !== null && $final !== '') {
                    $allFinalScores[] = (float) $final;
                }

                fputcsv($handle, $row, $delimiter);
            }

            // Class Average Row: Rata-rata Kelas
            $avgRow = ['Rata-rata Kelas', ''];
            foreach ($columns as $col) {
                $colVals = $columnScoresCollector[$col->id] ?? [];
                if (count($colVals) > 0) {
                    $colAvg = array_sum($colVals) / count($colVals);
                    $avgRow[] = $formatScore($colAvg);
                } else {
                    $avgRow[] = '';
                }
            }

            if (count($allFinalScores) > 0) {
                $totalAvg = array_sum($allFinalScores) / count($allFinalScores);
                $avgRow[] = $formatScore($totalAvg);
            } else {
                $avgRow[] = '';
            }

            fputcsv($handle, $avgRow, $delimiter);

            // Official Signature Block at Bottom Right
            fputcsv($handle, [], $delimiter);
            fputcsv($handle, [], $delimiter);

            $sigCol = max(2, count($columns));
            $makeSigRow = function (string $text) use ($sigCol) {
                $cells = array_fill(0, $sigCol, '');
                $cells[] = $text;

                return $cells;
            };

            fputcsv($handle, $makeSigRow('Mengetahui,'), $delimiter);
            fputcsv($handle, $makeSigRow('Guru Mata Pelajaran'), $delimiter);
            fputcsv($handle, [], $delimiter);
            fputcsv($handle, [], $delimiter);
            fputcsv($handle, $makeSigRow($gradebook->user->name), $delimiter);
            fputcsv($handle, $makeSigRow('NIP: '.($gradebook->user->nip ?? '-')), $delimiter);

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
