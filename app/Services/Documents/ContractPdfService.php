<?php

namespace App\Services\Documents;

use App\Models\EmployeeContract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ContractPdfService
{
    public function generate(EmployeeContract $contract): string
    {
        $contract->loadMissing(['company', 'employee', 'contractTemplate']);

        $path = sprintf(
            'companies/%s/employees/%s/contracts/%s.pdf',
            $contract->company_id,
            $contract->employee_id,
            $contract->reference,
        );

        $pdf = Pdf::loadView('pdf.contract', ['contract' => $contract])->setPaper('a4');
        Storage::disk(config('filesystems.private_disk'))->put($path, $pdf->output());

        $contract->forceFill([
            'pdf_path' => $path,
            'generated_at' => $contract->generated_at ?: now(),
            'status' => $contract->status === 'draft' ? 'generated' : $contract->status,
        ])->save();

        return $path;
    }
}
