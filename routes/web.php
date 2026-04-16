<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ParentRegisterController;
use App\Http\Controllers\ParentPanel;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', fn () => redirect()->route('login'));

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/parent/register', [ParentRegisterController::class, 'create'])->name('parent.register');
    Route::post('/parent/register', [ParentRegisterController::class, 'store'])->name('parent.register.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')
    ->middleware('auth');

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Exams
        Route::resource('exams', Admin\ExamController::class)->except(['show']);
        Route::post('exams/{exam}/toggle-publish', [Admin\ExamController::class, 'togglePublish'])
            ->name('exams.toggle-publish');

        // Questions
        Route::get('exams/{exam}/questions', [Admin\QuestionController::class, 'index'])->name('exams.questions');
        Route::post('exams/{exam}/questions', [Admin\QuestionController::class, 'store'])->name('exams.questions.store');
        Route::put('questions/{question}', [Admin\QuestionController::class, 'update'])->name('questions.update');
        Route::delete('questions/{question}', [Admin\QuestionController::class, 'destroy'])->name('questions.destroy');

        // Bulk exam import (Excel)
        Route::get('exams/bulk-import', [Admin\ExamBulkImportController::class, 'index'])->name('exams.bulk-import');
        Route::get('exams/bulk-import/template', [Admin\ExamBulkImportController::class, 'template'])->name('exams.bulk-import.template');
        Route::post('exams/bulk-import', [Admin\ExamBulkImportController::class, 'store'])->name('exams.bulk-import.store');
        Route::get('exams/bulk-import/{batch}/errors', [Admin\ExamBulkImportController::class, 'errors'])->name('exams.bulk-import.errors');
        Route::get('exams/bulk-import/{batch}', [Admin\ExamBulkImportController::class, 'show'])->name('exams.bulk-import.show');

        // Students
        Route::get('students', [Admin\StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [Admin\StudentController::class, 'create'])->name('students.create');
        Route::post('students', [Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('students/{user}', [Admin\StudentController::class, 'show'])->name('students.show');
        Route::get('students/{user}/edit', [Admin\StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{user}', [Admin\StudentController::class, 'update'])->name('students.update');
        Route::post('students/{user}/reset-pin', [Admin\StudentController::class, 'resetPin'])->name('students.reset-pin');
        Route::post('students/{user}/toggle-active', [Admin\StudentController::class, 'toggleActive'])->name('students.toggle-active');
        Route::delete('students/{user}', [Admin\StudentController::class, 'destroy'])->name('students.destroy');

        // Parents
        Route::get('parents', [Admin\ParentController::class, 'index'])->name('parents.index');
        Route::get('parents/create', [Admin\ParentController::class, 'create'])->name('parents.create');
        Route::post('parents', [Admin\ParentController::class, 'store'])->name('parents.store');
        Route::get('parents/{user}', [Admin\ParentController::class, 'show'])->name('parents.show');
        Route::get('parents/{user}/edit', [Admin\ParentController::class, 'edit'])->name('parents.edit');
        Route::put('parents/{user}', [Admin\ParentController::class, 'update'])->name('parents.update');
        Route::post('parents/{user}/toggle-active', [Admin\ParentController::class, 'toggleActive'])->name('parents.toggle-active');
        Route::post('parents/{user}/approve', [Admin\ParentController::class, 'approve'])->name('parents.approve');
        Route::post('parents/{user}/reject', [Admin\ParentController::class, 'reject'])->name('parents.reject');
        Route::delete('parents/{user}', [Admin\ParentController::class, 'destroy'])->name('parents.destroy');
        Route::post('parents/{user}/add-student', [Admin\ParentController::class, 'addStudent'])->name('parents.add-student');
        Route::delete('parents/{user}/remove-student/{profile}', [Admin\ParentController::class, 'removeStudent'])->name('parents.remove-student');

        // Profile
        Route::get('profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

        // Results
        Route::get('results', [Admin\ResultController::class, 'index'])->name('results.index');
    });

// ─── Parent ───────────────────────────────────────────────────────────────────
Route::prefix('parent')
    ->middleware(['auth', 'role:parent'])
    ->name('parent.')
    ->group(function () {
        Route::get('dashboard', [ParentPanel\DashboardController::class, 'index'])->name('dashboard');

        Route::get('students', [ParentPanel\StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [ParentPanel\StudentController::class, 'create'])->name('students.create');
        Route::post('students', [ParentPanel\StudentController::class, 'store'])->name('students.store');
        Route::get('students/{profile}/results', [ParentPanel\StudentController::class, 'results'])->name('students.results');
        Route::post('students/{profile}/reset-pin', [ParentPanel\StudentController::class, 'resetPin'])->name('students.reset-pin');

        // Profile
        Route::get('profile', [ParentPanel\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ParentPanel\ProfileController::class, 'update'])->name('profile.update');
    });

// ─── Student ──────────────────────────────────────────────────────────────────
Route::prefix('student')
    ->middleware(['auth', 'role:student'])
    ->name('student.')
    ->group(function () {
        Route::get('dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');

        Route::get('exams', [Student\ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/{exam}/instructions', [Student\ExamController::class, 'instructions'])->name('exams.instructions');
        Route::post('exams/{exam}/begin', [Student\ExamController::class, 'begin'])->name('exams.begin');
        Route::get('exams/{exam}/take', [Student\ExamController::class, 'take'])->name('exams.take');
        Route::post('exams/{exam}/submit', [Student\ExamController::class, 'submit'])->name('exams.submit');
        Route::get('exams/{exam}/result', [Student\ExamController::class, 'result'])->name('exams.result');
        Route::post('exams/{exam}/save-progress', [Student\ExamController::class, 'saveProgress'])->name('exams.save-progress');

        Route::get('results', [Student\ResultController::class, 'index'])->name('results.index');
    });
