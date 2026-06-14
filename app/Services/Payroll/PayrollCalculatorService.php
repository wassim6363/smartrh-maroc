<?php

namespace App\Services\Payroll;

use App\Models\IrBracket;
use App\Models\LegalSetting;

class PayrollCalculatorService
{
    public function calculate(array $items, LegalSetting $legalSetting, int $year, int $childrenCount = 0): array
    {
        $grossTotal = $this->calculateGrossTotal($items);
        $taxableGross = $this->calculateTaxableGross($items);
        $cnssBase = $this->calculateSubjectBase($items, 'subject_to_cnss');
        $amoBase = $this->calculateSubjectBase($items, 'subject_to_amo');
        $cnssEmployee = $this->calculateCnss($cnssBase, $legalSetting);
        $amoEmployee = $this->calculateAmo($amoBase, $legalSetting);
        $salaryAfterContributions = $this->calculateSalaryAfterContributions($taxableGross, $cnssEmployee, $amoEmployee);
        $professionalExpenses = $this->calculateProfessionalExpenses($taxableGross, $salaryAfterContributions, $legalSetting);
        $professionalExpensesBase = $this->professionalExpensesBase($legalSetting);
        $taxableNetIncome = $this->calculateTaxableNetIncome($salaryAfterContributions, $professionalExpenses);
        $ir = $this->calculateIr($taxableNetIncome, $year, $childrenCount);
        $exemptAllowances = $this->calculateExemptAllowances($items);
        $netDeductions = $this->calculateNetDeductions($items);
        $netToPay = $this->calculateNetToPay($salaryAfterContributions, $ir['ir_net'], $exemptAllowances, $netDeductions);

        return [
            'gross_total' => $grossTotal,
            'taxable_gross' => $taxableGross,
            'cnss_base' => $this->roundMoney($cnssBase),
            'amo_base' => $this->roundMoney($amoBase),
            'cnss_employee' => $cnssEmployee,
            'amo_employee' => $amoEmployee,
            'salary_after_contributions' => $salaryAfterContributions,
            'professional_expenses' => $professionalExpenses,
            'professional_expenses_base' => $professionalExpensesBase,
            'taxable_net_income' => $taxableNetIncome,
            'taxable_income' => $taxableNetIncome,
            'ir_brut' => $ir['ir_brut'],
            'ir_net' => $ir['ir_net'],
            'exempt_allowances' => $exemptAllowances,
            'tax_exempt_allowances' => $exemptAllowances,
            'net_deductions' => $netDeductions,
            'total_deductions' => $this->roundMoney($cnssEmployee + $amoEmployee + $ir['ir_net'] + $netDeductions),
            'net_to_pay' => $netToPay,
        ];
    }

    public function calculateGrossTotal(array $items): float
    {
        return $this->roundMoney(collect($items)
            ->filter(fn (array $item) => $this->type($item) === 'earning' && $this->bool($item, 'affects_gross', true))
            ->sum(fn (array $item) => $this->amount($item)));
    }

    public function calculateTaxableGross(array $items): float
    {
        return $this->roundMoney(collect($items)
            ->filter(fn (array $item) => $this->type($item) === 'earning')
            ->filter(fn (array $item) => $this->bool($item, 'subject_to_ir') && ! $this->bool($item, 'is_tax_exempt'))
            ->sum(fn (array $item) => $this->amount($item)));
    }

    public function calculateCnss(float $taxableGross, LegalSetting $legalSetting): float
    {
        $ceiling = $legalSetting->cnss_ceiling !== null ? (float) $legalSetting->cnss_ceiling : null;
        $base = $ceiling !== null ? min($taxableGross, $ceiling) : $taxableGross;

        return $this->roundMoney($base * (float) $legalSetting->cnss_employee_rate);
    }

    public function calculateAmo(float $taxableGross, LegalSetting $legalSetting): float
    {
        return $this->roundMoney($taxableGross * (float) $legalSetting->amo_employee_rate);
    }

    public function calculateSalaryAfterContributions(float $taxableGross, float $cnss, float $amo): float
    {
        return $this->roundMoney($taxableGross - $cnss - $amo);
    }

    public function calculateProfessionalExpenses(float $taxableGross, float $salaryAfterContributions, LegalSetting $legalSetting): float
    {
        $base = in_array($this->professionalExpensesBase($legalSetting), ['taxable_after_contributions', 'salary_after_contributions'], true)
            ? $salaryAfterContributions
            : $taxableGross;

        $rate = $legalSetting->professional_expense_rate ?? $legalSetting->professional_expenses_rate ?? 0;
        $expenses = $this->roundMoney($base * (float) $rate);
        $ceiling = $legalSetting->professional_expense_ceiling ?? $legalSetting->professional_expenses_ceiling;

        return $ceiling !== null ? min($expenses, (float) $ceiling) : $expenses;
    }

    private function professionalExpensesBase(LegalSetting $legalSetting): string
    {
        return $legalSetting->professional_expenses_base
            ?: ($legalSetting->getAttribute('professional_expense_base') ?: 'taxable_after_contributions');
    }

    public function calculateTaxableNetIncome(float $salaryAfterContributions, float $professionalExpenses): float
    {
        return max(0.0, $this->roundMoney($salaryAfterContributions - $professionalExpenses));
    }

    public function calculateIr(float $taxableNetIncome, int $year, int $childrenCount = 0): array
    {
        $bracket = IrBracket::query()
            ->where(function ($query) use ($year) {
                $query->where('year', $year)->orWhereNull('year');
            })
            ->where('period_type', 'monthly')
            ->where('min_amount', '<=', $taxableNetIncome)
            ->where(function ($query) use ($taxableNetIncome) {
                $query->whereNull('max_amount')->orWhere('max_amount', '>=', $taxableNetIncome);
            })
            ->orderByRaw('year is null')
            ->orderByDesc('year')
            ->orderByDesc('min_amount')
            ->first();

        $irBrut = $bracket
            ? max(0.0, $this->roundMoney(($taxableNetIncome * (float) $bracket->rate) - (float) $bracket->deduction))
            : 0.0;

        $familyDeduction = (float) (LegalSetting::query()
            ->where('year', $year)
            ->where(function ($query) {
                $query->where('is_active', true)->orWhere('active', true);
            })
            ->latest('id')
            ->value('family_deduction_amount') ?? 0);

        $irNet = max(0.0, $this->roundMoney($irBrut - ($childrenCount * $familyDeduction)));

        return [
            'ir_brut' => $irBrut,
            'family_deduction' => $this->roundMoney($childrenCount * $familyDeduction),
            'ir_net' => $irNet,
        ];
    }

    public function calculateExemptAllowances(array $items): float
    {
        return $this->roundMoney(collect($items)
            ->filter(fn (array $item) => $this->type($item) === 'earning')
            ->filter(fn (array $item) => $this->bool($item, 'is_tax_exempt') || $this->bool($item, 'is_non_taxable_allowance'))
            ->sum(fn (array $item) => $this->amount($item)));
    }

    public function calculateNetDeductions(array $items): float
    {
        return $this->roundMoney(collect($items)
            ->filter(fn (array $item) => $this->type($item) === 'deduction' && $this->bool($item, 'affects_net', true))
            ->sum(fn (array $item) => $this->amount($item)));
    }

    public function calculateNetToPay(float $salaryAfterContributions, float $irNet, float $exemptAllowances, float $netDeductions): float
    {
        return $this->roundMoney($salaryAfterContributions - $irNet + $exemptAllowances - $netDeductions);
    }

    public function calculateGrossFromTargetNet(float $targetNet, array $baseItems, LegalSetting $legalSetting, int $year, int $childrenCount = 0): array
    {
        $low = 0.0;
        $high = max($targetNet * 3, 1);
        $items = $this->replaceBaseAmount($baseItems, $high);

        while ($this->calculate($items, $legalSetting, $year, $childrenCount)['net_to_pay'] < $targetNet) {
            $high *= 2;
        }

        $result = [];
        for ($iteration = 1; $iteration <= 100; $iteration++) {
            $mid = ($low + $high) / 2;
            $items = $this->replaceBaseAmount($baseItems, $mid);
            $result = $this->calculate($items, $legalSetting, $year, $childrenCount);
            $delta = $result['net_to_pay'] - $targetNet;

            if (abs($delta) <= 0.01) {
                break;
            }

            if ($delta < 0) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        $result['target_net'] = $this->roundMoney($targetNet);
        $result['base_amount'] = $this->roundMoney($this->baseAmount($items));
        $result['iterations'] = $iteration;

        return $result;
    }

    public function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    private function calculateSubjectBase(array $items, string $flag): float
    {
        return $this->roundMoney(collect($items)
            ->filter(fn (array $item) => $this->type($item) === 'earning' && $this->bool($item, $flag))
            ->sum(fn (array $item) => $this->amount($item)));
    }

    private function replaceBaseAmount(array $items, float $amount): array
    {
        return collect($items)->map(function (array $item) use ($amount) {
            if (($item['code'] ?? null) === 'BASE') {
                $item['amount'] = $amount;
            }

            return $item;
        })->all();
    }

    private function baseAmount(array $items): float
    {
        return $this->amount(collect($items)->firstWhere('code', 'BASE') ?? []);
    }

    private function amount(array $item): float
    {
        return (float) ($item['amount'] ?? $item['default_amount'] ?? 0);
    }

    private function type(array $item): string
    {
        return (string) ($item['type'] ?? 'earning');
    }

    private function bool(array $item, string $key, bool $default = false): bool
    {
        return filter_var($item[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
    }
}
