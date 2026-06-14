<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesExport;
use App\Exports\PayrollJournalExport;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    private const EMPLOYEE_IMPORT_HEADERS = [
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

    public function employeeTemplate()
    {
        return $this->csvDownload('employee_import_template.csv', self::EMPLOYEE_IMPORT_HEADERS, fn ($out) => null);
    }

    public function employees()
    {
        $companyId = $this->resolveCompanyId();

        if (! $companyId) {
            abort(403, 'Aucune entreprise associée.');
        }

        return Excel::download(new EmployeesExport($companyId), 'employes.xlsx');
    }

    public function employeesCsv()
    {
        $companyId = $this->resolveCompanyId();

        if (! $companyId) {
            abort(403, 'Aucune entreprise associée.');
        }

        return $this->csvDownload('employes.csv', self::EMPLOYEE_IMPORT_HEADERS, function ($out) use ($companyId): void {
            Employee::query()
                ->where('company_id', $companyId)
                ->with('department')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->each(function (Employee $employee) use ($out): void {
                    fputcsv($out, [
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
                    ], ';');
                });
        });
    }

    public function payslips(Request $request)
    {
        return $this->csv('payslips.csv', ['Société', 'Période', 'Salarié', 'Référence', 'Brut', 'Net', 'Statut'], function ($out) use ($request): void {
            $this->scopeToUserCompany(Payslip::query())
                ->with(['company', 'payrollPeriod', 'employee'])
                ->when($request->integer('period'), fn ($query, $periodId) => $query->where('payroll_period_id', $periodId))
                ->each(function (Payslip $payslip) use ($out): void {
                    fputcsv($out, [
                        $payslip->company?->name,
                        $payslip->payrollPeriod?->name,
                        $payslip->employee?->full_name,
                        $payslip->reference,
                        $payslip->gross_salary,
                        $payslip->net_salary,
                        $payslip->status,
                    ], ';');
                });
        });
    }

    public function leaveRequests()
    {
        return $this->csv('leave_requests.csv', ['Société', 'Salarié', 'Type', 'Début', 'Fin', 'Jours', 'Statut'], function ($out): void {
            $this->scopeToUserCompany(LeaveRequest::query()->with(['company', 'employee', 'leaveType']))->each(function (LeaveRequest $leaveRequest) use ($out): void {
                fputcsv($out, [
                    $leaveRequest->company?->name,
                    $leaveRequest->employee?->full_name,
                    $leaveRequest->leaveType?->name,
                    $leaveRequest->starts_at?->format('d/m/Y'),
                    $leaveRequest->ends_at?->format('d/m/Y'),
                    $leaveRequest->days,
                    $leaveRequest->status,
                ], ';');
            });
        });
    }

    public function payrollJournalExport(Request $request)
    {
        $periodId = $request->integer('period');

        $period = PayrollPeriod::query()
            ->where('company_id', $this->resolveCompanyId())
            ->whereKey($periodId)
            ->first();

        if (! $period) {
            abort(404, 'Période de paie introuvable.');
        }

        return Excel::download(
            new PayrollJournalExport($period->id),
            sprintf('journal_paie_%s_%s.xlsx', $period->year, str_pad($period->month, 2, '0', STR_PAD_LEFT)),
        );
    }

    private function csv(string $filename, array $headers, callable $writeRows)
    {
        return $this->csvDownload($filename, $headers, $writeRows);
    }

    private function scopeToUserCompany(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', $user?->currentCompanyId() ?: 0);
    }

    private function resolveCompanyId(): ?int
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            $requestedCompanyId = request()->integer('company_id');
            if ($requestedCompanyId && Company::query()->whereKey($requestedCompanyId)->exists()) {
                return $requestedCompanyId;
            }

            return $user->currentCompanyId()
                ?: $user->company_id
                ?: Company::query()->orderBy('id')->value('id');
        }

        return $user?->currentCompanyId() ?: null;
    }

    private function csvDownload(string $filename, array $headers, callable $writeRows)
    {
        return response()->streamDownload(function () use ($headers, $writeRows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            $writeRows($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
