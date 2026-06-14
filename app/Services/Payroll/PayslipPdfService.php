<?php

namespace App\Services\Payroll;

use App\Models\GeneratedDocument;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PayslipPdfService
{
    public function generate(Payslip $payslip): string
    {
        $payslip->loadMissing(['company', 'employee.department', 'employee.position', 'payrollPeriod', 'lines']);

        $path = sprintf(
            'companies/%s/employees/%s/payslips/%s.pdf',
            $payslip->company_id,
            $payslip->employee_id,
            $payslip->reference,
        );

        $pdf = Pdf::loadView('pdf.payslip', ['payslip' => $payslip])->setPaper('a4');
        Storage::disk(config('filesystems.private_disk'))->put($path, $pdf->output());

        $payslip->forceFill(['pdf_path' => $path])->save();

        GeneratedDocument::query()->updateOrCreate(
            [
                'company_id' => $payslip->company_id,
                'payslip_id' => $payslip->id,
                'type' => 'payslip',
            ],
            [
                'employee_id' => $payslip->employee_id,
                'title' => 'Bulletin de paie ' . $payslip->reference,
                'file_path' => $path,
                'metadata' => ['disk' => config('filesystems.private_disk'), 'generated_from' => Payslip::class],
            ],
        );

        return $path;
    }
}
