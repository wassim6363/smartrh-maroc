<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Models\PayrollPeriod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Services\Documents\ContractGeneratorService;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayslipGenerationService;
use App\Services\Saas\SubscriptionLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_cannot_add_employee_after_reaching_max_employees(): void
    {
        [$company] = $this->subscribedCompany(['max_employees' => 1]);

        $this->employee($company);

        $service = app(SubscriptionLimitService::class);

        $this->assertFalse($service->canAddEmployee($company));
    }

    public function test_company_cannot_generate_payslip_after_reaching_monthly_limit(): void
    {
        [$company, $subscription] = $this->subscribedCompany(['max_payslips_per_month' => 1]);
        $employee = $this->employee($company);
        $period = $this->period($company);

        SubscriptionUsage::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'employees_count' => 1,
            'payslips_generated' => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(PayslipGenerationService::class)->generate($this->payslipPayload($company, $employee, $period));
    }

    public function test_company_cannot_generate_contract_after_reaching_monthly_limit(): void
    {
        [$company, $subscription] = $this->subscribedCompany(['max_contracts_per_month' => 1]);
        $employee = $this->employee($company);

        SubscriptionUsage::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'employees_count' => 1,
            'contracts_generated' => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(ContractGeneratorService::class)->generate([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'type' => 'CDI',
            'start_date' => '2026-06-01',
        ]);
    }

    public function test_usage_increments_after_payslip_generation(): void
    {
        [$company, $subscription] = $this->subscribedCompany(['max_payslips_per_month' => 5]);
        $employee = $this->employee($company);
        $period = $this->period($company);
        $this->legalRules();

        app(PayrollCalculator::class)->calculate($employee, $period);

        $this->assertDatabaseHas('subscription_usage', [
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'payslips_generated' => 1,
        ]);
    }

    public function test_usage_increments_after_contract_generation(): void
    {
        [$company, $subscription] = $this->subscribedCompany(['max_contracts_per_month' => 5]);
        $employee = $this->employee($company);
        $template = $this->template($company);

        app(ContractGeneratorService::class)->generate([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'contract_template_id' => $template->id,
            'type' => 'CDI',
            'start_date' => '2026-06-01',
            'salary' => 6000,
            'job_title' => 'Responsable RH',
            'city' => 'Casablanca',
        ]);

        $this->assertDatabaseHas('subscription_usage', [
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'contracts_generated' => 1,
        ]);
    }

    public function test_starter_cannot_use_document_requests(): void
    {
        [$company] = $this->subscribedCompany([
            'slug' => 'starter',
            'document_requests_enabled' => false,
        ]);

        $this->assertFalse(app(SubscriptionLimitService::class)->canUseDocumentRequests($company));
    }

    public function test_business_can_use_document_requests(): void
    {
        [$company] = $this->subscribedCompany([
            'slug' => 'business',
            'document_requests_enabled' => true,
        ]);

        $this->assertTrue(app(SubscriptionLimitService::class)->canUseDocumentRequests($company));
    }

    public function test_active_subscription_is_required_when_plans_exist(): void
    {
        $company = Company::query()->create(['name' => 'No Subscription Co']);
        $this->plan();

        $service = app(SubscriptionLimitService::class);

        $this->assertFalse($service->canAddEmployee($company));
        $this->assertFalse($service->canGeneratePayslip($company));
        $this->assertFalse($service->canGenerateContract($company));
    }

    private function subscribedCompany(array $planOverrides = []): array
    {
        $company = Company::query()->create(['name' => 'SaaS Test Co']);
        $plan = $this->plan($planOverrides);
        $subscription = Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->addMonth()->endOfMonth()->toDateString(),
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->startOfMonth()->toDateString(),
            'current_period_end' => now()->endOfMonth()->toDateString(),
            'amount' => $plan->monthly_price,
        ]);

        return [$company, $subscription, $plan];
    }

    private function plan(array $overrides = []): Plan
    {
        $slug = $overrides['slug'] ?? 'business-test';

        return Plan::query()->create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'monthly_price' => 299,
            'yearly_price' => 2990,
            'max_companies' => 1,
            'max_employees' => 50,
            'max_payslips_per_month' => 100,
            'max_contracts_per_month' => 50,
            'employee_portal_enabled' => true,
            'document_requests_enabled' => true,
            'audit_logs_enabled' => true,
            'api_access_enabled' => false,
            'is_active' => true,
            'sort_order' => 10,
            ...$overrides,
        ]);
    }

    private function employee(Company $company): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'employee_number' => 'SAA-' . $company->employees()->count(),
            'first_name' => 'Amina',
            'last_name' => 'Bennani',
            'hire_date' => '2026-01-01',
            'base_salary' => 6000,
        ]);
    }

    private function period(Company $company): PayrollPeriod
    {
        return PayrollPeriod::query()->create([
            'company_id' => $company->id,
            'name' => 'Juin 2026',
            'month' => 6,
            'year' => 2026,
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);
    }

    private function legalRules(): void
    {
        LegalSetting::query()->create([
            'year' => 2026,
            'label' => 'SaaS test',
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

    private function template(Company $company): ContractTemplate
    {
        return ContractTemplate::query()->create([
            'company_id' => $company->id,
            'type' => 'CDI',
            'title' => 'Contrat CDI',
            'name' => 'CDI',
            'language' => 'fr',
            'content_html' => '<p>{{company_name}} embauche {{employee_name}}.</p>',
            'body' => 'Template',
            'contract_type' => 'cdi',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function payslipPayload(Company $company, Employee $employee, PayrollPeriod $period): array
    {
        return [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'year' => 2026,
            'month' => 6,
            'items' => [
                ['code' => 'BASE', 'label' => 'Salaire de base', 'type' => 'earning', 'amount' => 6000, 'subject_to_cnss' => true, 'subject_to_amo' => true, 'subject_to_ir' => true, 'is_tax_exempt' => false],
            ],
        ];
    }
}
