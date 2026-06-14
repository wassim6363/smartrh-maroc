<?php

namespace App\Services\Documents;

use App\Models\EmployeeContract;
use App\Services\Audit\AuditLogger;

class ContractSignatureService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function markSigned(EmployeeContract $contract, string $signedPdfPath): EmployeeContract
    {
        $contract->update([
            'signed_pdf_path' => $signedPdfPath,
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        $this->audit->log('signed_contract_uploaded', $contract, [], [], [
            'employee_id' => $contract->employee_id,
            'reference' => $contract->reference,
        ]);

        return $contract->refresh();
    }
}
