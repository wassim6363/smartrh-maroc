<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\AmoRate;
use App\Models\AuditLog;
use App\Models\CnssRate;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\DemoRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeDocument;
use App\Models\EmployeePayrollItem;
use App\Models\GeneratedDocument;
use App\Models\IrBracket;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LegalSetting;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Plan;
use App\Models\Position;
use App\Models\ProfessionalExpenseRate;
use App\Models\SeniorityBonusRate;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Services\Documents\HrDocumentGenerator;
use App\Services\Documents\PayslipPdfGenerator;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@smartrh.test'],
            ['name' => 'SmartRH Admin', 'password' => 'password'],
        );
        $admin->syncRoles(['Super Admin']);

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@smartrh.test'],
            ['name' => 'Company Owner', 'password' => 'password'],
        );
        $owner->syncRoles(['Company Owner']);

        $company = Company::query()->create([
            'name' => 'SmartRH Demo SARL',
            'legal_name' => 'SmartRH Demo SARL',
            'ice' => '001525458000083',
            'rc' => '123456',
            'if' => '40123456',
            'cnss_number' => '9876543',
            'address' => 'Twin Center, Boulevard Zerktouni',
            'city' => 'Casablanca',
            'phone' => '+212 522 00 00 00',
            'email' => 'contact@smartrh.test',
        ]);

        $owner->update(['company_id' => $company->id]);

        User::query()->updateOrCreate(
            ['email' => 'payroll@smartrh.test'],
            ['name' => 'Payroll Manager', 'password' => 'password', 'company_id' => $company->id],
        )->syncRoles(['Payroll Manager']);

        User::query()->updateOrCreate(
            ['email' => 'rh@smartrh.test'],
            ['name' => 'RH Manager', 'password' => 'password', 'company_id' => $company->id],
        )->syncRoles(['RH Manager']);

        $hr = Department::query()->create(['company_id' => $company->id, 'name' => 'Human Resources']);
        $finance = Department::query()->create(['company_id' => $company->id, 'name' => 'Finance']);
        $tech = Department::query()->create(['company_id' => $company->id, 'name' => 'Technology']);
        $sales = Department::query()->create(['company_id' => $company->id, 'name' => 'Sales']);
        $support = Department::query()->create(['company_id' => $company->id, 'name' => 'Customer Support']);

        $hrManager = Position::query()->create(['company_id' => $company->id, 'department_id' => $hr->id, 'title' => 'HR Manager', 'min_salary' => 12000, 'max_salary' => 22000]);
        $accountant = Position::query()->create(['company_id' => $company->id, 'department_id' => $finance->id, 'title' => 'Accountant', 'min_salary' => 8000, 'max_salary' => 15000]);
        $developer = Position::query()->create(['company_id' => $company->id, 'department_id' => $tech->id, 'title' => 'Software Developer', 'min_salary' => 10000, 'max_salary' => 25000]);
        $salesRep = Position::query()->create(['company_id' => $company->id, 'department_id' => $sales->id, 'title' => 'Sales Representative', 'min_salary' => 6000, 'max_salary' => 14000]);
        $supportAgent = Position::query()->create(['company_id' => $company->id, 'department_id' => $support->id, 'title' => 'Support Agent', 'min_salary' => 5000, 'max_salary' => 12000]);

        $employees = collect([
            Employee::query()->create([
                'company_id' => $company->id,
                'user_id' => User::query()->updateOrCreate(['email' => 'amina.employee@smartrh.test'], ['name' => 'Amina Bennani', 'password' => 'password'])->assignRole('Employee')->id,
                'department_id' => $hr->id,
                'position_id' => $hrManager->id,
                'employee_number' => 'EMP-001',
                'first_name' => 'Amina',
                'last_name' => 'Bennani',
                'cin' => 'BE123456',
                'cnss_number' => '11223344',
                'email' => 'amina.bennani@smartrh.test',
                'phone' => '+212 661 11 22 33',
                'birth_date' => '1990-03-14',
                'hire_date' => '2022-01-10',
                'probation_ends_at' => '2022-04-10',
                'family_situation' => 'married',
                'dependents_count' => 2,
                'base_salary' => 18000,
            ]),
            Employee::query()->create([
                'company_id' => $company->id,
                'user_id' => User::query()->updateOrCreate(['email' => 'youssef.employee@smartrh.test'], ['name' => 'Youssef El Fassi', 'password' => 'password'])->assignRole('Employee')->id,
                'department_id' => $finance->id,
                'position_id' => $accountant->id,
                'employee_number' => 'EMP-002',
                'first_name' => 'Youssef',
                'last_name' => 'El Fassi',
                'cin' => 'BK654321',
                'cnss_number' => '55667788',
                'email' => 'youssef.elfassi@smartrh.test',
                'phone' => '+212 662 22 33 44',
                'birth_date' => '1988-08-22',
                'hire_date' => '2021-06-01',
                'probation_ends_at' => '2021-09-01',
                'family_situation' => 'single',
                'dependents_count' => 0,
                'base_salary' => 11500,
            ]),
            Employee::query()->create([
                'company_id' => $company->id,
                'user_id' => User::query()->updateOrCreate(['email' => 'salma.employee@smartrh.test'], ['name' => 'Salma Ouazzani', 'password' => 'password'])->assignRole('Employee')->id,
                'department_id' => $tech->id,
                'position_id' => $developer->id,
                'employee_number' => 'EMP-003',
                'first_name' => 'Salma',
                'last_name' => 'Ouazzani',
                'cin' => 'CN998877',
                'cnss_number' => '99887766',
                'email' => 'salma.ouazzani@smartrh.test',
                'phone' => '+212 663 33 44 55',
                'birth_date' => '1995-11-05',
                'hire_date' => '2023-02-15',
                'probation_ends_at' => '2023-05-15',
                'family_situation' => 'married',
                'dependents_count' => 1,
                'base_salary' => 16000,
            ]),
            Employee::query()->create([
                'company_id' => $company->id,
                'user_id' => User::query()->updateOrCreate(['email' => 'mehdi.employee@smartrh.test'], ['name' => 'Mehdi Rahmani', 'password' => 'password'])->assignRole('Employee')->id,
                'department_id' => $sales->id,
                'position_id' => $salesRep->id,
                'employee_number' => 'EMP-004',
                'first_name' => 'Mehdi',
                'last_name' => 'Rahmani',
                'cin' => 'D445566',
                'cnss_number' => null,
                'email' => 'mehdi.rahmani@smartrh.test',
                'phone' => '+212 664 44 55 66',
                'birth_date' => '1993-04-19',
                'hire_date' => now()->subMonths(2)->toDateString(),
                'probation_ends_at' => now()->addDays(20)->toDateString(),
                'contract_type' => 'cdd',
                'family_situation' => 'single',
                'dependents_count' => 0,
                'base_salary' => 8500,
            ]),
            Employee::query()->create([
                'company_id' => $company->id,
                'user_id' => User::query()->updateOrCreate(['email' => 'nadia.employee@smartrh.test'], ['name' => 'Nadia Tazi', 'password' => 'password'])->assignRole('Employee')->id,
                'department_id' => $support->id,
                'position_id' => $supportAgent->id,
                'employee_number' => 'EMP-005',
                'first_name' => 'Nadia',
                'last_name' => 'Tazi',
                'cin' => 'E778899',
                'cnss_number' => '44332211',
                'email' => 'nadia.tazi@smartrh.test',
                'phone' => '+212 665 55 66 77',
                'birth_date' => '1997-09-28',
                'hire_date' => '2024-09-01',
                'probation_ends_at' => '2024-12-01',
                'family_situation' => 'divorced',
                'dependents_count' => 1,
                'base_salary' => 7200,
            ]),
        ]);

        foreach ($employees as $employee) {
            if ($employee->employee_number !== 'EMP-004') {
                EmployeeBankAccount::query()->create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'bank_name' => 'Attijariwafa Bank',
                    'rib' => '007780000000000000000000',
                ]);
            }

            EmployeeDocument::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'type' => 'cin',
                'title' => 'CIN copy',
                'expires_at' => $employee->employee_number === 'EMP-005' ? now()->subDay()->toDateString() : null,
                'metadata' => ['demo' => true],
            ]);
        }

        $template = ContractTemplate::query()->create([
            'company_id' => $company->id,
            'name' => 'CDI standard',
            'contract_type' => 'cdi',
            'body' => 'Contrat de travail CDI entre {{ company.name }} et {{ employee.full_name }}.',
        ]);

        foreach ($employees as $employee) {
            $contractType = $employee->contract_type ?: 'cdi';

            Contract::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'contract_template_id' => $template->id,
                'contract_number' => 'CTR-' . $employee->employee_number,
                'type' => $contractType,
                'start_date' => $employee->hire_date,
                'end_date' => $contractType === 'cdd' ? now()->addDays(35)->toDateString() : null,
                'salary' => $employee->base_salary,
                'status' => 'signed',
                'signed_at' => now()->subMonths(3),
            ]);
        }

        $annualLeave = LeaveType::query()->create([
            'company_id' => $company->id,
            'name' => 'Annual leave',
            'code' => 'ANNUAL',
            'is_paid' => true,
            'annual_days' => 18,
        ]);

        LeaveRequest::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employees[0]->id,
            'leave_type_id' => $annualLeave->id,
            'starts_at' => now()->addWeeks(2)->toDateString(),
            'ends_at' => now()->addWeeks(2)->addDays(4)->toDateString(),
            'days' => 5,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        LeaveRequest::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employees[3]->id,
            'leave_type_id' => $annualLeave->id,
            'starts_at' => now()->addDays(10)->toDateString(),
            'ends_at' => now()->addDays(12)->toDateString(),
            'days' => 3,
            'status' => 'pending',
            'reason' => 'Family event',
        ]);

        Absence::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employees[1]->id,
            'date' => now()->subDays(8)->toDateString(),
            'hours' => 4,
            'type' => 'justified',
            'justified' => true,
            'payroll_impact' => false,
            'reason' => 'Medical appointment',
        ]);

        Absence::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employees[4]->id,
            'date' => '2026-06-10',
            'hours' => 0,
            'duration_days' => 1,
            'type' => 'unjustified',
            'justified' => false,
            'payroll_impact' => true,
            'deduction_amount' => 200,
            'reason' => 'Absence non payee demo',
        ]);

        foreach ([
            ['Prime transport imposable', 'TRANSPORT', 'prime', 500],
            ['Prime rendement', 'RENDEMENT', 'prime', 700],
        ] as [$label, $code, $type, $amount]) {
            EmployeePayrollItem::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employees[4]->id,
                'label' => $label,
                'code' => $code,
                'type' => $type,
                'amount' => $amount,
                'taxable' => true,
                'recurring' => true,
                'starts_at' => '2026-06-01',
                'active' => true,
                'notes' => 'Element de paie demo Nadia Tazi.',
            ]);
        }

        PayrollSetting::query()->create([
            'company_id' => $company->id,
            'default_working_hours' => 191,
            'minimum_wage' => 3120,
        ]);

        LegalSetting::query()->create([
            'year' => 2026,
            'label' => 'Paramètres légaux démo 2026',
            'cnss_ceiling' => 6000,
            'cnss_employee_rate' => 0.0448,
            'cnss_short_term_employee_rate' => 0.0052,
            'cnss_long_term_employee_rate' => 0.0396,
            'amo_employee_rate' => 0.0226,
            'professional_expenses_rate' => 0.20,
            'professional_expenses_ceiling' => null,
            'effective_from' => '2026-01-01',
            'active' => true,
            'notes' => 'Valeurs de demonstration a verifier avant production.',
        ]);

        foreach ([
            [2, 5, 0.05],
            [5, 12, 0.10],
            [12, 20, 0.15],
            [20, 25, 0.20],
            [25, null, 0.25],
        ] as [$minYears, $maxYears, $rate]) {
            SeniorityBonusRate::query()->create([
                'min_years' => $minYears,
                'max_years' => $maxYears,
                'rate' => $rate,
                'effective_from' => '2026-01-01',
                'active' => true,
                'notes' => 'Barème démo prime ancienneté.',
            ]);
        }

        CnssRate::query()->create([
            'name' => 'CNSS employee and employer demo',
            'employee_rate' => 0.0448,
            'employer_rate' => 0.0898,
            'salary_ceiling' => 6000,
            'effective_from' => '2024-01-01',
        ]);

        AmoRate::query()->create([
            'name' => 'AMO demo',
            'employee_rate' => 0.0226,
            'employer_rate' => 0.0411,
            'effective_from' => '2024-01-01',
        ]);

        ProfessionalExpenseRate::query()->create([
            'rate' => 0.20,
            'monthly_ceiling' => null,
            'effective_from' => '2024-01-01',
        ]);

        foreach ([
            [0, 2500, 0.00, 0],
            [2500.01, 4166.67, 0.10, 250],
            [4166.68, 5000, 0.20, 666.67],
            [5000.01, 6666.67, 0.30, 1166.67],
            [6666.68, 15000, 0.34, 1433.33],
            [15000.01, null, 0.38, 2033.33],
        ] as [$min, $max, $rate, $deduction]) {
            IrBracket::query()->create([
                'min_amount' => $min,
                'max_amount' => $max,
                'rate' => $rate,
                'deduction' => $deduction,
                'effective_from' => '2024-01-01',
            ]);
        }

        $period = PayrollPeriod::query()->create([
            'company_id' => $company->id,
            'name' => 'Juin 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'payment_date' => '2026-06-30',
            'status' => 'draft',
        ]);

        $calculator = app(PayrollCalculator::class);
        $payslipPdfGenerator = app(PayslipPdfGenerator::class);
        foreach ($employees as $employee) {
            $payslip = $calculator->calculate($employee, $period);
            $payslipPdfGenerator->generate($payslip);
        }

        $period->update(['status' => 'generated']);

        app(HrDocumentGenerator::class)->generate($employees[0], 'attestation_travail', $admin);
        app(HrDocumentGenerator::class)->generate($employees[3], 'contract_cdd', $admin);

        $this->call(PlanSeeder::class);
        $businessPlan = Plan::query()->where('slug', 'business')->firstOrFail();

        $subscription = Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $businessPlan->id,
            'status' => 'active',
            'starts_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->addMonth()->endOfMonth()->toDateString(),
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->startOfMonth()->toDateString(),
            'current_period_end' => now()->endOfMonth()->toDateString(),
            'amount' => $businessPlan->monthly_price,
        ]);

        Invoice::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-' . now()->format('Ym') . '-001',
            'amount' => $businessPlan->monthly_price,
            'status' => 'paid',
            'issued_at' => now()->startOfMonth()->toDateString(),
            'due_at' => now()->addDays(10)->toDateString(),
            'paid_at' => now()->toDateString(),
        ]);

        DemoRequest::query()->create([
            'full_name' => 'Client Démo',
            'company_name' => 'Cabinet Fiduciaire Exemple',
            'phone' => '+212600000000',
            'email' => 'client@example.com',
            'business_type' => 'Cabinet comptable',
            'employees_count' => 45,
            'message' => 'Je souhaite une démonstration pour gérer plusieurs sociétés clientes.',
        ]);

        $payrollTicket = SupportTicket::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'employee_id' => $employees[0]->id,
            'subject' => 'Question sur la génération des bulletins',
            'category' => 'payroll',
            'priority' => 'normal',
            'status' => 'open',
            'message' => 'Comment vérifier les paramètres CNSS et AMO avant production ?',
        ]);

        SupportTicketReply::query()->create([
            'support_ticket_id' => $payrollTicket->id,
            'user_id' => $admin->id,
            'message' => 'Merci pour votre demande. Les paramètres doivent être validés avec votre expert-comptable avant production.',
            'is_internal' => false,
        ]);

        SupportTicketReply::query()->create([
            'support_ticket_id' => $payrollTicket->id,
            'user_id' => $admin->id,
            'message' => 'Vérifier les taux légaux configurés pour 2026 avant la prochaine démo.',
            'is_internal' => true,
        ]);

        SupportTicket::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'employee_id' => $employees[1]->id,
            'subject' => 'Accès urgent au portail salarié',
            'category' => 'account',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'assigned_to_user_id' => $admin->id,
            'assigned_to' => $admin->id,
            'message' => 'Un salarié ne reçoit pas son accès au portail.',
        ]);

        SupportTicket::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'employee_id' => $employees[2]->id,
            'subject' => 'Contrat CDI téléchargé',
            'category' => 'contract',
            'priority' => 'low',
            'status' => 'resolved',
            'resolved_at' => now()->subDays(2),
            'message' => 'Le contrat CDI a bien été généré et téléchargé.',
        ]);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'event' => 'demo_seeded',
            'new_values' => ['company' => $company->name, 'employees' => $employees->count()],
        ]);
    }
}
