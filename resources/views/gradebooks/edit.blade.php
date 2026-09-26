@extends('layouts.teacher')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Portal</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('gradebooks.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Buku Nilai</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-800 font-bold">Edit Buku Nilai</span>
@endsection

@section('teacher_content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/80 shadow-xs">
        <!-- Header -->
        <div class="pb-6 mb-8 border-b border-slate-100 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    Edit Buku Nilai
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Perbarui informasi konfigurasi untuk buku nilai ini.
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
        <form action="{{ route('gradebooks.update', $gradebook) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Classroom & Subject (2 Cols) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Classroom -->
                <div>
                    <label for="classroom_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Kelas <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="classroom_id" 
                        id="classroom_id" 
                        required 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('classroom_id') border-rose-300 ring-rose-200 @enderror"
                    >
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ old('classroom_id', $gradebook->classroom_id) == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }} (Tingkat {{ $classroom->level }})
                            </option>
                        @endforeach
                    </select>
                    @error('classroom_id')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subject -->
                <div>
                    <label for="subject_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Mata Pelajaran <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="subject_id" 
                        id="subject_id" 
                        required 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('subject_id') border-rose-300 ring-rose-200 @enderror"
                    >
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id', $gradebook->subject_id) == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }} ({{ $subject->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Academic Year & Semester (2 Cols) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Academic Year -->
                <div>
                    <label for="academic_year_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Tahun Ajaran <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="academic_year_id" 
                        id="academic_year_id" 
                        required 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('academic_year_id') border-rose-300 ring-rose-200 @enderror"
                    >
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id', $gradebook->academic_year_id) == $year->id ? 'selected' : '' }}>
                                {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Semester -->
                <div>
                    <label for="semester" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Semester <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="semester" 
                        id="semester" 
                        required 
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('semester') border-rose-300 ring-rose-200 @enderror"
                    >
                        <option value="ganjil" {{ old('semester', $gradebook->semester) === 'ganjil' ? 'selected' : '' }}>
                            Semester Ganjil
                        </option>
                        <option value="genap" {{ old('semester', $gradebook->semester) === 'genap' ? 'selected' : '' }}>
                            Semester Genap
                        </option>
                    </select>
                    @error('semester')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Judul Buku Nilai
                </label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title', $gradebook->title) }}" 
                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all @error('title') border-rose-300 ring-rose-200 @enderror"
                >
                @error('title')
                    <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                @enderror
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
                    Perbarui Buku Nilai
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
