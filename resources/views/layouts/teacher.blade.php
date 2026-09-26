@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#F4F7FC] flex flex-col lg:flex-row">
    <!-- Sidebar Navigation -->
    <aside class="w-full lg:w-72 bg-white border-r border-slate-200/80 shrink-0 flex flex-col justify-between p-5 lg:min-h-screen">
        <div>
            <!-- Branding -->
            <div class="flex items-center gap-3 px-2 py-3 mb-6 border-b border-slate-100">
                <div class="w-11 h-11 rounded-2xl bg-[#1363DF] flex items-center justify-center text-white shadow-md shadow-blue-500/25 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="overflow-hidden">
                    <span class="text-sm font-extrabold text-slate-900 block truncate leading-tight">Buku Nilai Guru</span>
                    <span class="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">Portal Pendidik</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-2">
                <!-- Dashboard Link -->
                <a 
                    href="{{ route('dashboard') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm transition-all {{ request()->routeIs('dashboard') ? 'bg-[#1363DF] text-white shadow-lg shadow-blue-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- Gradebook List Link -->
                <a 
                    href="{{ route('gradebooks.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm transition-all {{ request()->routeIs('gradebooks.index') || request()->routeIs('gradebooks.show') ? 'bg-[#1363DF] text-white shadow-lg shadow-blue-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Buku Nilai</span>
                </a>

                <!-- Create Gradebook Link -->
                <a 
                    href="{{ route('gradebooks.create') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm transition-all {{ request()->routeIs('gradebooks.create') ? 'bg-[#1363DF] text-white shadow-lg shadow-blue-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Buat Buku Nilai</span>
                </a>
            </nav>
        </div>

        <!-- Teacher Profile & Logout Card in Sidebar Bottom -->
        <div class="mt-6 pt-5 border-t border-slate-100">
            <div class="flex items-center gap-3 mb-4 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-extrabold text-sm border border-blue-200 shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <span class="text-xs font-bold text-slate-800 block truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[11px] text-slate-500 block truncate">{{ auth()->user()->email }}</span>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST" class="w-full">
                @csrf
                <button 
                    type="submit" 
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50/70 hover:bg-rose-100/70 border border-rose-200/80 transition-all cursor-pointer active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Keluar dari Akun</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <header class="bg-white border-b border-slate-200/80 px-6 lg:px-8 py-4 sticky top-0 z-20 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 font-medium">
                @yield('breadcrumbs')
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Aktif: {{ auth()->user()->nip ? 'NIP '.auth()->user()->nip : 'Guru' }}
                </span>
            </div>
        </header>

        <!-- Page Body -->
        <main class="flex-1 p-6 lg:p-8">
            <!-- Flash Notification Messages -->
            @if (session('status'))
                <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3 shadow-xs">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            @yield('teacher_content')
        </main>
    </div>
</div>
@endsection
