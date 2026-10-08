@extends('layouts.app')

@section('content')
<div class="min-h-screen w-full flex flex-col lg:flex-row">
    <!-- Left Column: Visual & 3D Illustration -->
    <div class="lg:w-1/2 bg-[#1363DF] relative flex flex-col justify-between p-8 sm:p-12 lg:p-16 overflow-hidden min-h-[460px] lg:min-h-screen">
        <!-- Dot Pattern Overlay -->
        <div class="absolute inset-0 bg-dots-pattern opacity-60 pointer-events-none"></div>

        <!-- Subtle Glow Background -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-400/30 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header / App Branding -->
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-white text-xs font-semibold tracking-wide shadow-sm">
                <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                </svg>
                <span>NilaiKu: Buku Nilai Digital Fleksibel Guru</span>
            </div>
        </div>

        <!-- Center: 3D Workshop Illustration -->
        <div class="relative z-10 flex items-center justify-center my-auto py-8">
            <img 
                src="{{ asset('images/login-illustration.png') }}" 
                alt="Ilustrasi NilaiKu" 
                class="w-full max-w-[420px] sm:max-w-[460px] object-contain drop-shadow-[0_25px_35px_rgba(0,0,0,0.25)] transition-transform duration-700 hover:scale-105 select-none pointer-events-none"
            >
        </div>

        <!-- Bottom Text -->
        <div class="relative z-10 max-w-lg">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                Welcome Back!
            </h1>
            <p class="text-blue-100 text-sm sm:text-base mt-2.5 leading-relaxed font-normal">
                Masuk untuk melanjutkan aktivitasmu di NilaiKu: Buku Nilai Digital Fleksibel Guru.
            </p>
        </div>
    </div>

    <!-- Right Column: Login Form -->
    <div class="lg:w-1/2 bg-white flex items-center justify-center p-8 sm:p-12 lg:p-16">
        <div class="w-full max-w-md">
            <!-- Form Title -->
            <div class="mb-8">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Masuk ke Akun
                </h2>
                <p class="text-slate-500 text-sm mt-1.5">
                    Silakan masukkan email dan kata sandi akun pendidik Anda.
                </p>
            </div>

            <!-- Demo Credentials Helper Badge -->
            <div class="mb-6 p-4 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-start justify-between gap-3 shadow-sm">
                <div class="text-xs text-slate-700">
                    <span class="font-bold text-blue-700 uppercase tracking-wider block mb-1">Akun Guru Demo</span>
                    <p class="text-slate-600">Email: <span class="font-mono font-semibold text-slate-800">guru@sekolah.id</span></p>
                    <p class="text-slate-600">Sandi: <span class="font-mono font-semibold text-slate-800">password</span></p>
                </div>
                <button 
                    type="button" 
                    onclick="fillDemoCredentials()"
                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition-all whitespace-nowrap cursor-pointer active:scale-95"
                >
                    Pakai Akun Demo
                </button>
            </div>

            <!-- Validation Error Alert -->
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-700 text-sm flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">Gagal Masuk</p>
                        <ul class="mt-1 list-disc list-inside text-xs space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                        Email
                    </label>
                    <div class="relative rounded-2xl">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-sky-400">
                            <!-- Mail Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus 
                            placeholder="nama@email.com" 
                            class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-sky-100 bg-sky-50/20 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm @error('email') border-rose-300 ring-rose-200 @enderror"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                        Kata Sandi
                    </label>
                    <div class="relative rounded-2xl">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-sky-400">
                            <!-- Lock Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            placeholder="Masukkan kata sandi" 
                            class="w-full pl-12 pr-12 py-3.5 rounded-2xl border border-sky-100 bg-sky-50/20 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm @error('password') border-rose-300 ring-rose-200 @enderror"
                        >
                        <!-- Toggle Password Visibility -->
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility()" 
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                        >
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-sm pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-slate-600">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember" 
                            class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                        >
                        <span>Ingat saya</span>
                    </label>
                    <a href="javascript:void(0)" onclick="alert('Untuk keperluan hackathon, gunakan kata sandi: password')" class="font-medium text-blue-600 hover:text-blue-700 hover:underline">
                        Lupa kata sandi?
                    </a>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full py-3.5 px-6 rounded-2xl bg-[#1363DF] hover:bg-[#0e52b5] text-white font-bold text-base shadow-lg shadow-blue-500/25 hover:shadow-blue-500/35 transition-all transform hover:-translate-y-0.5 active:translate-y-0 text-center cursor-pointer"
                    >
                        Masuk
                    </button>
                </div>
            </form>

            <!-- Back to Home Link -->
            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" class="text-sm text-slate-500 hover:text-slate-800 transition-colors">
                    Kembali ke <span class="font-semibold text-slate-700">Beranda</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function fillDemoCredentials() {
        document.getElementById('email').value = 'guru@sekolah.id';
        document.getElementById('password').value = 'password';
    }

    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.add('hidden');
            eyeOffIcon.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('hidden');
            eyeOffIcon.classList.add('hidden');
        }
    }
</script>
@endsection
