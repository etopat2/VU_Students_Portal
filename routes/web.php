<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\LecturersController;
use App\Http\Controllers\StudentsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [AccountController::class, 'show'])->name('profile');
    Route::get('/settings', [AccountController::class, 'edit'])->name('settings');
    Route::put('/settings', [AccountController::class, 'update'])->name('settings.update');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');

    Route::middleware('role:student')->group(function () {
        Route::get('/my-courses', [DashboardController::class, 'courses'])->name('student.courses');
        Route::get('/my-grades', [DashboardController::class, 'grades'])->name('student.grades');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('students', StudentsController::class)->except('show');
        Route::resource('lecturers', LecturersController::class)->except('show');
        Route::resource('courses', CoursesController::class)->except('show');
        Route::resource('grades', GradesController::class)
            ->except('show')
            ->parameters(['grades' => 'grade']);
    });
});
