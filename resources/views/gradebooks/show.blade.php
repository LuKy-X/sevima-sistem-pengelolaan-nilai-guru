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
    <!-- Gradebook Header Card (Styling inspired by user mockup) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2.5 mb-2">
                <span class="px-3 py-1 rounded-xl bg-blue-50 text-[#1363DF] font-bold text-xs border border-blue-100">
                    Kelas {{ $gradebook->classroom->name }}
                </span>
                <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 font-semibold text-xs">
                    {{ $gradebook->academicYear->name }} • {{ ucfirst($gradebook->semester) }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $gradebook->subject->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $gradebook->title ?? 'Buku Nilai Mata Pelajaran' }}
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3 shrink-0">
            <a 
                href="{{ route('gradebooks.edit', $gradebook) }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Edit Konfigurasi</span>
            </a>

            <form 
                action="{{ route('gradebooks.destroy', $gradebook) }}" 
                method="POST" 
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku nilai ini? Data terkait akan ikut terhapus.')" 
                class="inline"
            >
                @csrf
                @method('DELETE')
                <button 
                    type="submit" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50/70 hover:bg-rose-100 text-rose-600 font-bold text-xs shadow-xs transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span>Hapus</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Metadata Details Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
            <span class="text-xs text-slate-400 block font-medium">Mata Pelajaran</span>
            <span class="text-sm font-bold text-slate-800">{{ $gradebook->subject->code }} - {{ $gradebook->subject->name }}</span>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
            <span class="text-xs text-slate-400 block font-medium">Kelas / Tingkat</span>
            <span class="text-sm font-bold text-slate-800">{{ $gradebook->classroom->name }} (Tingkat {{ $gradebook->classroom->level }})</span>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
            <span class="text-xs text-slate-400 block font-medium">Periode Akademik</span>
            <span class="text-sm font-bold text-slate-800">{{ $gradebook->academicYear->name }} • {{ ucfirst($gradebook->semester) }}</span>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
            <span class="text-xs text-slate-400 block font-medium">Guru Pengampu</span>
            <span class="text-sm font-bold text-slate-800">{{ $gradebook->user->name }}</span>
        </div>
    </div>

    <!-- Students in Classroom Section -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">
                    Daftar Siswa di Kelas Ini
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Siswa terdaftar pada kelas {{ $gradebook->classroom->name }} tahun ajaran {{ $gradebook->academicYear->name }}.
                </p>
            </div>
            <span class="px-3 py-1.5 rounded-xl bg-blue-50 text-[#1363DF] font-bold text-xs">
                Total: {{ $students->count() }} Siswa
            </span>
        </div>

        @if ($students->isEmpty())
            <div class="py-8 text-center text-sm text-slate-400">
                Belum ada siswa yang terhubung dengan kelas ini untuk tahun ajaran {{ $gradebook->academicYear->name }}.
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl border border-slate-100">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 w-16">No</th>
                            <th class="px-5 py-3.5">NISN</th>
                            <th class="px-5 py-3.5">Nama Siswa</th>
                            <th class="px-5 py-3.5">Jenis Kelamin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($students as $index => $student)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-5 py-3 font-semibold text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-5 py-3 font-mono font-medium text-slate-600">{{ $student->nisn }}</td>
                                <td class="px-5 py-3 font-bold text-slate-800">{{ $student->name }}</td>
                                <td class="px-5 py-3">
                                    @if ($student->gender === 'L')
                                        <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold text-xs">Laki-laki</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-pink-50 text-pink-700 font-semibold text-xs">Perempuan</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Notice for Next Stage -->
    <div class="p-5 rounded-2xl bg-blue-50/60 border border-blue-100 flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-xs leading-relaxed text-slate-600">
            <span class="font-bold text-slate-800">Catatan Tahap Pengembangan:</span>
            Detail buku nilai saat ini menampilkan data master buku nilai dan siswa. Fitur kolom penilaian dinamis (Assessment Columns), matriks spreadsheet input nilai, dan tugas/rubrik akan dihubungkan pada tahap selanjutnya sesuai rencana.
        </div>
    </div>
</div>
@endsection
