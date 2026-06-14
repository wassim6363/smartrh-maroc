<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\EmployeePortal\ContractController as EmployeeContractController;
use App\Http\Controllers\EmployeePortal\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\EmployeePortal\DocumentController as EmployeeDocumentController;
use App\Http\Controllers\EmployeePortal\PayslipController as EmployeePayslipController;
use App\Http\Controllers\EmployeePortal\SupportTicketController as EmployeeSupportTicketController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PricingController;
use Illuminate\Support\Facades\Route;

Route::get('/up', HealthController::class)->name('health.up');
Route::get('/', LandingController::class)->name('landing');

Route::get('/demo', [DemoRequestController::class, 'create'])->name('demo.create');
Route::post('/demo', [DemoRequestController::class, 'store'])->name('demo.store');
Route::get('/pricing', PricingController::class)->name('pricing');
Route::redirect('/login', '/employee/login')->name('login');

Route::get('/request-demo', [DemoRequestController::class, 'create'])->name('request-demo.create');
Route::post('/request-demo', [DemoRequestController::class, 'store'])->name('request-demo.store');
Route::get('/request-demo/thank-you', [DemoRequestController::class, 'thankYou'])->name('request-demo.thank-you');
Route::get('/terms', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/legal-notice', [LegalPageController::class, 'legalNotice'])->name('legal.legal-notice');

Route::prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/company', [OnboardingController::class, 'company'])->name('company');
    Route::post('/company', [OnboardingController::class, 'storeCompany'])->name('company.store');
    Route::get('/plan', [OnboardingController::class, 'plan'])->name('plan');
    Route::post('/plan', [OnboardingController::class, 'storePlan'])->name('plan.store');
    Route::get('/admin-user', [OnboardingController::class, 'adminUser'])->name('admin-user');
    Route::post('/admin-user', [OnboardingController::class, 'storeAdminUser'])->name('admin-user.store');
    Route::get('/complete', [OnboardingController::class, 'complete'])->name('complete');
});

Route::middleware('auth')->group(function () {
    Route::get('/downloads/documents/{document}', [DocumentDownloadController::class, 'generatedDocument'])->name('documents.download');
    Route::get('/downloads/contracts/{contract}', [DocumentDownloadController::class, 'employeeContract'])->name('contracts.download');
    Route::get('/downloads/payslips/{payslip}', [DocumentDownloadController::class, 'payslip'])->name('payslips.download');
    Route::get('/downloads/invoices/{invoice}', [DocumentDownloadController::class, 'invoice'])->name('invoices.download');

    Route::get('/exports/employees.csv', [ExportController::class, 'employeesCsv'])->name('exports.employees');
    Route::get('/exports/employees.xlsx', [ExportController::class, 'employees'])->name('exports.employees.xlsx');
    Route::get('/exports/payslips.csv', [ExportController::class, 'payslips'])->name('exports.payslips');
    Route::get('/exports/leave-requests.csv', [ExportController::class, 'leaveRequests'])->name('exports.leave-requests');
    Route::get('/exports/payroll-journal.xlsx', [ExportController::class, 'payrollJournalExport'])->name('exports.payroll-journal');
});

Route::get('/employee_import_template.csv', [ExportController::class, 'employeeTemplate'])->name('employees.import-template');

Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('/login', [EmployeePortalController::class, 'login'])->name('login');
    Route::post('/login', [EmployeePortalController::class, 'authenticate'])->name('authenticate');

    Route::middleware('auth')->group(function () {
        Route::get('/', EmployeeDashboardController::class)->name('dashboard');
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard.show');

        Route::get('/payslips', [EmployeePayslipController::class, 'index'])->name('payslips');
        Route::get('/payslips/{payslip}', [EmployeePayslipController::class, 'show'])->name('payslips.show');
        Route::get('/payslips/{payslip}/download', [EmployeePayslipController::class, 'download'])->name('payslips.download');

        Route::get('/contracts', [EmployeeContractController::class, 'index'])->name('contracts');
        Route::get('/contracts/{contract}', [EmployeeContractController::class, 'show'])->name('contracts.show');
        Route::get('/contracts/{contract}/download', [EmployeeContractController::class, 'download'])->name('contracts.download');

        Route::get('/documents', [EmployeeDocumentController::class, 'index'])->name('documents');
        Route::get('/documents/{document}', [EmployeeDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
        Route::post('/document-requests', [EmployeeDocumentController::class, 'storeRequest'])->name('documents.requests.store');
        Route::get('/document-requests/{documentRequest}', [EmployeeDocumentController::class, 'showRequest'])->name('documents.requests.show');

        Route::get('/leave-requests', [EmployeePortalController::class, 'leaveRequests'])->name('leave-requests');
        Route::post('/leave-requests', [EmployeePortalController::class, 'storeLeaveRequest'])->name('leave-requests.store');
        Route::get('/support', [EmployeeSupportTicketController::class, 'index'])->name('support');
        Route::get('/support/create', [EmployeeSupportTicketController::class, 'create'])->name('support.create');
        Route::post('/support', [EmployeeSupportTicketController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [EmployeeSupportTicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [EmployeeSupportTicketController::class, 'reply'])->name('support.reply');
        Route::post('/logout', [EmployeePortalController::class, 'logout'])->name('logout');
    });
});
