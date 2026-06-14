<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::plans() as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    ...$plan,
                    'company_limit' => $plan['max_companies'],
                    'employee_limit' => $plan['max_employees'],
                    'features' => [
                        'employee_portal' => $plan['employee_portal_enabled'],
                        'document_requests' => $plan['document_requests_enabled'],
                        'audit_logs' => $plan['audit_logs_enabled'],
                        'api_access' => $plan['api_access_enabled'],
                    ],
                ],
            );
        }
    }

    public static function plans(): array
    {
        return [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Plan RH essentiel pour tres petites entreprises.',
                'monthly_price' => 99,
                'yearly_price' => 990,
                'max_companies' => 1,
                'max_employees' => 10,
                'max_payslips_per_month' => 20,
                'max_contracts_per_month' => 10,
                'employee_portal_enabled' => true,
                'document_requests_enabled' => false,
                'audit_logs_enabled' => true,
                'api_access_enabled' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Plan complet pour PME marocaines.',
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
                'sort_order' => 20,
            ],
            [
                'name' => 'Cabinet',
                'slug' => 'cabinet',
                'description' => 'Plan multi-societes pour cabinets et fiduciaires.',
                'monthly_price' => 999,
                'yearly_price' => 9990,
                'max_companies' => 50,
                'max_employees' => 1000,
                'max_payslips_per_month' => 3000,
                'max_contracts_per_month' => 1000,
                'employee_portal_enabled' => true,
                'document_requests_enabled' => true,
                'audit_logs_enabled' => true,
                'api_access_enabled' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Plan sur devis avec limites elevees.',
                'monthly_price' => 0,
                'yearly_price' => null,
                'max_companies' => 999999,
                'max_employees' => 999999,
                'max_payslips_per_month' => 999999,
                'max_contracts_per_month' => 999999,
                'employee_portal_enabled' => true,
                'document_requests_enabled' => true,
                'audit_logs_enabled' => true,
                'api_access_enabled' => true,
                'is_active' => true,
                'sort_order' => 40,
            ],
        ];
    }
}
