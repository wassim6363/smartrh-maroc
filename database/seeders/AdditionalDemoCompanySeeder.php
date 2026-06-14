<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Plan;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdditionalDemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['name' => 'Atlas Digital Services'],
            [
                'legal_name' => 'Atlas Digital Services SARL',
                'ice' => '002222222000077',
                'rc' => '54321',
                'if' => '98765432',
                'if_number' => '98765432',
                'cnss_number' => '9988776',
                'address' => 'Boulevard Mohammed V',
                'city' => 'Rabat',
                'phone' => '+212 537 00 00 00',
                'email' => 'contact@atlas-demo.test',
                'status' => 'active',
            ],
        );

        $department = Department::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Operations'],
            ['description' => 'Equipe operations demo.'],
        );

        $position = Position::query()->updateOrCreate(
            ['company_id' => $company->id, 'title' => 'Charge RH'],
            ['department_id' => $department->id, 'min_salary' => 4500, 'max_salary' => 9000],
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'atlas.employee@smartrh.test'],
            ['name' => 'Atlas Employee', 'password' => 'password', 'company_id' => $company->id],
        );

        if (method_exists($user, 'assignRole') && ! $user->hasRole('Employee')) {
            $user->assignRole('Employee');
        }

        $employee = Employee::query()->updateOrCreate(
            ['company_id' => $company->id, 'employee_number' => 'ATL-001'],
            [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'first_name' => 'Imane',
                'last_name' => 'Alaoui',
                'cin' => 'AA123456',
                'cnss_number' => 'CNSS-ATL-001',
                'job_title' => 'Charge RH',
                'department' => 'Operations',
                'email' => 'imane.alaoui@atlas-demo.test',
                'hire_date' => '2026-02-01',
                'contract_type' => 'cdi',
                'base_salary' => 5200,
                'status' => 'active',
            ],
        );

        SupportTicket::query()->updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'Question facturation pack Starter'],
            [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'category' => 'billing',
                'priority' => 'normal',
                'status' => 'open',
                'message' => 'Merci de confirmer les limites du pack Starter pour notre équipe.',
            ],
        );

        $this->call(PlanSeeder::class);
        $starterPlan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->updateOrCreate(
            ['company_id' => $company->id, 'plan_id' => $starterPlan->id],
            [
                'status' => 'active',
                'starts_at' => now()->startOfMonth()->toDateString(),
                'ends_at' => now()->addMonth()->endOfMonth()->toDateString(),
                'billing_cycle' => 'monthly',
                'current_period_start' => now()->startOfMonth()->toDateString(),
                'current_period_end' => now()->endOfMonth()->toDateString(),
                'amount' => $starterPlan->monthly_price,
            ],
        );

        foreach ([
            ['Juin 2026', '2026-06-01', '2026-06-30'],
            ['Juillet 2026', '2026-07-01', '2026-07-31'],
        ] as [$name, $start, $end]) {
            PayrollPeriod::query()->updateOrCreate(
                ['company_id' => $company->id, 'starts_at' => $start, 'ends_at' => $end],
                [
                    'name' => $name,
                    'month' => (int) substr($start, 5, 2),
                    'year' => (int) substr($start, 0, 4),
                    'start_date' => $start,
                    'end_date' => $end,
                    'payment_date' => $end,
                    'status' => 'draft',
                ],
            );
        }

        PayrollSetting::query()->updateOrCreate(
            ['company_id' => $company->id],
            ['default_working_hours' => 191, 'minimum_wage' => 3120],
        );
    }
}
