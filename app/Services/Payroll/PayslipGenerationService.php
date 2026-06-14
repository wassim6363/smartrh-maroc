<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\LegalSetting;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\Saas\SubscriptionLimitService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayslipGenerationService
{
    public function __construct(
        private readonly PayrollCalculatorService $calculator,
        private readonly PayslipPdfService $pdfService,
    ) {}

    public function preview(array $data): array
    {
        $employee = Employee::query()
            ->where('company_id', (int) $data['company_id'])
            ->findOrFail((int) $data['employee_id']);

        $year = $this->yearFromData($data);
        $childrenCount = $data['children_count'] ?? $employee->children_count ?? $employee->dependents_count ?? 0;

        return $this->calculator->calculate(
            $data['items'],
            $this->legalSetting($year),
            $year,
            (int) $childrenCount,
        );
    }

    public function generate(array $data): Payslip
    {
        $companyId = (int) $data['company_id'];
        $employee = Employee::query()->where('company_id', $companyId)->findOrFail((int) $data['employee_id']);
        app(SubscriptionLimitService::class)->assertCanGeneratePayslip($employee->company);
        $result = $this->preview($data);

        return DB::transaction(function () use ($companyId, $employee, $data, $result): Payslip {
            $period = $this->periodFromData($companyId, $data);
            $reference = sprintf('%04d%02d-%s', $this->yearFromData($data), $this->monthFromData($data), $employee->employee_number ?: $employee->id);
            $alreadyExists = Payslip::query()
                ->where('company_id', $companyId)
                ->where('payroll_period_id', $period->id)
                ->where('employee_id', $employee->id)
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Un bulletin existe déjà pour cet employé et cette période.',
                ]);
            }

            $payslip = Payslip::query()->create(
                [
                    'company_id' => $companyId,
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'reference' => $reference,
                    'gross_total' => $result['gross_total'],
                    'taxable_gross' => $result['taxable_gross'],
                    'gross_salary' => $result['gross_total'],
                    'taxable_salary' => $result['taxable_net_income'],
                    'cnss_base' => $result['cnss_base'],
                    'amo_base' => $result['amo_base'],
                    'cnss_employee' => $result['cnss_employee'],
                    'amo_employee' => $result['amo_employee'],
                    'salary_after_contributions' => $result['salary_after_contributions'],
                    'taxable_before_professional_expenses' => $result['salary_after_contributions'],
                    'professional_expenses' => $result['professional_expenses'],
                    'taxable_net_income' => $result['taxable_net_income'],
                    'taxable_income' => $result['taxable_net_income'],
                    'ir_brut' => $result['ir_brut'],
                    'ir_gross' => $result['ir_brut'],
                    'ir_net' => $result['ir_net'],
                    'exempt_allowances' => $result['exempt_allowances'],
                    'total_non_taxable_indemnities' => $result['exempt_allowances'],
                    'net_deductions' => $result['net_deductions'],
                    'total_deductions' => $result['total_deductions'],
                    'total_employee_deductions' => $result['total_deductions'],
                    'net_to_pay' => $result['net_to_pay'],
                    'net_pay' => $result['net_to_pay'],
                    'net_salary' => $result['net_to_pay'],
                    'status' => 'generated',
                    'generated_at' => now(),
                    'calculation_snapshot' => [
                        'salaire_brut_total' => $result['gross_total'],
                        'brut_imposable' => $result['taxable_gross'],
                        'cnss_base' => $result['cnss_base'],
                        'cnss_employee' => $result['cnss_employee'],
                        'amo_base' => $result['amo_base'],
                        'amo_employee' => $result['amo_employee'],
                        'salary_after_contributions' => $result['salary_after_contributions'],
                        'professional_expenses' => $result['professional_expenses'],
                        'professional_expenses_base' => $result['professional_expenses_base'] ?? null,
                        'taxable_income' => $result['taxable_net_income'],
                        'ir_net' => $result['ir_net'],
                        'tax_exempt_allowances' => $result['exempt_allowances'],
                        'net_pay' => $result['net_to_pay'],
                        'items' => $data['items'],
                        'result' => $result,
                    ],
                ],
            );

            $payslip->lines()->delete();
            foreach ($data['items'] as $index => $item) {
                $payrollItem = PayrollItem::query()
                    ->where('company_id', $companyId)
                    ->where('code', $item['code'])
                    ->first();

                $payslip->lines()->create([
                    'company_id' => $companyId,
                    'payroll_item_id' => $payrollItem?->id,
                    'code' => $item['code'],
                    'label' => $item['label'],
                    'type' => $item['type'],
                    'amount' => $item['amount'],
                    'base' => $item['amount'],
                    'base_amount' => $item['amount'],
                    'rate' => null,
                    'subject_to_cnss' => $item['subject_to_cnss'] ?? false,
                    'subject_to_amo' => $item['subject_to_amo'] ?? false,
                    'subject_to_ir' => $item['subject_to_ir'] ?? false,
                    'is_tax_exempt' => $item['is_tax_exempt'] ?? false,
                    'sort_order' => ($index + 1) * 10,
                ]);
            }

            $this->pdfService->generate($payslip);
            app(SubscriptionLimitService::class)->incrementPayslipUsage($employee->company);

            return $payslip->refresh()->load(['company', 'employee', 'payrollPeriod', 'lines']);
        });
    }

    private function periodFromData(int $companyId, array $data): PayrollPeriod
    {
        if (! empty($data['payroll_period_id'])) {
            return PayrollPeriod::query()
                ->where('company_id', $companyId)
                ->findOrFail((int) $data['payroll_period_id']);
        }

        $startDate = CarbonImmutable::create((int) $data['year'], (int) $data['month'], 1)->startOfMonth();
        $endDate = $startDate->endOfMonth();

        $period = PayrollPeriod::query()
            ->where('company_id', $companyId)
            ->whereDate('starts_at', $startDate->toDateString())
            ->whereDate('ends_at', $endDate->toDateString())
            ->first();

        if ($period) {
            $period->forceFill([
                'month' => $period->month ?: (int) $data['month'],
                'year' => $period->year ?: (int) $data['year'],
                'start_date' => $period->start_date ?: $startDate->toDateString(),
                'end_date' => $period->end_date ?: $endDate->toDateString(),
            ])->save();

            return $period;
        }

        return PayrollPeriod::query()->create([
            'company_id' => $companyId,
            'name' => $startDate->locale('fr')->translatedFormat('F Y'),
            'month' => (int) $data['month'],
            'year' => (int) $data['year'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'starts_at' => $startDate->toDateString(),
            'ends_at' => $endDate->toDateString(),
            'status' => 'draft',
        ]);
    }

    private function legalSetting(int $year): LegalSetting
    {
        return LegalSetting::query()
            ->where('year', $year)
            ->where(function ($query) {
                $query->where('is_active', true)->orWhere('active', true);
            })
            ->latest('id')
            ->firstOrFail();
    }

    private function yearFromData(array $data): int
    {
        if (! empty($data['payroll_period_id'])) {
            $period = PayrollPeriod::query()->find((int) $data['payroll_period_id']);

            return (int) ($period?->year ?: $period?->starts_at?->year ?: $data['year']);
        }

        return (int) $data['year'];
    }

    private function monthFromData(array $data): int
    {
        if (! empty($data['payroll_period_id'])) {
            $period = PayrollPeriod::query()->find((int) $data['payroll_period_id']);

            return (int) ($period?->month ?: $period?->starts_at?->month ?: $data['month']);
        }

        return (int) $data['month'];
    }
}
