@extends('layouts.teacher')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Portal</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-800 font-bold">Semua Buku Nilai</span>
@endsection

@section('teacher_content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Buku Nilai Digital
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Daftar seluruh buku nilai yang Anda kelola untuk kelas dan mata pelajaran aktif.
            </p>
        </div>

        <div class="shrink-0">
            <a 
                href="{{ route('gradebooks.create') }}" 
                class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-[#1363DF] hover:bg-blue-700 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Buat Buku Nilai Baru</span>
            </a>
        </div>
    </div>

    <!-- Gradebooks Content -->
    @if ($gradebooks->isEmpty())
        <div class="bg-white rounded-3xl p-12 border border-slate-200/80 text-center shadow-xs">
            <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Buku Nilai</h3>
            <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                Belum ada buku nilai yang tercatat pada akun Anda. Klik tombol di bawah untuk membuat yang baru.
            </p>
            <a 
                href="{{ route('gradebooks.create') }}" 
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1363DF] hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Buat Buku Nilai Sekarang</span>
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($gradebooks as $gradebook)
                <div class="bg-white rounded-3xl border border-slate-200/90 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-500/5 transition-all p-6 flex flex-col justify-between">
                    <div>
                        <!-- Header Badges -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="px-3 py-1 rounded-xl bg-blue-50 text-[#1363DF] font-bold text-xs border border-blue-100">
                                {{ $gradebook->classroom->name }}
                            </span>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-600 font-semibold text-xs">
                                {{ $gradebook->academicYear->name }} • {{ ucfirst($gradebook->semester) }}
                            </span>
                        </div>

                        <!-- Subject -->
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">
                            {{ $gradebook->subject->code }}
                        </span>
                        <h2 class="text-lg font-extrabold text-slate-900 leading-snug line-clamp-1 mb-2">
                            {{ $gradebook->subject->name }}
                        </h2>

                        <!-- Title -->
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            {{ $gradebook->title ?? 'Buku Nilai Mata Pelajaran' }}
                        </p>
                    </div>

                    <!-- Footer Details & Actions -->
                    <div class="pt-5 mt-6 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a 
                            href="{{ route('gradebooks.show', $gradebook) }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-50 hover:bg-[#1363DF] text-[#1363DF] hover:text-white font-bold text-xs transition-colors"
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
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku nilai ini?')" 
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

        <!-- Pagination Links -->
        <div class="mt-6">
            {{ $gradebooks->links() }}
        </div>
    @endif
</div>
@endsection
