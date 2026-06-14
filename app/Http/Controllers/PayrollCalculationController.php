<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\CalculatePayrollPreviewRequest;
use App\Http\Requests\Payroll\GeneratePayslipRequest;
use App\Services\Payroll\PayslipGenerationService;

class PayrollCalculationController extends Controller
{
    public function calculatePreview(CalculatePayrollPreviewRequest $request, PayslipGenerationService $service)
    {
        return response()->json($service->preview($request->validated()));
    }

    public function generatePayslip(
        GeneratePayslipRequest $request,
        PayslipGenerationService $service,
    ) {
        $payslip = $service->generate($request->validated());
        $result = $payslip->calculation_snapshot['result'] ?? [];

        return response()->json([
            'payslip_id' => $payslip->id,
            'pdf_path' => $payslip->pdf_path,
            'calculation' => $result,
        ], 201);
    }
}
