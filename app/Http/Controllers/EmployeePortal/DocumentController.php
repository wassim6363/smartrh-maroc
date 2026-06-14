<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Models\EmployeeDocumentRequest;
use App\Models\GeneratedDocument;
use App\Services\Audit\AuditLogger;
use App\Services\Saas\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class DocumentController extends BaseEmployeePortalController
{
    public function index()
    {
        $employee = $this->employee();

        return view('employee.documents.index', [
            'employee' => $employee,
            'documents' => $employee->generatedDocuments()->latest()->paginate(12),
            'requests' => $employee->documentRequests()->with('generatedDocument')->latest()->paginate(8, ['*'], 'requests_page'),
            'requestTypes' => self::requestTypes(),
            'documentRequestsEnabled' => app(SubscriptionLimitService::class)->canUseDocumentRequests($employee->company),
        ]);
    }

    public function show(GeneratedDocument $document)
    {
        $this->abortUnlessOwn($document->employee_id);

        return view('employee.documents.show', [
            'employee' => $this->employee(),
            'document' => $document->load(['employee', 'company']),
        ]);
    }

    public function download(GeneratedDocument $document, AuditLogger $audit)
    {
        $this->abortUnlessOwn($document->employee_id);

        $path = $document->pdf_path ?: $document->file_path;
        abort_unless($path && Storage::disk(config('filesystems.private_disk'))->exists($path), 404);

        $audit->log('generated_document_pdf_downloaded_by_employee', $document, [], [], [
            'employee_id' => $document->employee_id,
            'reference' => $document->reference,
            'type' => $document->type,
        ]);

        return Storage::disk(config('filesystems.private_disk'))->download(
            $path,
            str($document->title)->slug() . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function storeRequest(Request $request, AuditLogger $audit)
    {
        $employee = $this->employee();
        abort_unless(app(SubscriptionLimitService::class)->canUseDocumentRequests($employee->company), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::requestTypes()))],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $documentRequest = EmployeeDocumentRequest::query()->create([
            ...$data,
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $audit->log('document_request_created', $documentRequest, [], [], [
            'employee_id' => $employee->id,
            'type' => $documentRequest->type,
        ]);
        app(SubscriptionLimitService::class)->incrementDocumentUsage($employee->company);

        return redirect()->route('employee.documents.requests.show', $documentRequest)
            ->with('status', 'Demande de document envoyée.');
    }

    public function showRequest(EmployeeDocumentRequest $documentRequest)
    {
        $this->abortUnlessOwn($documentRequest->employee_id);

        return view('employee.documents.request-show', [
            'employee' => $this->employee(),
            'request' => $documentRequest->load(['generatedDocument']),
            'requestTypes' => self::requestTypes(),
        ]);
    }

    public static function requestTypes(): array
    {
        return [
            'ATTESTATION_TRAVAIL' => 'Attestation de travail',
            'CERTIFICAT_TRAVAIL' => 'Certificat de travail',
            'BULLETIN_PAIE' => 'Bulletin de paie',
            'AUTRE' => 'Autre',
        ];
    }
}
