<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Exports\PayrollJournalExport;
use App\Imports\EmployeesImport;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::query()->create(['name' => 'Test Co']);
        $this->user = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@test.local',
            'password' => 'password',
            'company_id' => $this->company->id,
        ]);

        $plan = Plan::query()->create([
            'name' => 'Business',
            'slug' => 'business',
            'monthly_price' => 299,
            'max_employees' => 50,
            'max_payslips_per_month' => 100,
            'max_contracts_per_month' => 50,
            'employee_portal_enabled' => true,
            'document_requests_enabled' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Subscription::query()->create([
            'company_id' => $this->company->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->addMonth()->endOfMonth(),
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'amount' => 299,
        ]);
    }

    public function test_import_creates_employees(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'employes.csv',
            "matricule,cin,email,prenom,nom,num_cnss,situation_familiale,nombre_enfants,poste,salaire_base,date_embauche\nEMP001,AB123456,test@example.com,Jean,Dupont,CNSS001,Célibataire,0,Développeur,5000,2026-01-15\n"
        );

        $import = new EmployeesImport($this->company);
        $import->import($file);

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'AB123456',
            'email' => 'test@example.com',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $this->assertEquals(1, $import->getImportedCount());
        $this->assertEquals(0, $import->getSkippedCount());
    }

    public function test_import_skips_duplicate_cin(): void
    {
        Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'AB123456',
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'hire_date' => now(),
            'base_salary' => 5000,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'employes.csv',
            "matricule,cin,email,prenom,nom,num_cnss,situation_familiale,nombre_enfants,poste,salaire_base,date_embauche\nEMP002,AB123456,test@example.com,Jean,Dupont,CNSS001,Célibataire,0,Développeur,5000,2026-01-15\n"
        );

        $import = new EmployeesImport($this->company);
        $import->import($file);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertEquals(1, $import->getSkippedCount());
    }

    public function test_import_skips_exceeding_limit(): void
    {
        Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'EXISTING',
            'email' => 'existing@test.com',
            'first_name' => 'Existing',
            'last_name' => 'Employee',
            'hire_date' => now(),
            'base_salary' => 5000,
        ]);

        $plan = Plan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'monthly_price' => 99,
            'max_employees' => 1,
            'max_payslips_per_month' => 10,
            'max_contracts_per_month' => 5,
            'employee_portal_enabled' => false,
            'document_requests_enabled' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->company->subscriptions()->update(['plan_id' => $plan->id]);

        $file = UploadedFile::fake()->createWithContent(
            'employes.csv',
            "matricule,cin,email,prenom,nom,num_cnss,situation_familiale,nombre_enfants,poste,salaire_base,date_embauche\nEMP002,NEWCIN,new@example.com,Jean,Dupont,CNSS001,Célibataire,0,Développeur,5000,2026-01-15\n"
        );

        $import = new EmployeesImport($this->company);
        $import->import($file);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertEquals(1, $import->getSkippedCount());
    }

    public function test_import_validates_required_fields(): void
    {
        $csv = "matricule,cin,email,prenom,nom\n";
        $csv .= "EMP001,,,Jean,Dupont\n";

        $file = UploadedFile::fake()->createWithContent('employes.csv', $csv);

        $import = new EmployeesImport($this->company);
        $import->import($file);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertEquals(1, $import->getSkippedCount());
    }

    public function test_export_scopes_to_company(): void
    {
        $otherCompany = Company::query()->create(['name' => 'Other']);
        Employee::query()->create([
            'company_id' => $otherCompany->id,
            'employee_number' => 'EMP002',
            'cin' => 'OTHER01',
            'email' => 'other@test.com',
            'first_name' => 'Other',
            'last_name' => 'User',
            'hire_date' => now(),
            'base_salary' => 4000,
        ]);

        Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'MY001',
            'email' => 'my@test.com',
            'first_name' => 'My',
            'last_name' => 'User',
            'hire_date' => now(),
            'base_salary' => 5000,
        ]);

        $export = new EmployeesExport($this->company->id);

        Excel::fake();
        $export->download('test.xlsx');

        Excel::assertDownloaded('test.xlsx');
    }

    public function test_export_route_returns_download_response(): void
    {
        $this->seedEmployee();

        $this->actingAs($this->user);

        $response = $this->get(route('exports.employees.xlsx'));

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('employes.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_export_route_requires_authentication(): void
    {
        $response = $this->get(route('exports.employees.xlsx'));

        $response->assertRedirect(route('login'));
    }

    public function test_export_route_returns_only_own_company_employees(): void
    {
        Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'OWN001',
            'email' => 'own@test.com',
            'first_name' => 'Own',
            'last_name' => 'User',
            'hire_date' => now(),
            'base_salary' => 5000,
        ]);

        $otherCompany = Company::query()->create(['name' => 'Other']);

        Employee::query()->create([
            'company_id' => $otherCompany->id,
            'employee_number' => 'EMPOTHER',
            'cin' => 'OTHER01',
            'email' => 'other@test.com',
            'first_name' => 'Other',
            'last_name' => 'User',
            'hire_date' => now(),
            'base_salary' => 4000,
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('exports.employees'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('OWN001', $content);
        $this->assertStringNotContainsString('OTHER01', $content);
    }

    public function test_export_route_without_company_id_returns_403(): void
    {
        $userWithoutCompany = User::query()->create([
            'name' => 'No Company',
            'email' => 'nocompany@test.com',
            'password' => 'password',
        ]);

        $response = $this->actingAs($userWithoutCompany)->get(route('exports.employees.xlsx'));

        $response->assertStatus(403);
    }

    public function test_payroll_journal_export_route_returns_download(): void
    {
        $employee = Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'PAY001',
            'email' => 'pay@test.com',
            'first_name' => 'Pay',
            'last_name' => 'Roll',
            'hire_date' => now(),
            'base_salary' => 8000,
        ]);

        $period = PayrollPeriod::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Juin 2026',
            'month' => 6,
            'year' => 2026,
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'status' => 'closed',
        ]);

        $payslip = Payslip::query()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'reference' => 'PAY-2026-06-001',
            'gross_total' => 8000,
            'taxable_gross' => 7500,
            'cnss_employee' => 300,
            'amo_employee' => 150,
            'ir_net' => 800,
            'exempt_allowances' => 500,
            'total_deductions' => 1250,
            'net_to_pay' => 6750,
            'status' => 'generated',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('exports.payroll-journal', ['period' => $period->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=journal_paie_2026_06.xlsx');
    }

    public function test_export_template_route_returns_csv(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('employees.import-template'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('employee_import_template.csv', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('first_name;last_name;cin;cnss_number;email;phone;job_title;department;hire_date;contract_type;base_salary;marital_status;children_count;status', $response->streamedContent());
    }

    public function test_super_admin_can_export_via_route(): void
    {
        Role::findOrCreate('Super Admin');
        $this->user->assignRole('Super Admin');

        $this->seedEmployee();

        $this->actingAs($this->user);
        $response = $this->get(route('exports.employees.xlsx'));

        $response->assertOk();
        $this->assertStringContainsString('employes.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_employee_csv_export_route_returns_valid_csv(): void
    {
        $employee = $this->seedEmployee();

        $response = $this->actingAs($this->user)->get(route('exports.employees'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('employes.csv', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('first_name;last_name;cin;cnss_number;email;phone;job_title;department;hire_date;contract_type;base_salary;marital_status;children_count;status', $content);
        $this->assertStringContainsString($employee->first_name, $content);
        $this->assertStringContainsString($employee->last_name, $content);
    }

    public function test_super_admin_can_export_selected_company_csv(): void
    {
        Role::findOrCreate('Super Admin');
        $this->user->assignRole('Super Admin');

        $selected = $this->seedEmployee();
        $otherCompany = Company::query()->create(['name' => 'Other Export Co']);
        Employee::query()->create([
            'company_id' => $otherCompany->id,
            'employee_number' => 'OTHERCSV',
            'cin' => 'OTHERCSV',
            'email' => 'othercsv@test.com',
            'first_name' => 'Other',
            'last_name' => 'Csv',
            'hire_date' => now(),
            'base_salary' => 4000,
        ]);

        $response = $this->actingAs($this->user)->get(route('exports.employees', ['company_id' => $this->company->id]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString($selected->cin, $content);
        $this->assertStringNotContainsString('OTHERCSV', $content);
    }

    public function test_filament_employees_page_contains_export_actions(): void
    {
        Role::findOrCreate('Company Owner');
        $this->user->assignRole('Company Owner');

        $response = $this->actingAs($this->user)->get('/admin/employees');

        $response->assertOk();
        $response->assertSee('Exporter Excel');
        $response->assertSee('Exporter CSV');
        $response->assertSee('Télécharger le modèle');
    }

    private function seedEmployee(): Employee
    {
        return Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'CIN001',
            'email' => 'emp@test.com',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'hire_date' => now(),
            'base_salary' => 5000,
        ]);
    }

    public function test_payroll_journal_export_contains_expected_columns(): void
    {
        $employee = Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'cin' => 'PAY001',
            'email' => 'pay@test.com',
            'first_name' => 'Pay',
            'last_name' => 'Roll',
            'hire_date' => now(),
            'base_salary' => 8000,
        ]);

        $period = PayrollPeriod::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Juin 2026',
            'month' => 6,
            'year' => 2026,
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);

        Payslip::query()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'reference' => 'PAY-2026-06-001',
            'gross_total' => 8000,
            'taxable_gross' => 7500,
            'cnss_employee' => 300,
            'amo_employee' => 150,
            'ir_net' => 800,
            'exempt_allowances' => 500,
            'total_deductions' => 1250,
            'net_to_pay' => 6750,
            'status' => 'generated',
        ]);

        $export = new PayrollJournalExport($period->id);

        Excel::fake();
        $export->download('journal.xlsx');

        Excel::assertDownloaded('journal.xlsx');
    }
}
