@extends('layouts.teacher')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Portal</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('gradebooks.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Buku Nilai</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-800 font-bold">{{ $gradebook->classroom->name }} - {{ ucfirst($gradebook->semester) }} {{ $gradebook->academicYear->name }}</span>
@endsection

@section('teacher_content')
<div class="space-y-6">
    <!-- Gradebook Header & Control Center (Inspired by Digital Teacher Scorebook) -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="px-3 py-1 rounded-xl bg-blue-50 text-[#1363DF] font-bold text-xs border border-blue-100">
                    Kelas {{ $gradebook->classroom->name }}
                </span>
                <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 font-semibold text-xs">
                    {{ $gradebook->academicYear->name }} • {{ ucfirst($gradebook->semester) }}
                </span>
                <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-600 font-medium text-xs">
                    {{ $gradebook->user->name }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $gradebook->subject->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $gradebook->title ?? 'Buku Nilai Mata Pelajaran' }}
            </p>
        </div>

        <!-- Gradebook Quick Actions -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a 
                href="{{ route('gradebooks.columns.create', $gradebook) }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100 text-[#1363DF] font-bold text-xs shadow-xs transition-all cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Kolom Penilaian</span>
            </a>

            <button 
                type="submit" 
                form="score-matrix-form" 
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                <span>Simpan Nilai</span>
            </button>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <a 
                href="{{ route('gradebooks.edit', $gradebook) }}" 
                class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition-colors"
                title="Edit Konfigurasi Buku Nilai"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </a>

            <form 
                action="{{ route('gradebooks.destroy', $gradebook) }}" 
                method="POST" 
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku nilai ini? Seluruh data nilai akan ikut terhapus.')" 
                class="inline"
            >
                @csrf
                @method('DELETE')
                <button 
                    type="submit" 
                    class="p-2.5 rounded-xl border border-rose-200 bg-rose-50/60 hover:bg-rose-100 text-rose-600 transition-colors cursor-pointer"
                    title="Hapus Buku Nilai"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Metadata Indicators Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 px-2">
        <div class="flex items-center gap-3 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5 font-bold text-slate-700">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Total {{ $students->count() }} Siswa Terdaftar
            </span>
            <span>•</span>
            <span class="font-bold text-slate-700">
                {{ $gradebook->assessmentColumns->count() }} Kolom Penilaian
            </span>
            @php
                $totalWeight = $gradebook->assessmentColumns->sum('weight');
            @endphp
            <span>•</span>
            <span class="font-semibold {{ $totalWeight == 100 ? 'text-emerald-700' : 'text-amber-700' }}">
                Total Bobot: {{ number_format($totalWeight, 0) }}% / 100%
            </span>
        </div>

        <div class="text-xs text-slate-400 hidden md:block">
            Petunjuk: Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono font-bold">Tab</kbd> atau <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono font-bold">Enter</kbd> untuk berpindah sel. Simpan cepat dengan <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono font-bold">Ctrl + S</kbd>.
        </div>
    </div>

    @if ($gradebook->assessmentColumns->isEmpty())
        <!-- Notice for No Assessment Columns Yet -->
        <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-blue-900">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1363DF] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Buku nilai ini belum memiliki kolom penilaian dinamis (seperti UH, Tugas, Project, atau Ujian).</span>
            </div>
            <a 
                href="{{ route('gradebooks.columns.create', $gradebook) }}" 
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition-colors shrink-0"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Kolom Penilaian</span>
            </a>
        </div>
    @endif

    <!-- SPREADSHEET SCORE MATRIX (Primary Component) -->
    <form id="score-matrix-form" action="{{ route('gradebooks.scores.store', $gradebook) }}" method="POST">
        @csrf

        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="overflow-x-auto relative max-h-[75vh]">
                <table class="w-full border-collapse text-left text-xs sm:text-sm">
                    <!-- Table Header (Thematic Deep Blue inspired by reference) -->
                    <thead>
                        <tr class="bg-[#1363DF] text-white">
                            <!-- Fixed Column: No -->
                            <th scope="col" class="w-12 px-3 py-3.5 text-center font-extrabold uppercase tracking-wider text-xs border-r border-blue-400/30 sticky left-0 z-20 bg-[#1363DF]">
                                No
                            </th>

                            <!-- Fixed Column: Nama Siswa -->
                            <th scope="col" class="min-w-[220px] px-4 py-3.5 font-extrabold text-xs uppercase tracking-wider border-r border-blue-400/30 sticky left-12 z-20 bg-[#1363DF] shadow-[2px_0_5px_rgba(0,0,0,0.08)]">
                                Nama Siswa
                            </th>

                            @if ($gradebook->assessmentColumns->isEmpty())
                                <th scope="col" class="px-6 py-3.5 text-center border-r border-blue-400/30 min-w-[200px]">
                                    <span class="font-extrabold text-white text-xs block">Kolom Penilaian</span>
                                    <span class="text-[10px] text-blue-100 block mt-0.5 font-mono">Belum ada kolom</span>
                                </th>
                            @else
                                <!-- Dynamic Assessment Columns -->
                                @foreach ($gradebook->assessmentColumns as $column)
                                    <th scope="col" class="min-w-[125px] px-3 py-3 text-center border-r border-blue-400/30">
                                        <div class="flex flex-col items-center justify-between h-full gap-1">
                                            <!-- Column Name -->
                                            <span class="font-extrabold text-white text-xs sm:text-sm whitespace-nowrap" title="{{ $column->name }}">
                                                {{ $column->name }}
                                            </span>

                                            <!-- Metadata Badge: Type, Weight, Max Score -->
                                            <div class="flex flex-wrap items-center justify-center gap-1 text-[11px] text-blue-100 font-mono">
                                                <span class="px-1.5 py-0.5 rounded bg-white/20 uppercase font-semibold text-[10px]">
                                                    {{ $column->type }}
                                                </span>
                                                <span class="font-bold">{{ number_format($column->weight, 0) }}%</span>
                                                <span>•</span>
                                                <span>{{ number_format($column->max_score, 0) }}</span>
                                            </div>

                                            <!-- Header Column Quick Actions (Edit/Delete) -->
                                            <div class="flex items-center gap-1 mt-1 opacity-70 hover:opacity-100 transition-opacity">
                                                <a 
                                                    href="{{ route('gradebooks.columns.edit', [$gradebook, $column]) }}" 
                                                    class="p-1 rounded text-white/80 hover:text-white hover:bg-white/20 transition-colors"
                                                    title="Edit Kolom {{ $column->name }}"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                                <button 
                                                    type="button" 
                                                    onclick="triggerDeleteColumn('{{ route('gradebooks.columns.destroy', [$gradebook, $column]) }}', '{{ addslashes($column->name) }}')"
                                                    class="p-1 rounded text-white/80 hover:text-rose-200 hover:bg-rose-500/30 transition-colors cursor-pointer"
                                                    title="Hapus Kolom {{ $column->name }}"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            @endif

                                <!-- Calculated Average Column Header -->
                                <th scope="col" class="min-w-[110px] px-3 py-3.5 text-center font-extrabold text-xs uppercase tracking-wider border-l border-blue-400/40 bg-[#0c4cb3] text-white">
                                    Rata-rata
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body: Students and Score Matrix Rows -->
                        <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                            @if ($students->isEmpty())
                                <tr>
                                    <td colspan="{{ $gradebook->assessmentColumns->count() + 3 }}" class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <span class="font-bold text-slate-600 text-sm">Belum Ada Siswa Terdaftar</span>
                                            <span class="text-xs text-slate-400">Kelas {{ $gradebook->classroom->name }} belum memiliki siswa terdaftar untuk tahun ajaran {{ $gradebook->academicYear->name }}.</span>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @foreach ($students as $index => $student)
                                <tr class="hover:bg-blue-50/40 transition-colors group" data-student-row="{{ $student->id }}">
                                    <!-- No -->
                                    <td class="w-12 px-3 py-2 text-center font-mono font-semibold text-slate-400 border-r border-slate-100 sticky left-0 z-10 bg-white group-hover:bg-blue-50/40 transition-colors">
                                        {{ $index + 1 }}
                                    </td>

                                    <!-- Student Name -->
                                    <td class="px-4 py-2 border-r border-slate-100 sticky left-12 z-10 bg-white group-hover:bg-blue-50/40 transition-colors shadow-[2px_0_5px_rgba(0,0,0,0.03)]">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[10px] flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($student->name, 0, 1)) }}
                                            </span>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-800 text-sm block truncate" title="{{ $student->name }}">
                                                    {{ $student->name }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 font-mono block">
                                                    NISN: {{ $student->nisn ?? '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Score Input Cells -->
                                    @if ($gradebook->assessmentColumns->isEmpty())
                                        <td class="p-2 text-center text-slate-400 text-xs italic border-r border-slate-100">
                                            -
                                        </td>
                                    @else
                                        @foreach ($gradebook->assessmentColumns as $column)
                                            @php
                                                $currentScore = $scoresMatrix[$student->id][$column->id] ?? null;
                                            @endphp
                                            <td class="p-1.5 text-center border-r border-slate-100/80">
                                                <input 
                                                    type="number" 
                                                    step="any" 
                                                    min="0" 
                                                    max="{{ $column->max_score }}"
                                                    name="scores[{{ $student->id }}][{{ $column->id }}]"
                                                    value="{{ $currentScore !== null ? $currentScore : '' }}"
                                                    data-student-id="{{ $student->id }}"
                                                    data-column-id="{{ $column->id }}"
                                                    data-max-score="{{ $column->max_score }}"
                                                    class="score-input w-20 py-1.5 px-2 text-center font-mono font-bold text-slate-800 text-sm bg-slate-50/80 hover:bg-white border border-slate-200 rounded-xl focus:bg-white focus:border-[#1363DF] focus:ring-2 focus:ring-blue-100 transition-all outline-hidden"
                                                    placeholder="-"
                                                    autocomplete="off"
                                                >
                                            </td>
                                        @endforeach
                                    @endif

                                    <!-- Calculated Average Cell -->
                                    <td class="p-2 text-center border-l border-slate-100 bg-slate-50/50">
                                        @php
                                            $avg = $studentAverages[$student->id] ?? null;
                                        @endphp
                                        <span 
                                            id="avg-{{ $student->id }}" 
                                            class="student-avg inline-flex items-center justify-center min-w-14 px-2.5 py-1 rounded-xl text-xs font-mono font-extrabold transition-all {{ $avg !== null ? ($avg >= 75 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200') : 'bg-slate-100 text-slate-400' }}"
                                        >
                                            {{ $avg !== null ? $avg : '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            @endif
                        </tbody>

                        <!-- Table Footer: Column Summary Averages -->
                        <tfoot class="bg-slate-50 text-slate-600 font-bold border-t border-slate-200 text-xs">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-right font-extrabold text-slate-700 sticky left-0 z-10 bg-slate-50 border-r border-slate-200">
                                    Rata-rata Kelas:
                                </td>
                                @if ($gradebook->assessmentColumns->isEmpty())
                                    <td class="px-3 py-3 text-center text-slate-400 font-mono border-r border-slate-200">-</td>
                                @else
                                    @foreach ($gradebook->assessmentColumns as $column)
                                        <td class="px-3 py-3 text-center font-mono font-extrabold border-r border-slate-200 text-slate-800" id="col-avg-{{ $column->id }}">
                                            @php
                                                $colScores = [];
                                                foreach ($students as $s) {
                                                    if (isset($scoresMatrix[$s->id][$column->id]) && $scoresMatrix[$s->id][$column->id] !== null) {
                                                        $colScores[] = (float) $scoresMatrix[$s->id][$column->id];
                                                    }
                                                }
                                                $colAverage = count($colScores) > 0 ? round(array_sum($colScores) / count($colScores), 1) : null;
                                            @endphp
                                            {{ $colAverage !== null ? $colAverage : '-' }}
                                        </td>
                                    @endforeach
                                @endif
                                <td class="px-3 py-3 text-center font-mono font-extrabold bg-blue-50/50 text-[#1363DF]" id="overall-class-avg">
                                    @php
                                        $validAverages = array_filter($studentAverages, fn($v) => $v !== null);
                                        $overallAvg = count($validAverages) > 0 ? round(array_sum($validAverages) / count($validAverages), 1) : null;
                                    @endphp
                                    {{ $overallAvg !== null ? $overallAvg : '-' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Bottom Sticky-friendly Save Controls inside the card -->
                <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-slate-500 font-medium">
                        Perubahan nilai dapat langsung disimpan dengan tombol di sebelah kanan.
                    </div>
                    <div class="flex items-center gap-3">
                        <button 
                            type="reset" 
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Reset Form
                        </button>
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            <span>Simpan Nilai</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
</div>

<!-- Standalone Delete Column Form (avoiding nested forms) -->
<form id="delete-column-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function triggerDeleteColumn(deleteUrl, columnName) {
        if (confirm(`Apakah Anda yakin ingin menghapus kolom penilaian "${columnName}"? Seluruh nilai siswa pada kolom ini akan ikut terhapus.`)) {
            const form = document.getElementById('delete-column-form');
            form.action = deleteUrl;
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const inputs = document.querySelectorAll('.score-input');
        const form = document.getElementById('score-matrix-form');

        // Instant Dynamic Average calculation on cell input
        inputs.forEach(input => {
            input.addEventListener('input', function () {
                const maxScore = parseFloat(this.dataset.maxScore);
                const val = this.value.trim();

                // Highlight validation errors visually
                if (val !== '' && !isNaN(val) && parseFloat(val) > maxScore) {
                    this.classList.add('border-rose-500', 'bg-rose-50', 'text-rose-700');
                    this.title = `Nilai melebihi skor maksimal (${maxScore})`;
                } else if (val !== '' && !isNaN(val) && parseFloat(val) < 0) {
                    this.classList.add('border-rose-500', 'bg-rose-50', 'text-rose-700');
                    this.title = 'Nilai tidak boleh bernilai negatif';
                } else {
                    this.classList.remove('border-rose-500', 'bg-rose-50', 'text-rose-700');
                    this.title = '';
                }

                // Recalculate row student average
                recalculateStudentAverage(this.dataset.studentId);
                // Recalculate column average
                recalculateColumnAverage(this.dataset.columnId);
            });

            // Keyboard navigation (Enter / Up / Down / Tab)
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    moveToAdjacentRow(this, 1);
                } else if (e.key === 'ArrowDown') {
                    moveToAdjacentRow(this, 1);
                } else if (e.key === 'ArrowUp') {
                    moveToAdjacentRow(this, -1);
                }
            });
        });

        // Keyboard Shortcut: Ctrl + S or Cmd + S to save scores immediately
        window.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                if (form) {
                    form.submit();
                }
            }
        });

        function moveToAdjacentRow(currentInput, direction) {
            const studentId = currentInput.dataset.studentId;
            const columnId = currentInput.dataset.columnId;
            const rows = Array.from(document.querySelectorAll('tbody tr'));
            const currentRow = currentInput.closest('tr');
            const currentIndex = rows.indexOf(currentRow);
            const targetIndex = currentIndex + direction;

            if (targetIndex >= 0 && targetIndex < rows.length) {
                const targetInput = rows[targetIndex].querySelector(`.score-input[data-column-id="${columnId}"]`);
                if (targetInput) {
                    targetInput.focus();
                    targetInput.select();
                }
            }
        }

        function recalculateStudentAverage(studentId) {
            const studentInputs = document.querySelectorAll(`.score-input[data-student-id="${studentId}"]`);
            let sum = 0;
            let count = 0;

            studentInputs.forEach(inp => {
                const val = inp.value.trim();
                if (val !== '' && !isNaN(val)) {
                    sum += parseFloat(val);
                    count++;
                }
            });

            const avgElement = document.getElementById(`avg-${studentId}`);
            if (!avgElement) return;

            if (count > 0) {
                const avg = (sum / count).toFixed(1);
                const displayVal = avg.endsWith('.0') ? parseInt(avg) : avg;
                avgElement.textContent = displayVal;

                if (parseFloat(avg) >= 75) {
                    avgElement.className = 'student-avg inline-flex items-center justify-center min-w-14 px-2.5 py-1 rounded-xl text-xs font-mono font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200';
                } else {
                    avgElement.className = 'student-avg inline-flex items-center justify-center min-w-14 px-2.5 py-1 rounded-xl text-xs font-mono font-extrabold bg-amber-50 text-amber-700 border border-amber-200';
                }
            } else {
                avgElement.textContent = '-';
                avgElement.className = 'student-avg inline-flex items-center justify-center min-w-14 px-2.5 py-1 rounded-xl text-xs font-mono font-extrabold bg-slate-100 text-slate-400';
            }

            recalculateOverallClassAverage();
        }

        function recalculateColumnAverage(columnId) {
            const colInputs = document.querySelectorAll(`.score-input[data-column-id="${columnId}"]`);
            let sum = 0;
            let count = 0;

            colInputs.forEach(inp => {
                const val = inp.value.trim();
                if (val !== '' && !isNaN(val)) {
                    sum += parseFloat(val);
                    count++;
                }
            });

            const colAvgElement = document.getElementById(`col-avg-${columnId}`);
            if (colAvgElement) {
                if (count > 0) {
                    const avg = (sum / count).toFixed(1);
                    colAvgElement.textContent = avg.endsWith('.0') ? parseInt(avg) : avg;
                } else {
                    colAvgElement.textContent = '-';
                }
            }
        }

        function recalculateOverallClassAverage() {
            const avgBadges = document.querySelectorAll('.student-avg');
            let sum = 0;
            let count = 0;

            avgBadges.forEach(badge => {
                const val = badge.textContent.trim();
                if (val !== '' && val !== '-' && !isNaN(val)) {
                    sum += parseFloat(val);
                    count++;
                }
            });

            const overallElem = document.getElementById('overall-class-avg');
            if (overallElem) {
                if (count > 0) {
                    const avg = (sum / count).toFixed(1);
                    overallElem.textContent = avg.endsWith('.0') ? parseInt(avg) : avg;
                } else {
                    overallElem.textContent = '-';
                }
            }
        }
    });
</script>
@endsection
