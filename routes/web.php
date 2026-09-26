<?php

use App\Http\Controllers\AssessmentColumnController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\RubricController;
use App\Http\Controllers\RubricCriterionController;
use App\Http\Controllers\RubricLevelController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\StudentRubricScoreController;
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
    Route::get('/gradebooks/{gradebook}/export', [GradebookController::class, 'export'])->name('gradebooks.export');
    Route::resource('gradebooks', GradebookController::class);
    Route::resource('gradebooks.columns', AssessmentColumnController::class)->except(['index', 'show']);
    Route::post('/gradebooks/{gradebook}/scores', [ScoreController::class, 'store'])->name('gradebooks.scores.store');
    Route::post('/gradebooks/{gradebook}/scores/batch', [ScoreController::class, 'batchStore'])->name('gradebooks.scores.batch');

    // Rubric Backend API / Web Routes
    Route::get('/gradebooks/{gradebook}/columns/{column}/rubric', [RubricController::class, 'show'])->name('gradebooks.columns.rubric.show');
    Route::post('/gradebooks/{gradebook}/columns/{column}/rubric', [RubricController::class, 'store'])->name('gradebooks.columns.rubric.store');
    Route::put('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}', [RubricController::class, 'update'])->name('gradebooks.columns.rubric.update');
    Route::delete('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}', [RubricController::class, 'destroy'])->name('gradebooks.columns.rubric.destroy');

    Route::post('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}/criteria', [RubricCriterionController::class, 'store'])->name('gradebooks.columns.rubric.criteria.store');
    Route::put('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}/criteria/{criterion}', [RubricCriterionController::class, 'update'])->name('gradebooks.columns.rubric.criteria.update');
    Route::delete('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}/criteria/{criterion}', [RubricCriterionController::class, 'destroy'])->name('gradebooks.columns.rubric.criteria.destroy');

    Route::put('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}/criteria/{criterion}/levels/{level}', [RubricLevelController::class, 'update'])->name('gradebooks.columns.rubric.criteria.levels.update');

    Route::post('/gradebooks/{gradebook}/columns/{column}/rubric/{rubric}/scores', [StudentRubricScoreController::class, 'store'])->name('gradebooks.columns.rubric.scores.store');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
