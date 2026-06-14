<?php

use App\Http\Controllers\PayrollCalculationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('/payroll/calculate-preview', [PayrollCalculationController::class, 'calculatePreview']);
    Route::post('/payroll/generate-payslip', [PayrollCalculationController::class, 'generatePayslip']);
});
