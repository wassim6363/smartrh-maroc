<?php

namespace App\Services\Documents;

use App\Models\EmployeeDocumentRequest;
use App\Services\Audit\AuditLogger;

class EmployeeDocumentRequestWorkflow
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function approve(EmployeeDocumentRequest $request, ?string $responseMessage = null): EmployeeDocumentRequest
    {
        $request->update([
            'status' => 'approved',
            'response_message' => $responseMessage,
            'processed_at' => now(),
        ]);

        $this->audit->log('document_request_approved', $request, [], [], [
            'employee_id' => $request->employee_id,
            'type' => $request->type,
        ]);

        return $request->refresh();
    }

    public function reject(EmployeeDocumentRequest $request, string $responseMessage): EmployeeDocumentRequest
    {
        $request->update([
            'status' => 'rejected',
            'response_message' => $responseMessage,
            'processed_at' => now(),
        ]);

        $this->audit->log('document_request_rejected', $request, [], [], [
            'employee_id' => $request->employee_id,
            'type' => $request->type,
        ]);

        return $request->refresh();
    }

    public function complete(EmployeeDocumentRequest $request, ?int $generatedDocumentId = null, ?string $responseMessage = null): EmployeeDocumentRequest
    {
        $request->update([
            'status' => 'completed',
            'generated_document_id' => $generatedDocumentId,
            'response_message' => $responseMessage ?? $request->response_message,
            'processed_at' => now(),
        ]);

        $this->audit->log('document_request_completed', $request, [], [], [
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'generated_document_id' => $request->generated_document_id,
        ]);

        return $request->refresh();
    }
}
