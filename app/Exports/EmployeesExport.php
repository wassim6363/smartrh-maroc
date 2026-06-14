<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function __construct(private readonly int $companyId) {}

    public function query(): Builder
    {
        return Employee::query()
            ->where('company_id', $this->companyId)
            ->with('department')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'cin',
            'cnss_number',
            'email',
            'phone',
            'job_title',
            'department',
            'hire_date',
            'contract_type',
            'base_salary',
            'marital_status',
            'children_count',
            'status',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->first_name,
            $employee->last_name,
            $employee->cin,
            $employee->cnss_number,
            $employee->email,
            $employee->phone,
            $employee->job_title ?: $employee->position_label,
            $employee->department?->name ?: $employee->getAttribute('department'),
            $employee->hire_date?->format('Y-m-d'),
            $employee->contract_type,
            $employee->base_salary,
            $employee->marital_status ?: $employee->family_situation,
            $employee->children_count ?? $employee->dependents_count,
            $employee->status,
        ];
    }
}
