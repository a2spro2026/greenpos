<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RegistrationRequestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    // Leave impersonation even when acting as tenant user
    Route::post('/leave-impersonation', [ImpersonationController::class, 'leave'])
        ->middleware('auth')
        ->name('leave-impersonation');

    Route::middleware('platform.admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard.home');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/registrations', [RegistrationRequestController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/{registration}', [RegistrationRequestController::class, 'show'])->name('registrations.show');
        Route::post('/registrations/{registration}/approve', [RegistrationRequestController::class, 'approve'])->name('registrations.approve');
        Route::post('/registrations/{registration}/reject', [RegistrationRequestController::class, 'reject'])->name('registrations.reject');
        Route::post('/registrations/{registration}/suspend', [RegistrationRequestController::class, 'suspend'])->name('registrations.suspend');

        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::post('/companies/{company}/suspend', [CompanyController::class, 'suspend'])->name('companies.suspend');
        Route::post('/companies/{company}/reactivate', [CompanyController::class, 'reactivate'])->name('companies.reactivate');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('/companies/{company}/impersonate', [CompanyController::class, 'impersonate'])->name('companies.impersonate');

        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::put('/modules/plans/{plan}', [ModuleController::class, 'updatePlan'])->name('modules.plan.update');
        Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
