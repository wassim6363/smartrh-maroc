<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeePayrollItem;
use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Models\PayrollPeriod;
use App\Models\SeniorityBonusRate;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollFormulaTest extends TestCase
{
    use RefreshDatabase;

    public function test_moroccan_payroll_formula_matches_requested_example(): void
    {
        $company = Company::query()->create(['name' => 'Formula Co']);
        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'employee_number' => 'F-001',
            'first_name' => 'Formula',
            'last_name' => 'Employee',
            'hire_date' => '2026-01-01',
            'base_salary' => 6000,
        ]);
        $period = PayrollPeriod::query()->create([
            'company_id' => $company->id,
            'name' => 'Juin 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);

        $this->seedLegalRules();

        foreach ([['Transport imposable', 500], ['Prime rendement', 700]] as [$label, $amount]) {
            EmployeePayrollItem::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'label' => $label,
                'type' => 'prime',
                'amount' => $amount,
                'taxable' => true,
                'active' => true,
            ]);
        }

        Absence::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'date' => '2026-06-12',
            'type' => 'unjustified',
            'payroll_impact' => true,
            'deduction_amount' => 200,
        ]);

        $payslip = app(PayrollCalculator::class)->calculate($employee, $period);

        $this->assertSame('7000.00', $payslip->gross_salary);
        $this->assertSame('6000.00', $payslip->cnss_base);
        $this->assertSame('268.80', $payslip->cnss_employee);
        $this->assertSame('7000.00', $payslip->amo_base);
        $this->assertSame('158.20', $payslip->amo_employee);
        $this->assertSame('6573.00', $payslip->taxable_before_professional_expenses);
        $this->assertSame('1314.60', $payslip->professional_expenses);
        $this->assertSame('5258.40', $payslip->taxable_income);
        $this->assertSame('410.85', $payslip->ir_net);
        $this->assertSame('837.85', $payslip->total_deductions);
        $this->assertSame('6162.15', $payslip->net_pay);
        $this->assertFalse($payslip->lines()->where('type', 'deduction')->where('code', 'FP')->exists());
        $this->assertTrue($payslip->lines()->where('type', 'info')->where('code', 'FP')->exists());
    }

    public function test_seniority_bonus_boundaries(): void
    {
        $company = Company::query()->create(['name' => 'Seniority Co']);
        $period = PayrollPeriod::query()->create([
            'company_id' => $company->id,
            'name' => 'Septembre 2026',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-09-30',
        ]);
        $this->seedZeroLegalRules();
        $this->seedSeniorityRules();

        $cases = [
            ['LESS-2', '2024-10-01', '0.00'],
            ['EXACT-2', '2024-09-30', '50.00'],
            ['EXACT-5', '2021-09-30', '100.00'],
            ['EXACT-12', '2014-09-30', '150.00'],
            ['EXACT-20', '2006-09-30', '200.00'],
            ['EXACT-25', '2001-09-30', '250.00'],
        ];

        foreach ($cases as [$number, $hireDate, $expected]) {
            $employee = Employee::query()->create([
                'company_id' => $company->id,
                'employee_number' => $number,
                'first_name' => 'Seniority',
                'last_name' => $number,
                'hire_date' => $hireDate,
                'base_salary' => 1000,
            ]);

            $payslip = app(PayrollCalculator::class)->calculate($employee, $period);

            $this->assertSame($expected, $payslip->lines()->where('code', 'PRIME_ANCIENNETE')->value('amount') ?? '0.00');
        }
    }

    public function test_moroccan_payroll_supports_tax_exempt_allowances_and_exact_6000_net(): void
    {
        $company = Company::query()->create(['name' => 'Net 6000 Co']);
        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'employee_number' => 'NET-6000',
            'first_name' => 'Youssef',
            'last_name' => 'Net 6000',
            'hire_date' => '2026-01-01',
            'base_salary' => 4900.28,
        ]);
        $period = PayrollPeriod::query()->create([
            'company_id' => $company->id,
            'name' => 'Juin 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);

        LegalSetting::query()->create([
            'year' => 2026,
            'label' => 'Exact net 6000',
            'cnss_ceiling' => 6000,
            'cnss_employee_rate' => 0.0448,
            'amo_employee_rate' => 0.0226,
            'professional_expenses_rate' => 0.35,
            'professional_expenses_base' => 'taxable_gross',
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);

        IrBracket::query()->create([
            'year' => 2026,
            'min_amount' => 0,
            'max_amount' => 3000,
            'rate' => 0,
            'deduction' => 0,
            'period_type' => 'monthly',
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);

        foreach ([['TRANSPORT', 'Indemnité transport', 500], ['PANIER', 'Prime panier', 930]] as [$code, $label, $amount]) {
            EmployeePayrollItem::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'code' => $code,
                'label' => $label,
                'type' => 'earning',
                'amount' => $amount,
                'subject_to_cnss' => false,
                'subject_to_amo' => false,
                'subject_to_ir' => false,
                'is_tax_exempt' => true,
                'recurring' => true,
                'active' => true,
            ]);
        }

        $payslip = app(PayrollCalculator::class)->calculate($employee, $period);
        $snapshot = $payslip->calculation_snapshot;

        $this->assertSame('6330.28', $payslip->gross_total);
        $this->assertSame('4900.28', $payslip->taxable_gross);
        $this->assertSame('4900.28', $payslip->cnss_base);
        $this->assertSame('219.53', $payslip->cnss_employee);
        $this->assertSame('4900.28', $payslip->amo_base);
        $this->assertSame('110.75', $payslip->amo_employee);
        $this->assertSame('4570.00', $payslip->salary_after_contributions);
        $this->assertSame('1715.10', $payslip->professional_expenses);
        $this->assertSame('2854.90', $payslip->taxable_income);
        $this->assertSame('0.00', $payslip->ir_net);
        $this->assertSame('1430.00', $payslip->exempt_allowances);
        $this->assertSame('6000.00', $payslip->net_pay);
        $this->assertSame(6330.28, $snapshot['salaire_brut_total']);
        $this->assertSame(4900.28, $snapshot['brut_imposable']);
        $this->assertSame('taxable_gross', $snapshot['professional_expenses_base']);
        $this->assertEquals(1430.00, $snapshot['tax_exempt_allowances']);
        $this->assertEquals(6000.00, $snapshot['net_pay']);
        $this->assertTrue($payslip->lines()->where('code', 'TRANSPORT')->where('is_tax_exempt', true)->exists());
        $this->assertTrue($payslip->lines()->where('code', 'PANIER')->where('is_tax_exempt', true)->exists());
        $this->assertFalse($payslip->lines()->where('type', 'deduction')->where('code', 'FP')->exists());
    }

    public function test_landing_demo_and_employee_login_pages_load(): void
    {
        $this->get('/')->assertOk();
        $this->get('/demo')->assertOk();
        $this->get('/employee/login')->assertOk();
    }

    private function seedLegalRules(): void
    {
        LegalSetting::query()->create([
            'year' => 2026,
            'label' => 'Test 2026',
            'cnss_ceiling' => 6000,
            'cnss_employee_rate' => 0.0448,
            'cnss_short_term_employee_rate' => 0.0052,
            'cnss_long_term_employee_rate' => 0.0396,
            'amo_employee_rate' => 0.0226,
            'professional_expenses_rate' => 0.20,
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);

        IrBracket::query()->create([
            'year' => 2026,
            'min_amount' => 0,
            'max_amount' => null,
            'rate' => 0.10,
            'deduction' => 114.99,
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);
    }

    private function seedZeroLegalRules(): void
    {
        LegalSetting::query()->create([
            'year' => 2026,
            'label' => 'Zero contribution test',
            'cnss_ceiling' => 6000,
            'cnss_employee_rate' => 0,
            'amo_employee_rate' => 0,
            'professional_expenses_rate' => 0,
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);

        IrBracket::query()->create([
            'year' => 2026,
            'min_amount' => 0,
            'rate' => 0,
            'deduction' => 0,
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);
    }

    private function seedSeniorityRules(): void
    {
        foreach ([[2, 5, 0.05], [5, 12, 0.10], [12, 20, 0.15], [20, 25, 0.20], [25, null, 0.25]] as [$min, $max, $rate]) {
            SeniorityBonusRate::query()->create([
                'min_years' => $min,
                'max_years' => $max,
                'rate' => $rate,
                'effective_from' => '2026-01-01',
                'active' => true,
            ]);
        }
    }
}
