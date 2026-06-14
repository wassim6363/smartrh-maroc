<?php

namespace App\Services\Payroll;

use App\Models\Payslip;

class PayslipCumulCalculator
{
    public function update(Payslip $payslip): Payslip
    {
        $period = $payslip->payrollPeriod;
        $yearStart = $period->ends_at->copy()->startOfYear();

        $query = Payslip::query()
            ->where('employee_id', $payslip->employee_id)
            ->whereHas('payrollPeriod', function ($query) use ($yearStart, $period) {
                $query
                    ->whereDate('ends_at', '>=', $yearStart)
                    ->whereDate('ends_at', '<=', $period->ends_at);
            })
            ->whereIn('status', ['generated', 'validated', 'sent', 'closed']);

        $payslip->forceFill([
            'ytd_gross_salary' => round((float) (clone $query)->sum('gross_salary'), 2),
            'ytd_taxable_income' => round((float) (clone $query)->sum('taxable_income'), 2),
            'ytd_ir' => round((float) (clone $query)->sum('ir_net'), 2),
            'ytd_cnss' => round((float) (clone $query)->sum('cnss_employee'), 2),
            'ytd_amo' => round((float) (clone $query)->sum('amo_employee'), 2),
            'ytd_net_pay' => round((float) (clone $query)->sum('net_pay'), 2),
            'ytd_total_deductions' => round((float) (clone $query)->sum('total_deductions'), 2),
        ])->save();

        return $payslip->refresh();
    }
}
