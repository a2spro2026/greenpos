<?php

use App\Http\Controllers\SuperAdmin\BillingController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\DomainController;
use App\Http\Controllers\SuperAdmin\GlobalUserController;
use App\Http\Controllers\SuperAdmin\JournalController;
use App\Http\Controllers\SuperAdmin\LicenseController;
use App\Http\Controllers\SuperAdmin\ModuleManagerController;
use App\Http\Controllers\SuperAdmin\MonitoringController;
use App\Http\Controllers\SuperAdmin\PaymentController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SaasInvoiceController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')->name('superadmin.')->middleware('superadmin')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/reactivate', [TenantController::class, 'reactivate'])->name('tenants.reactivate');
    Route::post('/tenants/{tenant}/archive', [TenantController::class, 'archive'])->name('tenants.archive');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

    Route::get('/billing', [BillingController::class, 'dashboard'])->name('billing.dashboard');
    Route::get('/billing/gateways', [BillingController::class, 'gateways'])->name('billing.gateways');
    Route::put('/billing/gateways/{gateway}', [BillingController::class, 'updateGateway'])->name('billing.gateways.update');
    Route::post('/billing/run-job', [BillingController::class, 'runBillingJob'])->name('billing.run-job');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');

    Route::get('/modules', [ModuleManagerController::class, 'index'])->name('modules.index');
    Route::put('/modules/plans/{plan}', [ModuleManagerController::class, 'updatePlan'])->name('modules.plan.update');

    Route::get('/subscriptions/dashboard', [SubscriptionController::class, 'dashboard'])->name('subscriptions.dashboard');
    Route::get('/subscriptions/alerts', [SubscriptionController::class, 'alerts'])->name('subscriptions.alerts');
    Route::post('/subscriptions/alerts/{alert}/read', [SubscriptionController::class, 'markAlertRead'])->name('subscriptions.alerts.read');
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('/subscriptions/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
    Route::put('/subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::get('/subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlanForm'])->name('subscriptions.change-plan');
    Route::post('/subscriptions/{subscription}/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
    Route::post('/subscriptions/{subscription}/downgrade', [SubscriptionController::class, 'downgrade'])->name('subscriptions.downgrade');
    Route::post('/subscriptions/{subscription}/convert-trial', [SubscriptionController::class, 'convertTrial'])->name('subscriptions.convert-trial');
    Route::post('/subscriptions/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
    Route::post('/subscriptions/{subscription}/reactivate', [SubscriptionController::class, 'reactivate'])->name('subscriptions.reactivate');
    Route::post('/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/{subscription}/past-due', [SubscriptionController::class, 'markPastDue'])->name('subscriptions.past-due');
    Route::post('/subscriptions/{subscription}/issue-invoice', [SubscriptionController::class, 'issueInvoice'])->name('subscriptions.issue-invoice');

    Route::get('/invoices', [SaasInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [SaasInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [SaasInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [SaasInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [SaasInvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [SaasInvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/download', [SaasInvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/pay', [SaasInvoiceController::class, 'pay'])->name('invoices.pay');
    Route::post('/invoices/{invoice}/void', [SaasInvoiceController::class, 'void'])->name('invoices.void');

    Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::post('/licenses/{license}/revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::post('/licenses/{license}/renew', [LicenseController::class, 'renew'])->name('licenses.renew');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
    Route::post('/domains/{domain}/verify', [DomainController::class, 'verify'])->name('domains.verify');
    Route::delete('/domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');

    Route::get('/users', [GlobalUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/toggle-admin', [GlobalUserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('/monitoring/refresh', [MonitoringController::class, 'refresh'])->name('monitoring.refresh');

    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
});
