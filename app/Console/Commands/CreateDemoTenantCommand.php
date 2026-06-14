<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Plan;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\PayslipPdfGenerator;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CreateDemoTenantCommand extends Command
{
    protected $signature = 'smartrh:create-demo-tenant {email : Admin email for the demo tenant}';
    protected $description = 'Create a demo tenant with company, admin user, employees, payslips, and contracts.';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::query()->where('email', $email)->exists()) {
            $this->error('The user already exists. Please use another email.');

            return self::FAILURE;
        }

        try {
            $result = DB::transaction(function () use ($email) {
                $company = Company::query()->create([
                    'name' => 'Demo ' . ucfirst(explode('@', $email)[0]),
                    'email' => $email,
                    'phone' => '+212600000000',
                    'city' => 'Casablanca',
                    'status' => 'active',
                ]);

                $department = Department::query()->create([
                    'company_id' => $company->id,
                    'name' => 'General',
                ]);

                $position = Position::query()->create([
                    'company_id' => $company->id,
                    'department_id' => $department->id,
                    'title' => 'Employee',
                    'min_salary' => 3120,
                    'max_salary' => 20000,
                ]);

                $admin = User::query()->create([
                    'name' => explode('@', $email)[0],
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                ]);

                $assignedRole = $this->assignDemoAdminRole($admin);
                $admin->refresh();

                $businessPlan = Plan::query()->where('slug', 'business')->first()
                    ?? Plan::query()->where('name', 'Business')->first()
                    ?? Plan::query()->where('is_active', true)->orderBy('sort_order')->first();

                if (! $businessPlan) {
                    throw new \RuntimeException('No active plan found in the database.');
                }

                Subscription::query()->create([
                    'company_id' => $company->id,
                    'plan_id' => $businessPlan->id,
                    'status' => 'trialing',
                    'starts_at' => now()->toDateString(),
                    'trial_ends_at' => now()->addDays(14)->toDateString(),
                    'ends_at' => now()->addDays(14)->toDateString(),
                    'billing_cycle' => 'monthly',
                    'current_period_start' => now()->toDateString(),
                    'current_period_end' => now()->addDays(14)->toDateString(),
                    'amount' => 0,
                ]);

                PayrollSetting::query()->create([
                    'company_id' => $company->id,
                    'default_working_hours' => 191,
                    'minimum_wage' => 3120,
                ]);

                $employees = [];

                $employees[] = Employee::query()->create([
                    'company_id' => $company->id,
                    'user_id' => $admin->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'employee_number' => 'EMP-001',
                    'first_name' => explode('@', $email)[0],
                    'last_name' => 'Demo',
                    'email' => $email,
                    'hire_date' => now()->subMonth()->toDateString(),
                    'base_salary' => 8000,
                    'status' => 'active',
                ]);

                $employees[] = Employee::query()->create([
                    'company_id' => $company->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'employee_number' => 'EMP-002',
                    'first_name' => 'Demo',
                    'last_name' => 'Employee',
                    'email' => 'employee.' . $email,
                    'hire_date' => now()->subMonth()->toDateString(),
                    'base_salary' => 5000,
                    'status' => 'active',
                ]);

                $template = ContractTemplate::query()->first();
                if ($template) {
                    foreach ($employees as $emp) {
                        Contract::query()->create([
                            'company_id' => $company->id,
                            'employee_id' => $emp->id,
                            'contract_template_id' => $template->id,
                            'contract_number' => 'CTR-DEMO-' . $emp->employee_number,
                            'type' => 'cdi',
                            'start_date' => $emp->hire_date,
                            'salary' => $emp->base_salary,
                            'status' => 'signed',
                            'signed_at' => now()->subMonth(),
                        ]);
                    }
                }

                $period = PayrollPeriod::query()->create([
                    'company_id' => $company->id,
                    'name' => now()->format('F Y'),
                    'starts_at' => now()->startOfMonth()->toDateString(),
                    'ends_at' => now()->endOfMonth()->toDateString(),
                    'payment_date' => now()->endOfMonth()->toDateString(),
                    'status' => 'draft',
                ]);

                try {
                    $calculator = app(PayrollCalculator::class);
                    $payslip = $calculator->calculate($employees[0], $period);
                    app(PayslipPdfGenerator::class)->generate($payslip);
                    $period->update(['status' => 'generated']);
                } catch (\Throwable $e) {
                    $this->warn('Payslip generation skipped: ' . $e->getMessage());
                }

                return [
                    'company' => $company,
                    'admin' => $admin,
                    'role' => $assignedRole,
                    'plan' => $businessPlan->name,
                    'trial_ends_at' => now()->addDays(14)->toDateString(),
                ];
            });

            $company = $result['company'];
            $admin = $result['admin'];

            try {
                app(AuditLogger::class)->log('demo_tenant_created', $company, [], [
                    'user_id' => $admin->id,
                    'email' => $email,
                    'plan' => $result['plan'],
                    'role' => $result['role'],
                    'trial_ends_at' => $result['trial_ends_at'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Demo tenant audit log failed: ' . $e->getMessage());
            }

            $this->newLine();
            $this->info('[OK] Demo tenant created successfully.');
            $this->line('Company:  ' . $company->name);
            $this->line('Admin:    ' . $admin->email);
            $this->line('Password: password');
            $this->line('Role:     ' . $result['role']);
            $this->line('Plan:     ' . $result['plan']);
            $this->line('Status:   trialing');
            $this->line('Login URL: ' . url('/admin'));
            $this->newLine();
            $this->warn('Please change password after first login.');
            $this->warn('Payroll legal rules must be validated by a Moroccan accountant before production.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function assignDemoAdminRole(User $user): string
    {
        $preferredRoles = [
            'company_admin',
            'owner',
            'admin',
            'super_admin',
        ];

        foreach ($preferredRoles as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $user->assignRole($role);

                return $roleName;
            }
        }

        $role = Role::firstOrCreate([
            'name' => 'company_admin',
            'guard_name' => 'web',
        ]);

        $user->assignRole($role);

        return 'company_admin';
    }
}