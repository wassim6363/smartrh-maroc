<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayrollCalculationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_preview_returns_correct_6000_mad_example(): void
    {
        [$user, $company, $employee, $period] = $this->seedApiScenario();

        $this->actingAs($user)
            ->postJson('/api/payroll/calculate-preview', $this->payload($company, $employee, $period))
            ->assertOk()
            ->assertJsonPath('gross_total', 6330.28)
            ->assertJsonPath('taxable_gross', 4900.28)
            ->assertJsonPath('cnss_employee', 219.53)
            ->assertJsonPath('amo_employee', 110.75)
            ->assertJsonPath('professional_expenses', 1715.10)
            ->assertJsonPath('taxable_net_income', 2854.90)
            ->assertJsonPath('net_to_pay', 6000);
    }

    public function test_generate_payslip_creates_record_lines_and_pdf(): void
    {
        [$user, $company, $employee, $period] = $this->seedApiScenario();

        $response = $this->actingAs($user)
            ->postJson('/api/payroll/generate-payslip', $this->payload($company, $employee, $period))
            ->assertCreated()
            ->assertJsonPath('calculation.net_to_pay', 6000);

        $payslip = Payslip::query()->findOrFail($response->json('payslip_id'));

        $this->assertSame($company->id, $payslip->company_id);
        $this->assertSame($employee->id, $payslip->employee_id);
        $this->assertSame($period->id, $payslip->payroll_period_id);
        $this->assertSame('6000.00', $payslip->net_to_pay);
        $this->assertCount(3, $payslip->lines);
        $this->assertNotNull($payslip->pdf_path);
        Storage::disk('local')->assertExists($payslip->pdf_path);
    }

    public function test_duplicate_payslip_is_prevented(): void
    {
        [$user, $company, $employee, $period] = $this->seedApiScenario();

        $this->actingAs($user)
            ->postJson('/api/payroll/generate-payslip', $this->payload($company, $employee, $period))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson('/api/payroll/generate-payslip', $this->payload($company, $employee, $period))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id'])
            ->assertJsonPath('errors.employee_id.0', 'Un bulletin existe déjà pour cet employé et cette période.');
    }

    public function test_invalid_employee_company_combination_returns_validation_error(): void
    {
        [$user, $company, , $period] = $this->seedApiScenario();
        $otherCompany = Company::query()->create(['name' => 'Other Co']);
        $otherEmployee = Employee::query()->create([
            'company_id' => $otherCompany->id,
            'employee_number' => 'OTHER-1',
            'first_name' => 'Other',
            'last_name' => 'Employee',
            'hire_date' => '2026-01-01',
            'base_salary' => 5000,
        ]);

        $payload = $this->payload($company, $otherEmployee, $period);

        $this->actingAs($user)
            ->postJson('/api/payroll/generate-payslip', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id']);
    }

    public function test_user_cannot_generate_for_another_company(): void
    {
        [$user] = $this->seedApiScenario();
        $otherCompany = Company::query()->create(['name' => 'Forbidden Co']);
        $otherEmployee = Employee::query()->create([
            'company_id' => $otherCompany->id,
            'employee_number' => 'FORBID-1',
            'first_name' => 'Forbidden',
            'last_name' => 'Employee',
            'hire_date' => '2026-01-01',
            'base_salary' => 5000,
        ]);
        $otherPeriod = PayrollPeriod::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Juin 2026',
            'month' => 6,
            'year' => 2026,
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);

        $this->actingAs($user)
            ->postJson('/api/payroll/generate-payslip', $this->payload($otherCompany, $otherEmployee, $otherPeriod))
            ->assertForbidden();
    }

    private function seedApiScenario(): array
    {
        $company = Company::query()->create(['name' => 'API Co']);
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'API User',
            'email' => 'api-user@smartrh.test',
            'password' => 'password',
        ]);
        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'employee_number' => 'API-001',
            'first_name' => 'API',
            'last_name' => 'Employee',
            'hire_date' => '2026-01-01',
            'base_salary' => 4900.28,
            'status' => 'active',
        ]);
        $period = PayrollPeriod::query()->create([
            'company_id' => $company->id,
            'name' => 'Juin 2026',
            'month' => 6,
            'year' => 2026,
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);

        LegalSetting::query()->create([
            'label' => 'API 2026',
            'year' => 2026,
            'cnss_ceiling' => 6000,
            'cnss_employee_rate' => 0.0448,
            'amo_employee_rate' => 0.0226,
            'professional_expenses_rate' => 0.35,
            'professional_expenses_base' => 'taxable_gross',
            'professional_expense_rate' => 0.35,
            'family_deduction_amount' => 0,
            'effective_from' => '2025-12-31',
            'active' => true,
            'is_active' => true,
        ]);
        IrBracket::query()->create([
            'year' => 2026,
            'period_type' => 'monthly',
            'min_amount' => 0,
            'max_amount' => 3000,
            'rate' => 0,
            'deduction' => 0,
            'effective_from' => '2025-12-31',
            'active' => true,
        ]);

        return [$user, $company, $employee, $period];
    }

    private function payload(Company $company, Employee $employee, PayrollPeriod $period): array
    {
        return [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'year' => 2026,
            'month' => 6,
            'items' => [
                ['code' => 'BASE', 'label' => 'Salaire de base', 'type' => 'earning', 'amount' => 4900.28, 'subject_to_cnss' => true, 'subject_to_amo' => true, 'subject_to_ir' => true, 'is_tax_exempt' => false],
                ['code' => 'TRANSPORT', 'label' => 'Indemnité transport', 'type' => 'earning', 'amount' => 500, 'subject_to_cnss' => false, 'subject_to_amo' => false, 'subject_to_ir' => false, 'is_tax_exempt' => true],
                ['code' => 'PANIER', 'label' => 'Prime panier', 'type' => 'earning', 'amount' => 930, 'subject_to_cnss' => false, 'subject_to_amo' => false, 'subject_to_ir' => false, 'is_tax_exempt' => true],
            ],
        ];
    }
}
