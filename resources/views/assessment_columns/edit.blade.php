@extends('layouts.teacher')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Portal</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('gradebooks.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Buku Nilai</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('gradebooks.show', $gradebook) }}" class="text-slate-400 hover:text-slate-600 transition-colors">{{ $gradebook->classroom->name }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-800 font-bold">Edit Kolom Penilaian</span>
@endsection

@section('teacher_content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/80 shadow-xs">
        <!-- Header -->
        <div class="pb-6 mb-8 border-b border-slate-100 flex items-center justify-between gap-4">
            <div>
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100 mb-2 inline-block">
                    {{ $gradebook->subject->name }} - {{ $gradebook->classroom->name }}
                </span>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    Edit Kolom Penilaian
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Perbarui nama, tipe, bobot, atau urutan kolom penilaian ini.
                </p>
            </div>
            <a 
                href="{{ route('gradebooks.show', $gradebook) }}" 
                class="px-4 py-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 text-xs font-semibold transition-colors"
            >
                Kembali
            </a>
        </div>

        <!-- Form -->
        <form action="{{ route('gradebooks.columns.update', [$gradebook, $column]) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Nama Kolom Penilaian <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="{{ old('name', $column->name) }}" 
                    required 
                    autofocus
                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('name') border-rose-300 ring-rose-200 @enderror"
                >
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type & Order (2 Cols) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Type -->
                <div>
                    <label for="type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Tipe Penilaian <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="type" 
                        id="type" 
                        required 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('type') border-rose-300 ring-rose-200 @enderror"
                    >
                        <option value="tugas" {{ old('type', $column->type) === 'tugas' ? 'selected' : '' }}>Tugas / Mandiri</option>
                        <option value="uh" {{ old('type', $column->type) === 'uh' ? 'selected' : '' }}>Ulangan Harian (UH)</option>
                        <option value="project" {{ old('type', $column->type) === 'project' ? 'selected' : '' }}>Project / Praktik</option>
                        <option value="ujian" {{ old('type', $column->type) === 'ujian' ? 'selected' : '' }}>Ujian (UTS / UAS / PAS)</option>
                        <option value="kuis" {{ old('type', $column->type) === 'kuis' ? 'selected' : '' }}>Kuis</option>
                        <option value="lainnya" {{ old('type', $column->type) === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('type')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label for="order" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Urutan Kolom <span class="text-slate-400 font-normal lowercase">(angka)</span>
                    </label>
                    <input 
                        type="number" 
                        name="order" 
                        id="order" 
                        value="{{ old('order', $column->order) }}" 
                        min="1" 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('order') border-rose-300 ring-rose-200 @enderror"
                    >
                    @error('order')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Weight & Max Score (2 Cols) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Weight -->
                <div>
                    <label for="weight" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Bobot Persentase (%) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            name="weight" 
                            id="weight" 
                            value="{{ old('weight', $column->weight) }}" 
                            min="0" 
                            max="100" 
                            step="0.01" 
                            required 
                            class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all pr-10 @error('weight') border-rose-300 ring-rose-200 @enderror"
                        >
                        <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 text-sm font-semibold pointer-events-none">%</span>
                    </div>
                    @error('weight')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Score -->
                <div>
                    <label for="max_score" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Skor Maksimal <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="max_score" 
                        id="max_score" 
                        value="{{ old('max_score', $column->max_score) }}" 
                        min="1" 
                        max="1000" 
                        step="1" 
                        required 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('max_score') border-rose-300 ring-rose-200 @enderror"
                    >
                    @error('max_score')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a 
                    href="{{ route('gradebooks.show', $gradebook) }}" 
                    class="px-5 py-3 rounded-2xl text-slate-600 hover:text-slate-900 font-bold text-xs sm:text-sm transition-colors"
                >
                    Batal
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-3 rounded-2xl bg-[#1363DF] hover:bg-blue-700 text-white font-extrabold text-xs sm:text-sm shadow-lg shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                >
                    Perbarui Kolom Penilaian
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
