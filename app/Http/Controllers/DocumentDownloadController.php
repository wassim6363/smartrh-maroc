<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Invoice;
use App\Models\Payslip;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\ContractPdfService;
use App\Services\Documents\PayslipPdfGenerator;
use App\Services\Saas\InvoicePdfService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentDownloadController extends Controller
{
    public function generatedDocument(GeneratedDocument $document, AuditLogger $audit)
    {
        abort_unless($this->canAccessEmployee($document->employee_id), 403);
        $path = $document->pdf_path ?: $document->file_path;
        abort_unless($path && Storage::disk(config('filesystems.private_disk'))->exists($path), 404);

        $audit->log('generated_document_pdf_downloaded', $document, [], [], [
            'employee_id' => $document->employee_id,
            'reference' => $document->reference,
            'type' => $document->type,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download($path, str($document->title)->slug() . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function employeeContract(EmployeeContract $contract, ContractPdfService $generator, AuditLogger $audit)
    {
        abort_unless($this->canAccessEmployee($contract->employee_id), 403);

        $path = $contract->pdf_path ?: $generator->generate($contract);
        abort_unless(Storage::disk(config('filesystems.private_disk'))->exists($path), 404);

        $audit->log('contract_pdf_downloaded', $contract, [], [], [
            'employee_id' => $contract->employee_id,
            'reference' => $contract->reference,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download($path, 'contrat-' . $contract->reference . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function payslip(Payslip $payslip, PayslipPdfGenerator $generator, AuditLogger $audit)
    {
        abort_unless($this->canAccessEmployee($payslip->employee_id), 403);

        $document = $payslip->generatedDocuments()->where('type', 'payslip')->latest()->first()
            ?: $generator->generate($payslip);

        abort_unless(Storage::disk(config('filesystems.private_disk'))->exists($document->file_path), 404);

        $audit->log('payslip_pdf_downloaded_by_admin', $payslip, [], [], [
            'employee_id' => $payslip->employee_id,
            'reference' => $payslip->reference,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download($document->file_path, 'bulletin-' . $payslip->reference . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function invoice(Invoice $invoice, InvoicePdfService $generator, AuditLogger $audit)
    {
        abort_unless($this->canAccessCompany($invoice->company_id), 403);

        $path = $invoice->pdf_path;
        if (! $path || ! Storage::disk(config('filesystems.private_disk'))->exists($path)) {
            $path = $generator->generate($invoice);
        }

        abort_unless(Storage::disk(config('filesystems.private_disk'))->exists($path), 404);

        $audit->log('invoice_pdf_downloaded', $invoice, [], [], [
            'invoice_number' => $invoice->invoice_number,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download($path, $generator->filename($invoice) . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function canAccessEmployee(?int $employeeId): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Company Owner', 'RH Manager', 'Payroll Manager', 'Accountant'])) {
            $employee = Employee::query()->find($employeeId);

            return $employee && $user->canAccessCompany($employee->company_id);
        }

        return $employeeId && $user->employees()->whereKey($employeeId)->exists();
    }

    private function canAccessCompany(?int $companyId): bool
    {
        $user = Auth::user();

        return $user && $user->canAccessCompany($companyId);
    }
}
