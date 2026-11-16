<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentResultController;
use App\Http\Controllers\AssessmentSubmissionController;
use App\Http\Controllers\AcademicStructureController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CashManagementController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\LecturersController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SemesterRegistrationController;
use App\Http\Controllers\StudentAcademicRecordController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\StudySessionChangeRequestController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'password.change'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
    Route::get('/profile', [AccountController::class, 'show'])->name('profile');
    Route::get('/settings', [AccountController::class, 'edit'])->name('settings');
    Route::put('/settings', [AccountController::class, 'update'])->name('settings.update');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');

    Route::middleware('role:student')->group(function () {
        Route::get('/my-study-session', [StudySessionChangeRequestController::class, 'studentIndex'])
            ->name('student.study-session');
        Route::post('/my-study-session/requests', [StudySessionChangeRequestController::class, 'store'])
            ->name('student.study-session.requests.store');
        Route::post('/my-payments', [StudentPaymentController::class, 'checkout'])->name('student.payments.checkout');
        Route::get('/my-payments/{reference}/status', [StudentPaymentController::class, 'status'])
            ->where('reference', '[A-Z0-9-]+')
            ->name('student.payments.status');
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/my-courses', [DashboardController::class, 'courses'])->name('student.courses');
        Route::get('/my-grades', [DashboardController::class, 'grades'])->name('student.grades');
        Route::get('/my-assessments', [AssessmentSubmissionController::class, 'index'])
            ->name('student-assessments.index');
        Route::get('/my-assessments/{assessment}', [AssessmentSubmissionController::class, 'show'])
            ->name('student-assessments.show');
        Route::get('/my-assessments/{assessment}/question', [AssessmentSubmissionController::class, 'question'])
            ->name('student-assessments.question');
        Route::get('/my-assessments/{assessment}/answer', [AssessmentSubmissionController::class, 'ownAnswer'])
            ->name('student-assessments.answer');
        Route::post('/my-assessments/{assessment}/submissions', [AssessmentSubmissionController::class, 'store'])
            ->name('student-assessments.submissions.store');
    });

    Route::middleware('role:registrar,student')->group(function () {
        Route::post('/students/{student}/semester-registrations', [SemesterRegistrationController::class, 'store'])
            ->name('semester-registrations.store');
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/students/{student}/sessions/{session}/exam-permit', [
            StudentAcademicRecordController::class,
            'examPermit',
        ])->name('students.exam-permit');
        Route::get('/students/{student}/sessions/{session}/results', [
            StudentAcademicRecordController::class,
            'results',
        ])->name('students.results');
    });

    Route::middleware('role:accountant')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    });

    Route::middleware('role:lecturer,registrar')->group(function () {
        Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::post('/modules/{module}/sessions/{session}/assessments', [
            AssessmentController::class,
            'store',
        ])->name('assessments.store');
        Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])
            ->name('assessments.update');
        Route::get('/assessments/{assessment}/results', [AssessmentResultController::class, 'index'])
            ->name('assessments.results.index');
        Route::post('/assessments/{assessment}/results', [AssessmentResultController::class, 'store'])
            ->name('assessment-results.store');
        Route::get('/assessments/{assessment}/submissions', [AssessmentSubmissionController::class, 'report'])
            ->name('assessments.submissions.index');
        Route::get('/assessments/{assessment}/submissions/{submission}/download', [
            AssessmentSubmissionController::class,
            'downloadAnswer',
        ])->name('assessments.submissions.download');
    });

    Route::middleware('role:registrar')->group(function () {
        Route::resource('students', StudentsController::class)->except('show');
        Route::resource('lecturers', LecturersController::class)->except('show');
        Route::resource('courses', CoursesController::class)->except('show');
        Route::resource('grades', GradesController::class)
            ->except('show')
            ->parameters(['grades' => 'grade']);
    });

    Route::middleware('role:admin,registrar')->group(function () {
        Route::resource('study-sessions', StudySessionController::class)->except('show');
        Route::get('/study-session-requests', [StudySessionChangeRequestController::class, 'index'])
            ->name('study-session-requests.index');
        Route::put('/study-session-requests/{changeRequest}', [StudySessionChangeRequestController::class, 'review'])
            ->name('study-session-requests.review');

        Route::get('/academic-structure/{type}', [AcademicStructureController::class, 'index'])
            ->name('academic-structure.index');
        Route::get('/academic-structure/{type}/create', [AcademicStructureController::class, 'create'])
            ->name('academic-structure.create');
        Route::post('/academic-structure/{type}', [AcademicStructureController::class, 'store'])
            ->name('academic-structure.store');
        Route::get('/academic-structure/{type}/{record}/edit', [AcademicStructureController::class, 'edit'])
            ->name('academic-structure.edit');
        Route::put('/academic-structure/{type}/{record}', [AcademicStructureController::class, 'update'])
            ->name('academic-structure.update');
        Route::delete('/academic-structure/{type}/{record}', [AcademicStructureController::class, 'destroy'])
            ->name('academic-structure.destroy');

    });

    Route::middleware('role:accountant,registrar')->group(function () {
        Route::get('/financial-statements', [FinancialStatementController::class, 'index'])
            ->name('financial-statements.index');
        Route::get('/financial-statements/{student}', [FinancialStatementController::class, 'show'])
            ->name('financial-statements.show');
    });

    Route::get('/my-financial-statement', [FinancialStatementController::class, 'myStatement'])
        ->middleware('role:student')
        ->name('financial-statements.mine');

    Route::middleware('role:accountant')->group(function () {
        Route::get('/cash-management', [CashManagementController::class, 'index'])->name('cash-management.index');
        Route::post('/cash-management/registers', [CashManagementController::class, 'open'])
            ->name('cash-management.open');
        Route::post('/cash-management/registers/{cashRegister}/payments', [CashManagementController::class, 'payment'])
            ->name('cash-management.payments.store');
        Route::post('/cash-management/registers/{cashRegister}/expenses', [CashManagementController::class, 'expense'])
            ->name('cash-management.expenses.store');
        Route::put('/cash-management/registers/{cashRegister}/close', [CashManagementController::class, 'close'])
            ->name('cash-management.close');
        Route::get('/cash-management/receipts/{cashTransaction}', [CashManagementController::class, 'receipt'])
            ->name('cash-management.receipt');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/study-sessions/assignments', [StudySessionController::class, 'assignments'])
            ->name('study-sessions.assignments');
        Route::put('/study-sessions/assignments/{student}', [StudySessionController::class, 'assign'])
            ->name('study-sessions.assign');
        Route::get('/system-settings', [SystemSettingsController::class, 'edit'])->name('system-settings.edit');
        Route::put('/system-settings', [SystemSettingsController::class, 'update'])->name('system-settings.update');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}/role', [UserManagementController::class, 'updateRole'])
            ->name('users.update-role');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

Route::get('/payments/flutterwave/callback', [StudentPaymentController::class, 'callback'])
    ->name('payments.flutterwave.callback');
Route::post('/webhooks/flutterwave', [StudentPaymentController::class, 'webhook'])
    ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])
    ->name('payments.flutterwave.webhook');
