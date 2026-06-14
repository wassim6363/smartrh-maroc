<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Models\Payslip;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\PayslipPdfGenerator;
use Illuminate\Support\Facades\Storage;

class PayslipController extends BaseEmployeePortalController
{
    public function index()
    {
        $employee = $this->employee();

        return view('employee.payslips.index', [
            'employee' => $employee,
            'payslips' => $employee->payslips()->with('payrollPeriod')->latest()->paginate(12),
        ]);
    }

    public function show(Payslip $payslip)
    {
        $this->abortUnlessOwn($payslip->employee_id);

        return view('employee.payslips.show', [
            'employee' => $this->employee(),
            'payslip' => $payslip->load(['company', 'employee', 'payrollPeriod', 'lines']),
        ]);
    }

    public function download(Payslip $payslip, PayslipPdfGenerator $generator, AuditLogger $audit)
    {
        $this->abortUnlessOwn($payslip->employee_id);

        $document = $payslip->generatedDocuments()->where('type', 'payslip')->latest()->first()
            ?: $generator->generate($payslip);

        abort_unless(Storage::disk(config('filesystems.private_disk'))->exists($document->file_path), 404);

        $audit->log('payslip_pdf_downloaded_by_employee', $payslip, [], [], [
            'employee_id' => $payslip->employee_id,
            'reference' => $payslip->reference,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download(
            $document->file_path,
            'bulletin-' . $payslip->reference . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
