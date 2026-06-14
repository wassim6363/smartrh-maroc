<?php

namespace Tests\Unit;

use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Services\Payroll\PayrollCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_taxable_gross_excludes_transport_and_panier(): void
    {
        $this->seedRules();

        $this->assertSame(4900.28, $this->calculator()->calculateTaxableGross($this->items()));
    }

    public function test_cnss_calculation(): void
    {
        $legal = $this->seedRules();

        $this->assertSame(219.53, $this->calculator()->calculateCnss(4900.28, $legal));
    }

    public function test_amo_calculation(): void
    {
        $legal = $this->seedRules();

        $this->assertSame(110.75, $this->calculator()->calculateAmo(4900.28, $legal));
    }

    public function test_professional_expenses_calculation(): void
    {
        $legal = $this->seedRules();

        $this->assertSame(1715.10, $this->calculator()->calculateProfessionalExpenses(4900.28, 4570.00, $legal));
    }

    public function test_taxable_net_income_calculation(): void
    {
        $this->seedRules();

        $this->assertSame(2854.90, $this->calculator()->calculateTaxableNetIncome(4570.00, 1715.10));
    }

    public function test_net_to_pay_is_6000(): void
    {
        $legal = $this->seedRules();

        $result = $this->calculator()->calculate($this->items(), $legal, 2026);

        $this->assertSame(6330.28, $result['gross_total']);
        $this->assertSame(4900.28, $result['taxable_gross']);
        $this->assertSame(219.53, $result['cnss_employee']);
        $this->assertSame(110.75, $result['amo_employee']);
        $this->assertSame(4570.00, $result['salary_after_contributions']);
        $this->assertSame(1715.10, $result['professional_expenses']);
        $this->assertSame(2854.90, $result['taxable_net_income']);
        $this->assertSame(0.0, $result['ir_net']);
        $this->assertSame(1430.00, $result['exempt_allowances']);
        $this->assertSame(6000.00, $result['net_to_pay']);
    }

    public function test_cnss_ceiling_is_applied_when_taxable_gross_exceeds_6000(): void
    {
        $legal = $this->seedRules();

        $this->assertSame(268.80, $this->calculator()->calculateCnss(10000, $legal));
    }

    public function test_taxable_transport_is_included_in_taxable_gross_and_subject_bases(): void
    {
        $legal = $this->seedRules();

        $result = $this->calculator()->calculate([
            [
                'code' => 'BASE',
                'label' => 'Salaire de base',
                'type' => 'earning',
                'amount' => 7700,
                'subject_to_cnss' => true,
                'subject_to_amo' => true,
                'subject_to_ir' => true,
                'is_tax_exempt' => false,
            ],
            [
                'code' => 'TRANSPORT_IMP',
                'label' => 'Prime transport imposable',
                'type' => 'earning',
                'amount' => 500,
                'subject_to_cnss' => true,
                'subject_to_amo' => true,
                'subject_to_ir' => true,
                'is_tax_exempt' => false,
            ],
        ], $legal, 2026);

        $this->assertSame(8200.00, $result['gross_total']);
        $this->assertSame(8200.00, $result['taxable_gross']);
        $this->assertSame(8200.00, $result['cnss_base']);
        $this->assertSame(8200.00, $result['amo_base']);
        $this->assertSame(0.0, $result['exempt_allowances']);
    }

    public function test_exempt_transport_is_excluded_from_taxable_gross_and_subject_bases(): void
    {
        $legal = $this->seedRules();

        $result = $this->calculator()->calculate([
            [
                'code' => 'BASE',
                'label' => 'Salaire de base',
                'type' => 'earning',
                'amount' => 7700,
                'subject_to_cnss' => true,
                'subject_to_amo' => true,
                'subject_to_ir' => true,
                'is_tax_exempt' => false,
            ],
            [
                'code' => 'TRANSPORT_EXO',
                'label' => 'Indemnite transport exoneree',
                'type' => 'earning',
                'amount' => 500,
                'subject_to_cnss' => false,
                'subject_to_amo' => false,
                'subject_to_ir' => false,
                'is_tax_exempt' => true,
            ],
        ], $legal, 2026);

        $this->assertSame(8200.00, $result['gross_total']);
        $this->assertSame(7700.00, $result['taxable_gross']);
        $this->assertSame(7700.00, $result['cnss_base']);
        $this->assertSame(7700.00, $result['amo_base']);
        $this->assertSame(500.00, $result['exempt_allowances']);
    }

    private function calculator(): PayrollCalculatorService
    {
        return app(PayrollCalculatorService::class);
    }

    private function seedRules(): LegalSetting
    {
        $legal = LegalSetting::query()->create([
            'label' => 'Parametres legaux demo 2026',
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
            'min_amount' => 0,
            'max_amount' => 3000,
            'rate' => 0,
            'deduction' => 0,
            'period_type' => 'monthly',
            'effective_from' => '2025-12-31',
            'active' => true,
        ]);

        return $legal;
    }

    private function items(): array
    {
        return [
            [
                'code' => 'BASE',
                'label' => 'Salaire de base',
                'type' => 'earning',
                'amount' => 4900.28,
                'subject_to_cnss' => true,
                'subject_to_amo' => true,
                'subject_to_ir' => true,
                'is_tax_exempt' => false,
            ],
            [
                'code' => 'TRANSPORT',
                'label' => 'Indemnite transport',
                'type' => 'earning',
                'amount' => 500,
                'subject_to_cnss' => false,
                'subject_to_amo' => false,
                'subject_to_ir' => false,
                'is_tax_exempt' => true,
            ],
            [
                'code' => 'PANIER',
                'label' => 'Prime panier',
                'type' => 'earning',
                'amount' => 930,
                'subject_to_cnss' => false,
                'subject_to_amo' => false,
                'subject_to_ir' => false,
                'is_tax_exempt' => true,
            ],
        ];
    }
}
