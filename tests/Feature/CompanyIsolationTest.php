<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_view_other_company_employee(): void
    {
        Role::findOrCreate('Company Owner');
        $companyA = Company::query()->create(['name' => 'A']);
        $companyB = Company::query()->create(['name' => 'B']);
        $user = User::query()->create(['name' => 'Owner', 'email' => 'owner@test.local', 'password' => 'password', 'company_id' => $companyA->id]);
        $user->assignRole('Company Owner');
        $employeeB = Employee::query()->create(['company_id' => $companyB->id, 'employee_number' => 'B1', 'first_name' => 'B', 'last_name' => 'User', 'hire_date' => now(), 'base_salary' => 1]);

        $this->assertFalse($user->can('view', $employeeB));
    }

    public function test_company_user_cannot_download_other_company_payslip(): void
    {
        Role::findOrCreate('Company Owner');
        $companyA = Company::query()->create(['name' => 'A']);
        $companyB = Company::query()->create(['name' => 'B']);
        $user = User::query()->create(['name' => 'Owner', 'email' => 'owner@test.local', 'password' => 'password', 'company_id' => $companyA->id]);
        $user->assignRole('Company Owner');
        $employeeB = Employee::query()->create(['company_id' => $companyB->id, 'employee_number' => 'B1', 'first_name' => 'B', 'last_name' => 'User', 'hire_date' => now(), 'base_salary' => 1]);
        $period = PayrollPeriod::query()->create(['company_id' => $companyB->id, 'name' => 'P', 'starts_at' => now()->startOfMonth(), 'ends_at' => now()->endOfMonth()]);
        $payslip = Payslip::query()->create(['company_id' => $companyB->id, 'payroll_period_id' => $period->id, 'employee_id' => $employeeB->id, 'reference' => 'B1', 'status' => 'generated']);

        $this->actingAs($user)->get(route('payslips.download', $payslip))->assertForbidden();
    }

    public function test_employee_cannot_download_another_employee_document(): void
    {
        Role::findOrCreate('Employee');
        Storage::fake('local');
        $company = Company::query()->create(['name' => 'A']);
        $userA = User::query()->create(['name' => 'A', 'email' => 'a@test.local', 'password' => 'password']);
        $userA->assignRole('Employee');
        $employeeA = Employee::query()->create(['company_id' => $company->id, 'user_id' => $userA->id, 'employee_number' => 'A1', 'first_name' => 'A', 'last_name' => 'User', 'hire_date' => now(), 'base_salary' => 1]);
        $employeeB = Employee::query()->create(['company_id' => $company->id, 'employee_number' => 'B1', 'first_name' => 'B', 'last_name' => 'User', 'hire_date' => now(), 'base_salary' => 1]);
        Storage::disk('local')->put('companies/1/employees/2/documents/test.pdf', 'PDF');
        $document = GeneratedDocument::query()->create(['company_id' => $company->id, 'employee_id' => $employeeB->id, 'type' => 'test', 'title' => 'Test', 'file_path' => 'companies/1/employees/2/documents/test.pdf']);

        $this->actingAs($userA)->get(route('documents.download', $document))->assertForbidden();
    }
}
