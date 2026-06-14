<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(string $event, ?Model $auditable = null, array $oldValues = [], array $newValues = [], array $metadata = []): AuditLog
    {
        $employeeId = $this->employeeId($auditable, $metadata);

        return AuditLog::query()->create([
            'company_id' => $auditable?->company_id ?? $metadata['company_id'] ?? null,
            'user_id' => Auth::id(),
            'employee_id' => $employeeId,
            'event' => $event,
            'action' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    private function employeeId(?Model $auditable, array $metadata): ?int
    {
        if ($auditable instanceof Employee) {
            return $auditable->id;
        }

        return $auditable?->employee_id ?? $metadata['employee_id'] ?? null;
    }
}
