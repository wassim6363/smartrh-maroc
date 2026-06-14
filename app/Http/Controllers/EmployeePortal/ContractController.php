<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Models\EmployeeContract;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\ContractPdfService;
use Illuminate\Support\Facades\Storage;

class ContractController extends BaseEmployeePortalController
{
    public function index()
    {
        $employee = $this->employee();

        return view('employee.contracts.index', [
            'employee' => $employee,
            'contracts' => $employee->employeeContracts()->latest()->paginate(12),
        ]);
    }

    public function show(EmployeeContract $contract)
    {
        $this->abortUnlessOwn($contract->employee_id);

        return view('employee.contracts.show', [
            'employee' => $this->employee(),
            'contract' => $contract->load(['company', 'employee', 'contractTemplate']),
        ]);
    }

    public function download(EmployeeContract $contract, ContractPdfService $generator, AuditLogger $audit)
    {
        $this->abortUnlessOwn($contract->employee_id);

        $path = $contract->signed_pdf_path ?: ($contract->pdf_path ?: $generator->generate($contract));
        abort_unless(Storage::disk(config('filesystems.private_disk'))->exists($path), 404);

        $audit->log('contract_pdf_downloaded_by_employee', $contract, [], [], [
            'employee_id' => $contract->employee_id,
            'reference' => $contract->reference,
            'signed' => (bool) $contract->signed_pdf_path,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download(
            $path,
            'contrat-' . $contract->reference . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
