<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'probation_ends_at' => 'date',
        'termination_date' => 'date',
        'dependents_count' => 'integer',
        'base_salary' => 'decimal:2',
        'seniority_bonus' => 'decimal:2',
        'transport_bonus' => 'decimal:2',
        'meal_bonus' => 'decimal:2',
        'performance_bonus' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'working_hours_per_month' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Employee $employee) {
            app(AuditLogger::class)->log('employee_created', $employee, [], $employee->only(['employee_number', 'first_name', 'last_name']));
        });

        static::updated(function (Employee $employee) {
            $event = $employee->wasChanged('base_salary') ? 'salary_changed' : 'employee_updated';
            app(AuditLogger::class)->log($event, $employee, $employee->getOriginal(), $employee->getChanges());
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getDepartmentLabelAttribute(): string
    {
        return $this->getRelationValue('department')?->name ?: (string) ($this->attributes['department'] ?? '');
    }

    public function getPositionLabelAttribute(): string
    {
        return $this->getRelationValue('position')?->title ?: (string) ($this->attributes['job_title'] ?? '');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function employeeContracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(EmployeePayrollItem::class);
    }

    public function hasManyGeneratedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
