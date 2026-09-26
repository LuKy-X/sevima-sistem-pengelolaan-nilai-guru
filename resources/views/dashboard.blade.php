@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1363DF] flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-base font-bold text-slate-900 block leading-tight">Buku Nilai Digital Guru</span>
                        <span class="text-xs text-blue-600 font-semibold tracking-wide uppercase">Portal Pendidik</span>
                    </div>
                </div>

                <!-- Right Profile & Logout -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-sm font-bold text-slate-800 leading-tight">{{ $user->name }}</span>
                        <span class="text-xs text-slate-500 font-medium">NIP: {{ $user->nip ?? '-' }}</span>
                    </div>

                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm border border-blue-200">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    <!-- Logout Form -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200/80 transition-all cursor-pointer active:scale-95"
                            title="Keluar dari akun"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Hero Card -->
        <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-[#1363DF] to-blue-700 p-8 sm:p-10 text-white shadow-xl shadow-blue-500/15 mb-8">
            <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 max-w-2xl">
                <span class="inline-block px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold uppercase tracking-wider mb-3">
                    Berhasil Masuk
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Selamat Datang, {{ $user->name }}! 👋
                </h1>
                <p class="text-blue-100 text-sm sm:text-base mt-2 leading-relaxed">
                    Sesi Anda telah terautentikasi secara aman di sistem. Anda saat ini berada di halaman Dashboard Guru.
                </p>
            </div>
        </div>

        <!-- Authentication Verification Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1: User Profile Info -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Profil Guru</h3>
                        <p class="text-xs text-slate-500">Informasi identitas akun</p>
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Nama Lengkap</span>
                        <span class="font-semibold text-slate-800">{{ $user->name }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Email Akun</span>
                        <span class="font-semibold text-slate-800">{{ $user->email }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">NIP</span>
                        <span class="font-semibold text-slate-800 font-mono">{{ $user->nip ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Security & Session Status -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Status Keamanan</h3>
                        <p class="text-xs text-slate-500">Middleware & Sesi</p>
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500 text-xs">Auth Middleware</span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Terproteksi
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500 text-xs">Guard</span>
                        <span class="font-mono text-xs text-slate-700">web (session)</span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-500 text-xs">CSRF Protection</span>
                        <span class="text-xs font-semibold text-emerald-600">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Next Feature Notice -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Tahap Berikutnya</h3>
                            <p class="text-xs text-slate-500">Scope terisolasi</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Fitur Buku Nilai (Gradebook), Kolom Penilaian Dinamis, dan Matriks Nilai Siswa siap diimplementasikan pada tahap selanjutnya sesuai arsitektur.
                    </p>
                </div>

                <div class="pt-4 mt-4 border-t border-slate-100">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">
                        Sistem Siap Digunakan
                    </span>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
