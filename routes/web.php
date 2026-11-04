<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\AcademicController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\SettingController;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth', 'active_user'])->group(function () {
    // Password change
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/change-password', [AuthController::class, 'updatePassword'])->name('password.update');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Parent Portal Routes
    Route::middleware('role:parent,super_admin')->prefix('parent')->name('parent.')->group(function () {
        Route::get('/dashboard', [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/statement/{learnerId}', [ParentPortalController::class, 'childStatement'])->name('statement');
        Route::get('/statement/{learnerId}', [ParentPortalController::class, 'childStatement'])->name('child.statement');
        Route::get('/reports/{learnerId}', [ParentPortalController::class, 'childReports'])->name('reports');
        Route::get('/reports/{learnerId}', [ParentPortalController::class, 'childReports'])->name('child.reports');
    });

    // Learner Management Routes
    Route::prefix('learners')->name('learners.')->group(function () {
        Route::get('/', [LearnerController::class, 'index'])->name('index');
        Route::get('/export', [LearnerController::class, 'exportCsv'])->name('export');
        
        Route::middleware('role:super_admin,registrar')->group(function () {
            Route::get('/create', [LearnerController::class, 'create'])->name('create');
            Route::post('/', [LearnerController::class, 'store'])->name('store');
            Route::get('/import', [LearnerController::class, 'importForm'])->name('import');
            Route::post('/import', [LearnerController::class, 'processImport'])->name('import.post');
            Route::get('/{id}/edit', [LearnerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [LearnerController::class, 'update'])->name('update');
            Route::delete('/{id}', [LearnerController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{id}', [LearnerController::class, 'show'])->name('show');
        Route::post('/calculate-fees', [LearnerController::class, 'calculateFeesApi'])->name('calculate-fees');
    });

    // Academic Structure Routes
    Route::prefix('academic')->name('academic.')->group(function () {
        Route::get('/classes', [AcademicController::class, 'classesIndex'])->name('classes');
        Route::get('/classes/{id}', [AcademicController::class, 'classShow'])->name('class.show');
        Route::post('/classes/{id}/class-teacher', [AcademicController::class, 'assignClassTeacher'])->name('class.assign-teacher');
        
        Route::middleware('role:super_admin,headteacher')->group(function () {
            Route::get('/years', [AcademicController::class, 'yearsIndex'])->name('years');
            Route::post('/years/{id}/current', [AcademicController::class, 'setCurrentYear'])->name('year.set-current');
            Route::post('/terms/{id}/current', [AcademicController::class, 'setCurrentTerm'])->name('term.set-current');
            Route::get('/promotion', [AcademicController::class, 'promotionIndex'])->name('promotion');
            Route::post('/promotion/preview', [AcademicController::class, 'promotionPreview'])->name('promotion.preview');
            Route::post('/promotion/execute', [AcademicController::class, 'executePromotion'])->name('promotion.execute');
        });

        Route::get('/learning-areas', [AcademicController::class, 'learningAreasIndex'])->name('learning-areas');
        Route::post('/learning-areas/assign', [AcademicController::class, 'assignSubjectTeacher'])->name('learning-areas.assign');
    });

    // Fee & Invoice Management
    Route::prefix('fees')->name('fees.')->group(function () {
        Route::get('/invoices', [FeeController::class, 'invoicesIndex'])->name('invoices');
        Route::get('/invoices/{id}', [FeeController::class, 'invoiceShow'])->name('invoices.show');
        Route::get('/statement/{learnerId}', [FeeController::class, 'statement'])->name('statement');

        Route::middleware('role:super_admin,bursar')->group(function () {
            Route::get('/structures', [FeeController::class, 'structuresIndex'])->name('structures');
            Route::post('/structures', [FeeController::class, 'storeStructure'])->name('structures.store');
            Route::post('/invoices/{id}/discount', [FeeController::class, 'applyDiscount'])->name('invoices.discount');
        });
    });

    // Payments & Accounting
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/receipt/{id}', [PaymentController::class, 'receipt'])->name('receipt');
        Route::get('/daily-report', [PaymentController::class, 'dailyReport'])->name('daily-report');
        Route::get('/arrears-report', [PaymentController::class, 'arrearsReport'])->name('arrears-report');
        Route::post('/mpesa/stk', [PaymentController::class, 'triggerMpesaStk'])->name('mpesa.stk');

        Route::middleware('role:super_admin,bursar')->group(function () {
            Route::get('/create', [PaymentController::class, 'create'])->name('create');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
            Route::post('/{id}/reverse', [PaymentController::class, 'reverse'])->name('reverse');
        });
    });

    // CBE Assessments
    Route::prefix('assessments')->name('assessments.')->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index');
        Route::get('/support-report', [AssessmentController::class, 'supportReport'])->name('support-report');
        Route::middleware('role:super_admin,headteacher,teacher')->group(function () {
            Route::post('/', [AssessmentController::class, 'store'])->name('store');
        });
    });

    // Report Cards
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportCardController::class, 'index'])->name('index');
        Route::get('/view/{id}', [ReportCardController::class, 'show'])->name('show');
        Route::get('/download/{learnerId}', [ReportCardController::class, 'downloadPdf'])->name('download');
        Route::get('/pdf/{learnerId}', [ReportCardController::class, 'downloadPdf'])->name('pdf');
        Route::middleware('role:super_admin,headteacher')->group(function () {
            Route::post('/bulk-generate', [ReportCardController::class, 'bulkGenerate'])->name('bulk-generate');
        });
    });

    // Daily Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::middleware('role:super_admin,headteacher,teacher')->group(function () {
            Route::post('/', [AttendanceController::class, 'store'])->name('store');
        });
    });

    // School Settings & User Management
    Route::prefix('settings')->name('settings.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::get('/users', [SettingController::class, 'usersIndex'])->name('users');
        Route::post('/users', [SettingController::class, 'storeUser'])->name('users.store');
        Route::post('/users/{id}/toggle', [SettingController::class, 'toggleUserStatus'])->name('users.toggle');
        Route::get('/audit-logs', [SettingController::class, 'auditLogsIndex'])->name('audit-logs');
    });
});
