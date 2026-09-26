<?php

use App\Http\Controllers\AssessmentColumnController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('gradebooks', GradebookController::class);
    Route::resource('gradebooks.columns', AssessmentColumnController::class)->except(['index', 'show']);
    Route::post('/gradebooks/{gradebook}/scores', [ScoreController::class, 'store'])->name('gradebooks.scores.store');
    Route::post('/gradebooks/{gradebook}/scores/batch', [ScoreController::class, 'batchStore'])->name('gradebooks.scores.batch');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
