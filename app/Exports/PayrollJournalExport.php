<?php

namespace App\Exports;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollJournalExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    private int $payrollPeriodId;

    public function __construct(int $payrollPeriodId)
    {
        $this->payrollPeriodId = $payrollPeriodId;
    }

    public function query(): Builder
    {
        return \App\Models\Payslip::query()
            ->with('employee')
            ->where('payroll_period_id', $this->payrollPeriodId);
    }

    public function headings(): array
    {
        return [
            'Référence',
            'CIN',
            'Nom complet',
            'Email',
            'Numéro CNSS',
            'Salaire brut',
            'Brut imposable',
            'CNSS (part salariale)',
            'AMO (part salariale)',
            'IR Net',
            'Indemnités exonérées',
            'Total retenues',
            'Net à payer',
        ];
    }

    public function map($payslip): array
    {
        return [
            $payslip->reference,
            $payslip->employee?->cin,
            $payslip->employee ? trim($payslip->employee->first_name.' '.$payslip->employee->last_name) : '',
            $payslip->employee?->email,
            $payslip->employee?->cnss_number,
            $payslip->gross_total,
            $payslip->taxable_gross,
            $payslip->cnss_employee,
            $payslip->amo_employee,
            $payslip->ir_net,
            $payslip->exempt_allowances,
            $payslip->total_deductions,
            $payslip->net_to_pay,
        ];
    }
}
