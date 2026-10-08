@extends('layouts.teacher')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Portal</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('gradebooks.show', $gradebook) }}" class="text-slate-400 hover:text-slate-600 transition-colors">{{ $gradebook->classroom->name }} ({{ $gradebook->subject->name }})</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-800 font-bold">Rubrik {{ $column->name }}</span>
@endsection

@section('teacher_content')
<div class="space-y-8">
    <!-- Page Header (Matches Reference: "Manajemen Rubrik Nilai / Kelola Rubrik Nilai") -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Manajemen Rubrik Nilai
                </h1>
                <p class="text-sm text-slate-500 mt-1 font-medium">
                    Kelola Rubrik Nilai • Kolom: <span class="font-bold text-slate-700">{{ $column->name }}</span> ({{ $gradebook->classroom->name }} - {{ $gradebook->subject->name }})
                </p>
            </div>

            <!-- Quick Navigation Back to Score Matrix -->
            <div class="flex items-center gap-2">
                <a 
                    href="{{ route('gradebooks.show', $gradebook) }}" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Kembali ke Buku Nilai</span>
                </a>
            </div>
        </div>
        <hr class="mt-4 border-slate-200/90">
    </div>

    <!-- Mode Selector Tabs: Struktur Rubrik vs Penilaian Siswa -->
    <div class="flex items-center gap-3 border-b border-slate-200">
        <button 
            type="button" 
            onclick="switchTab('struktur')" 
            id="tab-btn-struktur"
            class="px-5 py-3 font-bold text-sm border-b-2 border-[#1363DF] text-[#1363DF] flex items-center gap-2 transition-colors cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span>Struktur Kriteria & Bobot</span>
        </button>

        <button 
            type="button" 
            onclick="switchTab('penilaian')" 
            id="tab-btn-penilaian"
            class="px-5 py-3 font-bold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition-colors cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span>Penilaian Siswa (4 Level)</span>
            @if ($rubric->isConfigured())
                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold">Siap Dinilai</span>
            @else
                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[11px] font-bold">Bobot &ne; 100%</span>
            @endif
        </button>
    </div>

    <!-- TAB 1: STRUKTUR RUBRIK (EXACT VISUAL REPLICA OF USER REFERENCE SCREENSHOT) -->
    <div id="tab-content-struktur" class="space-y-6">
        <!-- Main Rubric Container Card -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
            <!-- Form for Updating Rubric Information and Criteria -->
            <form id="rubric-update-form" action="{{ route('gradebooks.columns.rubric.update', [$gradebook, $column, $rubric]) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Blue Top Header Banner (Matches Reference) -->
                <div class="bg-[#0B4BA6] px-6 py-5 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                name="name" 
                                value="{{ old('name', $rubric->name) }}" 
                                class="text-xl sm:text-2xl font-extrabold bg-transparent text-white border-b border-transparent hover:border-blue-300 focus:border-white focus:outline-none w-full tracking-tight transition-colors"
                                placeholder="Rubrik Nilai {{ $column->name }}"
                                required
                            >
                            <input 
                                type="text" 
                                name="description" 
                                value="{{ old('description', $rubric->description ?? 'Isi form berikut untuk memanajemen kolom tugas atau remidi pada buku nilai') }}" 
                                class="text-xs sm:text-sm text-blue-100 bg-transparent border-b border-transparent hover:border-blue-300 focus:border-white focus:outline-none w-full mt-1 transition-colors"
                                placeholder="Isi form berikut untuk memanajemen kolom tugas atau remidi pada buku nilai"
                            >
                        </div>
                    </div>
                </div>

                <!-- Criteria Table (Faithful to Reference Table with Header, Rows, Total, and Add Button) -->
                <div class="p-6">
                    <div class="relative rounded-xl border border-blue-200/60 overflow-hidden shadow-xs">
                        <table class="w-full border-collapse text-left">
                            <!-- Table Header: Deep Blue with White Text -->
                            <thead>
                                <tr class="bg-[#0B4BA6] text-white text-sm sm:text-base">
                                    <th scope="col" class="py-4 px-6 font-bold">
                                        Kriteria Penilaian
                                    </th>
                                    <th scope="col" class="py-4 px-6 text-center font-bold w-36 sm:w-44 border-l border-blue-400/30">
                                        Nilai / Bobot (%)
                                    </th>
                                    <th scope="col" class="py-4 px-4 text-center font-bold w-20 border-l border-blue-400/30">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <!-- Table Body: Criteria Rows -->
                            <tbody class="divide-y divide-slate-200/80 bg-white text-slate-800 text-sm sm:text-base">
                                @if ($rubric->criteria->isEmpty())
                                    <tr>
                                        <td colspan="3" class="py-12 text-center text-slate-400">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="font-bold text-slate-600 text-sm">Belum Ada Kriteria Rubrik</span>
                                                <span class="text-xs text-slate-400">Klik tombol "+" di samping atau tombol Tambah Kriteria di bawah untuk mulai menambahkan.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    @foreach ($rubric->criteria as $idx => $criterion)
                                        <tr class="hover:bg-blue-50/30 transition-colors group">
                                            <!-- Kriteria Penilaian Column -->
                                            <td class="py-4 px-6">
                                                <div class="flex flex-col gap-1">
                                                    <input 
                                                        type="hidden" 
                                                        name="criteria[{{ $idx }}][id]" 
                                                        value="{{ $criterion->id }}"
                                                    >
                                                    <input 
                                                        type="text" 
                                                        name="criteria[{{ $idx }}][name]" 
                                                        value="{{ old("criteria.{$idx}.name", $criterion->name) }}" 
                                                        class="font-semibold text-slate-800 text-sm sm:text-base bg-transparent border border-transparent hover:border-slate-300 focus:border-[#1363DF] focus:bg-white rounded-lg px-2 py-1 w-full transition-all"
                                                        placeholder="Nama kriteria penilaian"
                                                        required
                                                    >
                                                    
                                                    <!-- Expand / Collapse 4 Levels Trigger -->
                                                    <button 
                                                        type="button" 
                                                        onclick="toggleLevels('criterion-levels-{{ $criterion->id }}')" 
                                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1363DF] hover:text-blue-800 px-2 py-0.5 mt-0.5 rounded transition-colors w-fit cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                        <span>Lihat & Sesuaikan 4 Level Penilaian</span>
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Nilai / Bobot Column -->
                                            <td class="py-4 px-6 text-center border-l border-slate-200/80 bg-slate-50/50">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <input 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0" 
                                                        max="100" 
                                                        name="criteria[{{ $idx }}][weight]" 
                                                        value="{{ old("criteria.{$idx}.weight", $criterion->weight) }}" 
                                                        class="w-24 text-center font-bold text-slate-900 text-base bg-white border border-slate-200 focus:border-[#1363DF] rounded-xl py-1.5 shadow-2xs focus:outline-none"
                                                        required
                                                    >
                                                    <span class="text-sm font-bold text-slate-400">%</span>
                                                </div>
                                            </td>

                                            <!-- Aksi Column -->
                                            <td class="py-4 px-4 text-center border-l border-slate-200/80">
                                                <button 
                                                    type="button" 
                                                    onclick="triggerDeleteCriterion('{{ route('gradebooks.columns.rubric.criteria.destroy', [$gradebook, $column, $rubric, $criterion]) }}', '{{ addslashes($criterion->name) }}')"
                                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Kriteria"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Expandable Drawer for 4 Performance Levels (Feature Tahap 1 & 2 integration) -->
                                        <tr id="criterion-levels-{{ $criterion->id }}" class="hidden bg-slate-50/70 border-b border-slate-200">
                                            <td colspan="3" class="p-5">
                                                <div class="bg-white rounded-xl p-4 border border-blue-100 shadow-2xs space-y-3">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs font-extrabold uppercase tracking-wider text-[#1363DF]">
                                                            4 Level Penilaian: {{ $criterion->name }}
                                                        </span>
                                                        <span class="text-[11px] text-slate-400">Guru dapat mengubah deskripsi dan skor setiap level di bawah ini</span>
                                                    </div>

                                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                                        @foreach ($criterion->levels as $level)
                                                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                                                                <div>
                                                                    <div class="flex items-center justify-between mb-1.5">
                                                                        <span class="px-2 py-0.5 rounded-md bg-blue-100 text-[#1363DF] font-bold text-[11px]">
                                                                            Level {{ $level->level_number }}
                                                                        </span>
                                                                        <span class="font-bold text-xs text-slate-700">
                                                                            Skor: {{ number_format($level->score, 0) }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="font-bold text-xs text-slate-800 mb-1">
                                                                        {{ $level->name }}
                                                                    </div>
                                                                    <p class="text-[11px] text-slate-600 line-clamp-3 leading-relaxed">
                                                                        {{ $level->description }}
                                                                    </p>
                                                                </div>

                                                                <button 
                                                                    type="button" 
                                                                    onclick="openEditLevelModal('{{ route('gradebooks.columns.rubric.criteria.levels.update', [$gradebook, $column, $rubric, $criterion, $level]) }}', '{{ addslashes($level->name) }}', '{{ $level->score }}', '{{ addslashes($level->description) }}', '{{ $level->level_number }}')"
                                                                    class="mt-3 text-[11px] font-bold text-blue-600 hover:text-blue-800 text-center py-1 bg-white hover:bg-blue-50 border border-slate-200 rounded-lg transition-colors cursor-pointer"
                                                                >
                                                                    Ubah Deskripsi
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>

                            <!-- Total Row: Solid Blue with White Text (Matches Reference) -->
                            <tfoot>
                                <tr class="bg-[#0B4BA6] text-white text-base font-bold">
                                    <td class="py-4 px-6 flex items-center justify-between">
                                        <span>Total</span>
                                        @if (abs($rubric->totalWeight() - 100) < 0.01)
                                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/30 text-emerald-100 font-semibold border border-emerald-400/40">
                                                Tepat 100% (Siap Digunakan)
                                            </span>
                                        @elseif ($rubric->totalWeight() > 100)
                                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-rose-500/30 text-rose-100 font-semibold border border-rose-400/40">
                                                Melebihi 100% (Kelebihan {{ $rubric->totalWeight() - 100 }}%)
                                            </span>
                                        @else
                                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/30 text-amber-100 font-semibold border border-amber-400/40">
                                                Sisa Bobot Tersedia: {{ 100 - $rubric->totalWeight() }}%
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-center border-l border-blue-400/30 text-lg">
                                        <span id="rubric-total-weight-display">{{ number_format($rubric->totalWeight(), 0) }}</span>
                                    </td>
                                    <td class="py-4 px-4 text-center border-l border-blue-400/30">
                                        <!-- Placeholder alignment for action column -->
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Action Bar: Add Criterion Button & Primary "Simpan Rubrik" Button (Matches Reference) -->
                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <button 
                            type="button" 
                            onclick="openAddCriterionModal()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-[#1363DF] hover:bg-blue-100 font-bold text-xs transition-colors cursor-pointer w-full sm:w-auto justify-center"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Tambah Kriteria Baru</span>
                        </button>

                        <button 
                            type="submit" 
                            form="rubric-update-form"
                            class="inline-flex items-center justify-center gap-2 px-7 py-3 rounded-xl bg-[#1E78F6] hover:bg-[#0B4BA6] text-white font-extrabold text-sm shadow-md shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer w-full sm:w-auto"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Simpan Rubrik</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 2: PENILAIAN SISWA MENGGUNAKAN 4 LEVEL (Tahap 2 Backend Logic Integration) -->
    <div id="tab-content-penilaian" class="hidden space-y-6">
        @if (! $rubric->isConfigured())
            <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-4">
                <svg class="w-6 h-6 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="font-extrabold text-sm sm:text-base">Rubrik Belum Dapat Digunakan untuk Penilaian</h3>
                    <p class="text-xs sm:text-sm text-amber-700 mt-1">
                        Sesuai aturan sistem, total bobot seluruh kriteria harus tepat bernilai 100%. Saat ini total bobot adalah <span class="font-bold font-mono">{{ $rubric->totalWeight() }}%</span>.
                    </p>
                    <button 
                        type="button" 
                        onclick="switchTab('struktur')" 
                        class="mt-3 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors cursor-pointer"
                    >
                        Sesuaikan Bobot Kriteria
                    </button>
                </div>
            </div>
        @else
            <!-- Reference Guide Card: Kriteria & 4 Level Pencapaian (Collapsible) -->
            <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 sm:p-5">
                <div class="flex items-center justify-between cursor-pointer select-none" onclick="toggleReferenceGuide()">
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 rounded-lg bg-[#1363DF] text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Panduan Kriteria & 4 Level Referensi Pencapaian</h4>
                            <p class="text-[11px] text-slate-500">Klik untuk melihat deskripsi acuan Level 1 - 4 untuk setiap kriteria penilaian.</p>
                        </div>
                    </div>
                    <span id="guide-chevron" class="text-slate-400 font-bold text-xs flex items-center gap-1 transition-transform">
                        <span>Lihat Deskripsi</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </span>
                </div>

                <div id="reference-guide-content" class="hidden mt-4 pt-4 border-t border-blue-200/60 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($rubric->criteria as $criterion)
                            <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                    <span class="font-extrabold text-slate-800 text-xs sm:text-sm">{{ $criterion->name }}</span>
                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[10px] font-mono font-bold">Bobot: {{ number_format($criterion->weight, 0) }}%</span>
                                </div>
                                <div class="mt-2.5 space-y-1.5">
                                    @foreach ($criterion->levels as $lvl)
                                        <div class="text-[11px] p-2 rounded-lg bg-slate-50 border border-slate-100 flex flex-col gap-0.5">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-slate-700">L{{ $lvl->level_number }}: {{ $lvl->name }}</span>
                                                <span class="font-mono font-bold text-blue-600">Ref: {{ number_format($lvl->score, 0) }}</span>
                                            </div>
                                            <p class="text-slate-500 text-[10px] leading-relaxed">{{ $lvl->description ?: 'Belum ada deskripsi khusus' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Student Scoring Spreadsheet / Matrix Form -->
            <form id="student-rubric-scores-form" action="{{ route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]) }}" method="POST">
                @csrf

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="font-extrabold text-slate-800 text-base">Evaluasi Rubrik Siswa</h2>
                            <p class="text-xs text-slate-500">Pilih level sebagai referensi cepat, lalu sesuaikan nilai aktual secara bebas (misal: 72, 83, 87.5). Nilai akhir otomatis tersinkronisasi ke buku nilai.</p>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                <span>Simpan & Sinkronkan Nilai</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="rubric-table" data-column-max="{{ $column->max_score ?? 100 }}" class="w-full border-collapse text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-[#0B4BA6] text-white">
                                    <th class="w-12 px-3 py-3.5 text-center font-bold">No</th>
                                    <th class="min-w-[200px] px-4 py-3.5 font-bold">Nama Siswa</th>
                                    @foreach ($rubric->criteria as $criterion)
                                        <th class="min-w-[220px] px-3 py-3 text-center border-l border-blue-400/30">
                                            <div class="font-bold text-xs sm:text-sm">{{ $criterion->name }}</div>
                                            <div class="text-[10px] text-blue-100 font-mono">Bobot: {{ number_format($criterion->weight, 0) }}%</div>
                                        </th>
                                    @endforeach
                                    <th class="min-w-[120px] px-3 py-3.5 text-center font-bold border-l border-blue-400/30 bg-[#08387F]">
                                        Nilai Rubrik
                                    </th>
                                    <th class="min-w-[100px] px-3 py-3.5 text-center font-bold border-l border-blue-400/30 bg-[#08387F]">
                                        Progress
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($students as $sIdx => $student)
                                    @php
                                        $summary = $studentSummaries[$student->id] ?? null;
                                        $finalScore = $summary['final_score'] ?? null;
                                        $completion = $summary['completion_percentage'] ?? 0;
                                    @endphp
                                    <tr class="hover:bg-blue-50/30 transition-colors">
                                        <td class="px-3 py-3 text-center text-slate-400 font-mono font-bold">{{ $sIdx + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <span class="font-bold text-slate-800 block">{{ $student->name }}</span>
                                            <span class="text-[11px] text-slate-400 font-mono">{{ $student->nisn ?? 'NISN: -' }}</span>
                                        </td>
                                        @foreach ($rubric->criteria as $criterion)
                                            @php
                                                $studentScore = $criterion->studentScores->where('student_id', $student->id)->first();
                                                $selectedLevelId = $studentScore?->rubric_level_id;
                                                $actualScore = $studentScore?->score !== null ? (float) $studentScore->score : ($studentScore?->level?->score !== null ? (float) $studentScore->level->score : null);
                                            @endphp
                                            <td class="px-3 py-3 border-l border-slate-100 bg-white">
                                                <div class="flex flex-col gap-1.5 min-w-[180px]">
                                                    <!-- Level Reference Shortcut -->
                                                    <select 
                                                        class="level-shortcut-select w-full text-[11px] font-semibold text-slate-700 rounded-lg border-slate-200 bg-slate-50 hover:bg-slate-100/80 focus:border-[#1363DF] focus:bg-white py-1 px-1.5 cursor-pointer transition-colors"
                                                        data-student-id="{{ $student->id }}"
                                                        data-criterion-id="{{ $criterion->id }}"
                                                        onchange="applyLevelShortcut(this)"
                                                        title="Pilih level sebagai acuan skor otomatis"
                                                    >
                                                        <option value="" data-score="">-- Acuan Level (Opsional) --</option>
                                                        @foreach ($criterion->levels as $level)
                                                            <option 
                                                                value="{{ $level->id }}" 
                                                                data-score="{{ $level->score }}"
                                                                {{ $selectedLevelId == $level->id ? 'selected' : '' }}
                                                            >
                                                                L{{ $level->level_number }}: {{ $level->name }} ({{ number_format($level->score, 0) }})
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <!-- Hidden input for selected level_id (nullable) -->
                                                    <input 
                                                        type="hidden" 
                                                        name="scores[{{ $student->id }}][{{ $criterion->id }}][level_id]" 
                                                        id="level-id-{{ $student->id }}-{{ $criterion->id }}" 
                                                        value="{{ $selectedLevelId }}"
                                                    >

                                                    <!-- Actual Numerical Score Input (Full Teacher Control) -->
                                                    <div class="relative flex items-center">
                                                        <span class="absolute left-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-tight select-none">Nilai:</span>
                                                        <input 
                                                            type="number" 
                                                            step="0.01" 
                                                            min="0" 
                                                            max="1000" 
                                                            name="scores[{{ $student->id }}][{{ $criterion->id }}][score]" 
                                                            id="score-input-{{ $student->id }}-{{ $criterion->id }}" 
                                                            value="{{ $actualScore !== null ? (fmod($actualScore, 1) === 0.0 ? number_format($actualScore, 0) : $actualScore) : '' }}" 
                                                            placeholder="0 - 100" 
                                                            class="rubric-actual-score-input w-full text-xs font-extrabold font-mono text-right pl-12 pr-2.5 py-1.5 rounded-lg border border-slate-200 bg-white focus:border-[#1363DF] focus:ring-1 focus:ring-[#1363DF] focus:bg-blue-50/20 transition-all"
                                                            data-student-id="{{ $student->id }}"
                                                            data-criterion-id="{{ $criterion->id }}"
                                                            data-weight="{{ $criterion->weight }}"
                                                            oninput="onRubricScoreInput(this)"
                                                            title="Nilai aktual kriteria (dapat diisi angka bebas)"
                                                        >
                                                    </div>
                                                </div>
                                            </td>
                                        @endforeach
                                        <td class="px-3 py-3 text-center border-l border-slate-100 font-mono font-extrabold text-sm bg-blue-50/30">
                                            <span id="rubric-final-score-{{ $student->id }}" class="{{ $finalScore !== null ? 'text-blue-700' : 'text-slate-300' }}">
                                                {{ $finalScore !== null ? number_format($finalScore, 1) : '-' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-center border-l border-slate-100">
                                            <span id="rubric-progress-{{ $student->id }}" class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $completion >= 100 ? 'bg-emerald-100 text-emerald-800' : ($completion > 0 ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-500') }}">
                                                {{ round($completion) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Save & Sync Actions inside card -->
                    <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-500 font-medium">
                            Nilai akhir kriteria dan total rubrik otomatis dikalkulasi dan disinkronkan ke tabel nilai siswa.
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
                                <span>Simpan & Sinkronkan Nilai</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

<!-- MODAL: Tambah Kriteria Baru -->
<div id="add-criterion-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-800 text-base">Tambah Kriteria Penilaian</h3>
            <button type="button" onclick="closeAddCriterionModal()" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form action="{{ route('gradebooks.columns.rubric.criteria.store', [$gradebook, $column, $rubric]) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Kriteria</label>
                <input 
                    type="text" 
                    name="name" 
                    required 
                    placeholder="Contoh: Kualitas Kode / Jawaban Benar"
                    class="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#1363DF] focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Bobot Kriteria (%)</label>
                <div class="relative">
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        max="100" 
                        name="weight" 
                        required 
                        value="{{ max(0, 100 - $rubric->totalWeight()) }}"
                        placeholder="Contoh: 30"
                        class="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#1363DF] focus:outline-none"
                    >
                    <span class="absolute right-4 top-2.5 text-sm font-bold text-slate-400">%</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Sisa bobot yang tersedia: {{ max(0, 100 - $rubric->totalWeight()) }}%</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi (Opsional)</label>
                <textarea 
                    name="description" 
                    rows="2" 
                    placeholder="Panduan umum mengenai kriteria ini..."
                    class="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2 focus:border-[#1363DF] focus:outline-none"
                ></textarea>
            </div>

            <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 text-[11px] text-blue-700 leading-relaxed">
                Setiap kriteria yang dibuat akan otomatis dilengkapi dengan 4 level penilaian standar (Level 1 s/d Level 4).
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button 
                    type="button" 
                    onclick="closeAddCriterionModal()" 
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-bold text-xs transition-colors shadow-xs cursor-pointer"
                >
                    Simpan Kriteria
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Ubah Level Penilaian -->
<div id="edit-level-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-800 text-base" id="modal-level-title">Ubah Level Penilaian</h3>
            <button type="button" onclick="closeEditLevelModal()" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="edit-level-form" action="" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Level</label>
                <input 
                    type="text" 
                    name="name" 
                    id="modal-level-name" 
                    required 
                    class="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#1363DF] focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nilai Skor</label>
                <input 
                    type="number" 
                    step="0.01" 
                    min="0" 
                    max="1000" 
                    name="score" 
                    id="modal-level-score" 
                    required 
                    class="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-[#1363DF] focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Kualifikasi Siswa</label>
                <textarea 
                    name="description" 
                    id="modal-level-description" 
                    rows="3" 
                    class="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2 focus:border-[#1363DF] focus:outline-none"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button 
                    type="button" 
                    onclick="closeEditLevelModal()" 
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-bold text-xs transition-colors shadow-xs cursor-pointer"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Criterion Form -->
<form id="delete-criterion-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    function switchTab(tab) {
        const tabStruktur = document.getElementById('tab-content-struktur');
        const tabPenilaian = document.getElementById('tab-content-penilaian');
        const btnStruktur = document.getElementById('tab-btn-struktur');
        const btnPenilaian = document.getElementById('tab-btn-penilaian');

        if (tab === 'struktur') {
            tabStruktur.classList.remove('hidden');
            tabPenilaian.classList.add('hidden');
            btnStruktur.classList.add('border-[#1363DF]', 'text-[#1363DF]');
            btnStruktur.classList.remove('border-transparent', 'text-slate-500');
            btnPenilaian.classList.remove('border-[#1363DF]', 'text-[#1363DF]');
            btnPenilaian.classList.add('border-transparent', 'text-slate-500');
        } else {
            tabStruktur.classList.add('hidden');
            tabPenilaian.classList.remove('hidden');
            btnPenilaian.classList.add('border-[#1363DF]', 'text-[#1363DF]');
            btnPenilaian.classList.remove('border-transparent', 'text-slate-500');
            btnStruktur.classList.remove('border-[#1363DF]', 'text-[#1363DF]');
            btnStruktur.classList.add('border-transparent', 'text-slate-500');
        }
    }

    function toggleLevels(id) {
        const row = document.getElementById(id);
        if (row) {
            row.classList.toggle('hidden');
        }
    }

    function openAddCriterionModal() {
        document.getElementById('add-criterion-modal').classList.remove('hidden');
    }

    function closeAddCriterionModal() {
        document.getElementById('add-criterion-modal').classList.add('hidden');
    }

    function openEditLevelModal(actionUrl, name, score, description, levelNumber) {
        document.getElementById('modal-level-title').innerText = 'Ubah Level ' + levelNumber + ' (' + name + ')';
        document.getElementById('edit-level-form').action = actionUrl;
        document.getElementById('modal-level-name').value = name;
        document.getElementById('modal-level-score').value = score;
        document.getElementById('modal-level-description').value = description;
        document.getElementById('edit-level-modal').classList.remove('hidden');
    }

    function closeEditLevelModal() {
        document.getElementById('edit-level-modal').classList.add('hidden');
    }

    function toggleReferenceGuide() {
        const content = document.getElementById('reference-guide-content');
        const chevron = document.getElementById('guide-chevron');
        if (content) {
            content.classList.toggle('hidden');
        }
        if (chevron) {
            chevron.classList.toggle('rotate-180');
        }
    }

    function applyLevelShortcut(selectElem) {
        const studentId = selectElem.dataset.studentId;
        const criterionId = selectElem.dataset.criterionId;
        const selectedOption = selectElem.options[selectElem.selectedIndex];
        const levelId = selectElem.value;
        const levelScore = selectedOption ? selectedOption.dataset.score : null;

        const levelIdInput = document.getElementById(`level-id-${studentId}-${criterionId}`);
        const scoreInput = document.getElementById(`score-input-${studentId}-${criterionId}`);

        if (levelIdInput) {
            levelIdInput.value = levelId;
        }

        if (scoreInput) {
            if (levelScore !== '' && levelScore !== undefined && levelScore !== null) {
                const numericScore = parseFloat(levelScore);
                scoreInput.value = Number.isInteger(numericScore) ? numericScore : numericScore.toFixed(1);
            }
            recalculateStudentRubric(studentId);
        }
    }

    function onRubricScoreInput(inputElem) {
        const studentId = inputElem.dataset.studentId;
        recalculateStudentRubric(studentId);
    }

    function recalculateStudentRubric(studentId) {
        const scoreInputs = document.querySelectorAll(`.rubric-actual-score-input[data-student-id="${studentId}"]`);
        let weightedSum = 0;
        let availableWeight = 0;
        let totalConfiguredWeight = 0;
        let evaluatedCount = 0;

        scoreInputs.forEach(inp => {
            const weight = parseFloat(inp.dataset.weight) || 0;
            const val = inp.value.trim();
            totalConfiguredWeight += weight;

            if (val !== '' && !isNaN(val)) {
                const numericVal = parseFloat(val);
                if (numericVal >= 0) {
                    weightedSum += numericVal * (weight / 100);
                    availableWeight += weight;
                    evaluatedCount++;
                }
            }
        });

        const finalScoreElem = document.getElementById(`rubric-final-score-${studentId}`);
        const progressElem = document.getElementById(`rubric-progress-${studentId}`);

        if (finalScoreElem) {
            if (availableWeight > 0) {
                const tableElem = document.getElementById('rubric-table');
                const parentMax = parseFloat(tableElem?.dataset?.columnMax) || 100;
                let proportionalScore = weightedSum / (availableWeight / 100);
                let finalScore = proportionalScore;
                if (Math.abs(parentMax - 100) > 0.001) {
                    finalScore = (proportionalScore / 100) * parentMax;
                }
                const formatted = finalScore.toFixed(1).replace(/\.0$/, '');
                finalScoreElem.textContent = formatted;
                finalScoreElem.className = 'text-blue-700 font-extrabold';
            } else {
                finalScoreElem.textContent = '-';
                finalScoreElem.className = 'text-slate-300 font-extrabold';
            }
        }

        if (progressElem) {
            const percentage = totalConfiguredWeight > 0 ? Math.round((availableWeight / totalConfiguredWeight) * 100) : 0;
            progressElem.textContent = `${percentage}%`;
            if (percentage >= 100) {
                progressElem.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800';
            } else if (percentage > 0) {
                progressElem.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800';
            } else {
                progressElem.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500';
            }
        }
    }

    function triggerDeleteCriterion(url, name) {
        if (confirm('Apakah Anda yakin ingin menghapus kriteria "' + name + '"?')) {
            const form = document.getElementById('delete-criterion-form');
            form.action = url;
            form.submit();
        }
    }
</script>
@endsection
