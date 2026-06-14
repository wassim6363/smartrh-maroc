<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Services\Audit\AuditLogger;
use App\Services\Saas\SubscriptionLimitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollCalculator
{
    public function __construct(
        private readonly TaxRuleResolver $rules,
        private readonly PayslipCumulCalculator $cumuls,
    ) {}

    /**
     * Moroccan payroll rules are configurable in the database. The rules and
     * resulting payslips must be verified by an accountant before production.
     */
    public function calculate(Employee $employee, PayrollPeriod $period): Payslip
    {
        app(SubscriptionLimitService::class)->assertCanGeneratePayslip($employee->company);

        return DB::transaction(function () use ($employee, $period) {
            $settings = PayrollSetting::query()
                ->where('company_id', $employee->company_id)
                ->latest()
                ->first();

            $periodDate = $period->ends_at;
            $legalSetting = $this->rules->legalSetting($periodDate);
            $cnss = $this->rules->cnssRate($employee->company_id, $periodDate);
            $amo = $this->rules->amoRate($employee->company_id, $periodDate);
            $expenseRate = $this->rules->professionalExpenseRate($employee->company_id, $periodDate);

            $baseSalary = $this->money((float) $employee->base_salary);
            $completedYears = $employee->hire_date ? (int) floor($employee->hire_date->diffInYears($periodDate)) : 0;
            $seniorityRate = $this->rules->seniorityBonusRate($completedYears, $periodDate);
            $seniorityBonus = $completedYears >= 2 && $seniorityRate
                ? $this->money($baseSalary * (float) $seniorityRate->rate)
                : 0.0;

            $absenceAmount = $this->absenceAmount($employee, $period);
            $items = $this->calculationItems(
                $employee,
                $employee->payrollItems()->activeForPeriod($period)->get(),
                $baseSalary,
                $seniorityBonus,
            );

            $earningItems = collect($items)->where('type', 'earning');
            $deductionItems = collect($items)->where('type', 'deduction');

            $grossTotal = max(0.0, $this->money($earningItems->sum('amount') - $absenceAmount));
            $taxableGross = max(0.0, $this->money($earningItems->where('subject_to_ir', true)->sum('amount') - $absenceAmount));
            $cnssSubjectAmount = max(0.0, $this->money($earningItems->where('subject_to_cnss', true)->sum('amount') - $absenceAmount));
            $amoSubjectAmount = max(0.0, $this->money($earningItems->where('subject_to_amo', true)->sum('amount') - $absenceAmount));
            $taxExemptAllowances = $this->money($earningItems->where('is_tax_exempt', true)->sum('amount'));

            $cnssCeiling = $legalSetting?->cnss_ceiling ?? $cnss?->salary_ceiling;
            $cnssRate = $legalSetting?->cnss_employee_rate ?? $cnss?->employee_rate;
            $cnssBase = $cnssCeiling !== null ? min($cnssSubjectAmount, (float) $cnssCeiling) : $cnssSubjectAmount;
            $cnssEmployee = ($settings?->include_cnss ?? true) && $cnssRate
                ? $this->money($cnssBase * (float) $cnssRate)
                : 0.0;
            $cnssEmployer = ($settings?->include_cnss ?? true) && $cnss
                ? $this->money($cnssBase * (float) $cnss->employer_rate)
                : 0.0;

            $amoRate = $legalSetting?->amo_employee_rate ?? $amo?->employee_rate;
            $amoBase = $amoSubjectAmount;
            $amoEmployee = ($settings?->include_amo ?? true) && $amoRate
                ? $this->money($amoBase * (float) $amoRate)
                : 0.0;
            $amoEmployer = ($settings?->include_amo ?? true) && $amo
                ? $this->money($amoBase * (float) $amo->employer_rate)
                : 0.0;

            $salaryAfterContributions = $this->money($taxableGross - $cnssEmployee - $amoEmployee);
            $professionalExpensesRate = $legalSetting?->professional_expenses_rate ?? $expenseRate?->rate;
            $professionalExpensesCeiling = $legalSetting?->professional_expenses_ceiling ?? $expenseRate?->monthly_ceiling;
            $professionalExpensesBase = $legalSetting?->professional_expenses_base ?: 'taxable_after_contributions';
            $professionalExpensesAmountBase = $professionalExpensesBase === 'taxable_gross'
                ? $taxableGross
                : $salaryAfterContributions;
            $professionalExpenses = $professionalExpensesRate
                ? $this->money($professionalExpensesAmountBase * (float) $professionalExpensesRate)
                : 0.0;

            if ($professionalExpensesCeiling !== null) {
                $professionalExpenses = min($professionalExpenses, (float) $professionalExpensesCeiling);
            }

            $taxableIncome = max(0.0, $this->money($salaryAfterContributions - $professionalExpenses));
            $irBracket = $this->rules->irBracket($employee->company_id, $taxableIncome, $periodDate);
            $irGross = ($settings?->include_ir ?? true) && $irBracket
                ? max(0.0, $this->money(($taxableIncome * (float) $irBracket->rate) - (float) $irBracket->deduction))
                : 0.0;
            $irNet = $irGross;

            $advances = $this->money($deductionItems->where('legacy_type', 'advance')->sum('amount'));
            $otherDeductions = $this->money($deductionItems->reject(fn (array $item): bool => $item['legacy_type'] === 'advance')->sum('amount'));
            $netDeductions = $this->money($advances + $otherDeductions);
            $totalDeductions = $this->money($cnssEmployee + $amoEmployee + $irNet + $netDeductions);
            $employerContributions = $this->money($cnssEmployer + $amoEmployer);
            $netPay = $this->money($salaryAfterContributions - $irNet - $netDeductions + $taxExemptAllowances);

            $snapshot = [
                'salaire_brut_total' => $grossTotal,
                'brut_imposable' => $taxableGross,
                'cnss_base' => $cnssBase,
                'cnss_employee' => $cnssEmployee,
                'amo_base' => $amoBase,
                'amo_employee' => $amoEmployee,
                'salary_after_contributions' => $salaryAfterContributions,
                'professional_expenses' => $professionalExpenses,
                'professional_expenses_base' => $professionalExpensesBase,
                'taxable_income' => $taxableIncome,
                'ir_net' => $irNet,
                'tax_exempt_allowances' => $taxExemptAllowances,
                'net_pay' => $netPay,
                'items' => collect($items)->map(fn (array $item): array => [
                    'label' => $item['label'],
                    'amount' => $item['amount'],
                    'type' => $item['type'],
                    'subject_to_cnss' => $item['subject_to_cnss'],
                    'subject_to_amo' => $item['subject_to_amo'],
                    'subject_to_ir' => $item['subject_to_ir'],
                    'is_tax_exempt' => $item['is_tax_exempt'],
                ])->values()->all(),
            ];

            $taxablePrimes = $this->money($earningItems
                ->filter(fn (array $item): bool => ! in_array($item['code'], ['BASE', 'PRIME_ANCIENNETE'], true) && $item['subject_to_ir'])
                ->sum('amount') + $seniorityBonus);

            $alreadyExists = Payslip::query()
                ->where('company_id', $employee->company_id)
                ->where('payroll_period_id', $period->id)
                ->where('employee_id', $employee->id)
                ->exists();

            $payslip = Payslip::query()->updateOrCreate(
                [
                    'company_id' => $employee->company_id,
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'reference' => $period->starts_at->format('Ym') . '-' . $employee->employee_number,
                    'base_salary' => $baseSalary,
                    'gross_total' => $grossTotal,
                    'taxable_gross' => $taxableGross,
                    'total_taxable_primes' => $taxablePrimes,
                    'total_taxable_indemnities' => 0,
                    'total_non_taxable_indemnities' => $taxExemptAllowances,
                    'total_overtime' => $this->money((float) $employee->overtime_amount),
                    'total_absences' => $absenceAmount,
                    'gross_salary' => $grossTotal,
                    'cnss_base' => $cnssBase,
                    'cnss_employee' => $cnssEmployee,
                    'amo_base' => $amoBase,
                    'amo_employee' => $amoEmployee,
                    'salary_after_contributions' => $salaryAfterContributions,
                    'taxable_before_professional_expenses' => $salaryAfterContributions,
                    'professional_expenses' => $professionalExpenses,
                    'taxable_net_income' => $taxableIncome,
                    'taxable_income' => $taxableIncome,
                    'ir_brut' => $irGross,
                    'ir_gross' => $irGross,
                    'ir_net' => $irNet,
                    'exempt_allowances' => $taxExemptAllowances,
                    'total_advances' => $advances,
                    'total_other_deductions' => $otherDeductions,
                    'net_deductions' => $netDeductions,
                    'total_deductions' => $totalDeductions,
                    'net_to_pay' => $netPay,
                    'net_pay' => $netPay,
                    'taxable_salary' => $taxableIncome,
                    'total_employee_deductions' => $totalDeductions,
                    'total_employer_contributions' => $employerContributions,
                    'net_salary' => $netPay,
                    'status' => 'generated',
                    'generated_at' => now(),
                    'calculation_snapshot' => [
                        'legal_setting_id' => $legalSetting?->id,
                        'cnss_rate_id' => $cnss?->id,
                        'amo_rate_id' => $amo?->id,
                        'professional_expense_rate_id' => $expenseRate?->id,
                        'ir_bracket_id' => $irBracket?->id,
                        'seniority_bonus_rate_id' => $seniorityRate?->id,
                        'completed_seniority_years' => $completedYears,
                        ...$snapshot,
                        'formula' => [
                            'gross_salary' => 'earning items - absences',
                            'taxable_gross' => 'earning items subject_to_ir - absences',
                            'cnss_base' => 'min(earning items subject_to_cnss - absences, cnss_ceiling)',
                            'amo_base' => 'earning items subject_to_amo - absences',
                            'salary_after_contributions' => 'taxable_gross - cnss_employee - amo_employee',
                            'professional_expenses' => 'configured base * rate, capped when configured',
                            'taxable_income' => 'salary_after_contributions - professional_expenses',
                            'net_pay' => 'salary_after_contributions - ir_net - deductions + tax_exempt_allowances',
                        ],
                    ],
                ],
            );

            $this->replaceLines($payslip, $items, [
                'absenceAmount' => $absenceAmount,
                'cnssBase' => $cnssBase,
                'cnssRate' => $cnssRate,
                'cnssEmployee' => $cnssEmployee,
                'amoBase' => $amoBase,
                'amoRate' => $amoRate,
                'amoEmployee' => $amoEmployee,
                'professionalExpensesAmountBase' => $professionalExpensesAmountBase,
                'professionalExpensesRate' => $professionalExpensesRate,
                'professionalExpenses' => $professionalExpenses,
                'taxableIncome' => $taxableIncome,
                'irNet' => $irNet,
                'advances' => $advances,
                'otherDeductions' => $otherDeductions,
                'netPay' => $netPay,
                'cnssEmployer' => $cnssEmployer,
                'amoEmployer' => $amoEmployer,
            ]);

            $payslip = $this->cumuls->update($payslip);

            app(AuditLogger::class)->log('payslip_generated', $payslip, [], ['reference' => $payslip->reference]);
            if (! $alreadyExists) {
                app(SubscriptionLimitService::class)->incrementPayslipUsage($employee->company);
            }

            return $payslip->refresh()->load(['company', 'employee', 'payrollPeriod', 'lines']);
        });
    }

    private function calculationItems(Employee $employee, Collection $employeeItems, float $baseSalary, float $seniorityBonus): array
    {
        $items = [[
            'code' => 'BASE',
            'label' => 'Salaire de base',
            'type' => 'earning',
            'legacy_type' => 'base',
            'amount' => $baseSalary,
            'subject_to_cnss' => true,
            'subject_to_amo' => true,
            'subject_to_ir' => true,
            'is_tax_exempt' => false,
        ]];

        if ($seniorityBonus > 0) {
            $items[] = [
                'code' => 'PRIME_ANCIENNETE',
                'label' => "Prime d'anciennete",
                'type' => 'earning',
                'legacy_type' => 'prime',
                'amount' => $seniorityBonus,
                'subject_to_cnss' => true,
                'subject_to_amo' => true,
                'subject_to_ir' => true,
                'is_tax_exempt' => false,
            ];
        }

        foreach ([
            ['SENIORITY_LEGACY', "Prime d'anciennete saisie", $employee->seniority_bonus],
            ['TRANSPORT_LEGACY', 'Indemnite transport saisie', $employee->transport_bonus],
            ['MEAL_LEGACY', 'Prime panier saisie', $employee->meal_bonus],
            ['PERFORMANCE_LEGACY', 'Prime rendement saisie', $employee->performance_bonus],
            ['HS', 'Heures supplementaires', $employee->overtime_amount],
        ] as [$code, $label, $amount]) {
            if ((float) $amount <= 0) {
                continue;
            }

            $items[] = [
                'code' => $code,
                'label' => $label,
                'type' => 'earning',
                'legacy_type' => 'prime',
                'amount' => $this->money((float) $amount),
                'subject_to_cnss' => true,
                'subject_to_amo' => true,
                'subject_to_ir' => true,
                'is_tax_exempt' => false,
            ];
        }

        foreach ($employeeItems as $item) {
            $legacyType = (string) $item->type;
            $type = in_array($legacyType, ['advance', 'deduction', 'other'], true)
                ? 'deduction'
                : (in_array($legacyType, ['earning', 'deduction'], true) ? $legacyType : 'earning');
            $isTaxExempt = (bool) $item->is_tax_exempt || $legacyType === 'indemnity_non_taxable';
            $subjectToIr = (bool) $item->subject_to_ir && (bool) ($item->taxable ?? true);

            $items[] = [
                'code' => $item->code ?: str((string) $item->label)->slug('_')->upper()->limit(40, '')->toString(),
                'label' => $item->label,
                'type' => $type,
                'legacy_type' => $legacyType,
                'amount' => $this->money((float) $item->amount),
                'subject_to_cnss' => $type === 'earning' && (bool) $item->subject_to_cnss,
                'subject_to_amo' => $type === 'earning' && (bool) $item->subject_to_amo,
                'subject_to_ir' => $type === 'earning' && $subjectToIr,
                'is_tax_exempt' => $type === 'earning' && $isTaxExempt,
            ];
        }

        return $items;
    }

    private function absenceAmount(Employee $employee, PayrollPeriod $period): float
    {
        return $this->money((float) $employee->absences()
            ->whereBetween('date', [$period->starts_at, $period->ends_at])
            ->where('payroll_impact', true)
            ->get()
            ->sum(function ($absence) use ($employee) {
                if ($absence->deduction_amount !== null) {
                    return (float) $absence->deduction_amount;
                }

                $hours = (float) $absence->hours;
                if ($hours <= 0 && $absence->duration_days) {
                    $hours = ((float) $absence->duration_days) * 8;
                }

                return ($hours / max((float) $employee->working_hours_per_month, 1)) * (float) $employee->base_salary;
            }));
    }

    private function replaceLines(Payslip $payslip, array $items, array $values): void
    {
        $payslip->lines()->delete();

        $lines = [];
        foreach ($items as $index => $item) {
            if ((float) $item['amount'] === 0.0) {
                continue;
            }

            $lines[] = [
                $item['type'],
                $item['code'],
                $item['label'] . ($item['is_tax_exempt'] ? ' (exoneree)' : ''),
                $item['amount'],
                null,
                $item['amount'],
                ($index + 1) * 10,
                $item,
            ];
        }

        $lines = [
            ...$lines,
            ['absence', 'ABS', 'Absences et retards', 0, null, $values['absenceAmount'], 700, []],
            ['deduction', 'CNSS-SAL', 'CNSS salarie', $values['cnssBase'], $values['cnssRate'], $values['cnssEmployee'], 800, []],
            ['deduction', 'AMO-SAL', 'AMO salarie', $values['amoBase'], $values['amoRate'], $values['amoEmployee'], 900, []],
            ['deduction', 'IR', 'IR net', $values['taxableIncome'], null, $values['irNet'], 1000, []],
            ['deduction', 'AVANCE', 'Avances', 0, null, $values['advances'], 1100, []],
            ['deduction', 'RETENUE', 'Autres retenues', 0, null, $values['otherDeductions'], 1200, []],
            ['info', 'FP', 'Frais professionnels', $values['professionalExpensesAmountBase'], $values['professionalExpensesRate'], $values['professionalExpenses'], 1300, []],
            ['net', 'NET', 'Net a payer', 0, null, $values['netPay'], 1400, []],
            ['employer_contribution', 'CNSS-PAT', 'CNSS patronale', $values['cnssBase'], null, $values['cnssEmployer'], 1500, []],
            ['employer_contribution', 'AMO-PAT', 'AMO patronale', $values['amoBase'], null, $values['amoEmployer'], 1600, []],
        ];

        foreach ($lines as [$type, $code, $label, $base, $rate, $amount, $sortOrder, $flags]) {
            if ($amount == 0 && ! in_array($code, ['BASE', 'NET'], true)) {
                continue;
            }

            $payslip->lines()->create([
                'company_id' => $payslip->company_id,
                'type' => $type,
                'code' => $code,
                'label' => $label,
                'base' => $base,
                'base_amount' => $base,
                'rate' => $rate,
                'amount' => $amount,
                'subject_to_cnss' => (bool) ($flags['subject_to_cnss'] ?? false),
                'subject_to_amo' => (bool) ($flags['subject_to_amo'] ?? false),
                'subject_to_ir' => (bool) ($flags['subject_to_ir'] ?? false),
                'is_tax_exempt' => (bool) ($flags['is_tax_exempt'] ?? false),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}
