@extends('layouts.teacher')

@section('breadcrumbs')
    <span class="text-slate-400">Portal</span>
    <span class="text-slate-300">/</span>
    <span class="text-slate-800 font-bold">Dashboard Guru</span>
@endsection

@section('teacher_content')
<div class="space-y-8">
    <!-- Welcome Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-[#1363DF] via-blue-600 to-indigo-700 p-8 sm:p-10 text-white shadow-xl shadow-blue-500/15">
        <div class="absolute -right-12 -bottom-12 w-80 h-80 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Portal Buku Nilai Digital
                </span>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight">
                    Selamat Datang, {{ $user->name }}! 👋
                </h1>
                <p class="text-blue-100 text-sm sm:text-base mt-2 leading-relaxed font-normal">
                    Kelola buku nilai digital untuk kelas dan mata pelajaran yang Anda ampu dengan mudah dan terstruktur.
                </p>
            </div>

            <div class="shrink-0">
                <a 
                    href="{{ route('gradebooks.create') }}" 
                    class="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-white text-[#1363DF] hover:bg-blue-50 font-extrabold text-sm shadow-lg shadow-black/10 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Buat Buku Nilai Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Stat 1: Total Gradebooks -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Buku Nilai Saya</span>
                <span class="text-2xl font-extrabold text-slate-800">{{ $totalGradebooks }}</span>
            </div>
        </div>

        <!-- Stat 2: Active Identity -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div class="overflow-hidden">
                <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Identitas Pengampu</span>
                <span class="text-sm font-bold text-slate-800 truncate block">{{ $user->nip ? 'NIP '.$user->nip : $user->name }}</span>
            </div>
        </div>

        <!-- Stat 3: Security & Session Status -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Status Akses</span>
                <span class="text-sm font-bold text-emerald-600 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Terverifikasi
                </span>
            </div>
        </div>
    </div>

    <!-- Gradebooks Section -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 mb-6 border-b border-slate-100">
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">
                    Daftar Buku Nilai Aktif
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Buku nilai digital yang terdaftar khusus pada akun Anda.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a 
                    href="{{ route('gradebooks.index') }}" 
                    class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline"
                >
                    Lihat Semua ({{ $totalGradebooks }}) &rarr;
                </a>
            </div>
        </div>

        @if ($recentGradebooks->isEmpty())
            <!-- Empty State -->
            <div class="text-center py-12 px-4">
                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">Belum Ada Buku Nilai</h3>
                <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                    Anda belum membuat buku nilai. Buat buku nilai pertama untuk mulai mengelola nilai siswa.
                </p>
                <a 
                    href="{{ route('gradebooks.create') }}" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Buat Buku Nilai Pertama</span>
                </a>
            </div>
        @else
            <!-- Gradebook Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($recentGradebooks as $gradebook)
                    <div class="rounded-2xl border border-slate-200/90 bg-white hover:border-blue-300 hover:shadow-md transition-all p-5 flex flex-col justify-between">
                        <div>
                            <!-- Header tags -->
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
                                    {{ $gradebook->classroom->name }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold">
                                    {{ $gradebook->academicYear->name }} • {{ ucfirst($gradebook->semester) }}
                                </span>
                            </div>

                            <!-- Subject & Title -->
                            <h3 class="font-extrabold text-slate-900 text-base leading-snug line-clamp-1 mb-1">
                                {{ $gradebook->subject->name }}
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $gradebook->title ?? 'Buku Nilai Mata Pelajaran' }}
                            </p>
                        </div>

                        <!-- Card Footer & Actions -->
                        <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <a 
                                href="{{ route('gradebooks.show', $gradebook) }}" 
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-[#1363DF] text-[#1363DF] hover:text-white font-bold text-xs transition-colors"
                            >
                                <span>Buka Buku Nilai</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>

                            <div class="flex items-center gap-1">
                                <a 
                                    href="{{ route('gradebooks.edit', $gradebook) }}" 
                                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" 
                                    title="Edit"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
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
                                        class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" 
                                        title="Hapus"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
