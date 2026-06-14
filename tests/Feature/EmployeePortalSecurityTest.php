<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocumentRequest;
use App\Models\GeneratedDocument;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Documents\ContractSignatureService;
use App\Services\Documents\EmployeeDocumentRequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeePortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_view_another_employee_payslip(): void
    {
        [$employeeA, $employeeB] = $this->employees();
        $payslip = $this->payslip($employeeB);

        $this->actingAs($employeeA->user)
            ->get(route('employee.payslips.show', $payslip))
            ->assertForbidden();
    }

    public function test_employee_cannot_download_another_employee_contract(): void
    {
        Storage::fake('local');
        [$employeeA, $employeeB] = $this->employees();
        Storage::disk('local')->put('contracts/other.pdf', '%PDF-1.4');

        $contract = EmployeeContract::query()->create([
            'company_id' => $employeeB->company_id,
            'employee_id' => $employeeB->id,
            'type' => 'CDI',
            'reference' => 'CDI-OTHER',
            'title' => 'Contrat CDI',
            'start_date' => now()->toDateString(),
            'salary' => 6000,
            'status' => 'generated',
            'pdf_path' => 'contracts/other.pdf',
            'generated_at' => now(),
        ]);

        $this->actingAs($employeeA->user)
            ->get(route('employee.contracts.download', $contract))
            ->assertForbidden();
    }

    public function test_employee_cannot_view_another_employee_generated_document(): void
    {
        [$employeeA, $employeeB] = $this->employees();

        $document = GeneratedDocument::query()->create([
            'company_id' => $employeeB->company_id,
            'employee_id' => $employeeB->id,
            'type' => 'ATTESTATION_TRAVAIL',
            'title' => 'Attestation',
            'file_path' => 'documents/attestation.pdf',
        ]);

        $this->actingAs($employeeA->user)
            ->get(route('employee.documents.show', $document))
            ->assertForbidden();
    }

    public function test_employee_can_create_document_request(): void
    {
        [$employee] = $this->employees();

        $this->actingAs($employee->user)
            ->post(route('employee.documents.requests.store'), [
                'type' => 'ATTESTATION_TRAVAIL',
                'title' => 'Attestation pour banque',
                'message' => 'Merci de préparer une attestation.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employee_document_requests', [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'type' => 'ATTESTATION_TRAVAIL',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_request_created',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_admin_can_approve_document_request(): void
    {
        [$employee] = $this->employees();
        $admin = $this->admin($employee->company_id);

        $request = EmployeeDocumentRequest::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'type' => 'CERTIFICAT_TRAVAIL',
            'title' => 'Certificat',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin);
        app(EmployeeDocumentRequestWorkflow::class)->approve($request, 'Approuvé.');

        $this->assertSame('approved', $request->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_request_approved',
            'employee_id' => $employee->id,
        ]);

        $this->get('/admin/employee-document-requests')->assertOk();
    }

    public function test_signed_contract_upload_changes_status_to_signed(): void
    {
        Storage::fake('local');
        [$employee] = $this->employees();
        Storage::disk('local')->put('companies/signed-contracts/signed.pdf', '%PDF-1.4');

        $contract = EmployeeContract::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'type' => 'CDI',
            'reference' => 'CDI-SIGNED',
            'title' => 'Contrat CDI',
            'start_date' => now()->toDateString(),
            'salary' => 6000,
            'status' => 'generated',
        ]);

        app(ContractSignatureService::class)->markSigned($contract, 'companies/signed-contracts/signed.pdf');

        $contract->refresh();
        $this->assertSame('signed', $contract->status);
        $this->assertNotNull($contract->signed_at);
        $this->assertSame('companies/signed-contracts/signed.pdf', $contract->signed_pdf_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'signed_contract_uploaded',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_audit_log_created_after_employee_payslip_pdf_download(): void
    {
        Storage::fake('local');
        [$employee] = $this->employees();
        $payslip = $this->payslip($employee);

        Storage::disk('local')->put('companies/1/employees/1/payslips/test.pdf', '%PDF-1.4');
        GeneratedDocument::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'payslip_id' => $payslip->id,
            'type' => 'payslip',
            'title' => 'Bulletin de paie',
            'file_path' => 'companies/1/employees/1/payslips/test.pdf',
        ]);

        $this->actingAs($employee->user)
            ->get(route('employee.payslips.download', $payslip))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payslip_pdf_downloaded_by_employee',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_employee_portal_main_pages_load_with_own_data(): void
    {
        [$employee] = $this->employees();
        $payslip = $this->payslip($employee);
        $contract = EmployeeContract::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'type' => 'CDI',
            'reference' => 'CDI-PORTAL',
            'title' => 'Contrat CDI',
            'start_date' => now()->toDateString(),
            'salary' => 6000,
            'status' => 'generated',
            'generated_at' => now(),
        ]);
        GeneratedDocument::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'type' => 'ATTESTATION_TRAVAIL',
            'title' => 'Attestation portail',
            'file_path' => 'documents/attestation-portail.pdf',
        ]);
        SupportTicket::query()->create([
            'company_id' => $employee->company_id,
            'user_id' => $employee->user_id,
            'employee_id' => $employee->id,
            'subject' => 'Question portail',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'open',
            'message' => 'Besoin aide',
        ]);

        $this->actingAs($employee->user)->get(route('employee.dashboard.show'))->assertOk()->assertSee($payslip->reference)->assertSee($contract->reference);
        $this->actingAs($employee->user)->get(route('employee.payslips'))->assertOk()->assertSee($payslip->reference);
        $this->actingAs($employee->user)->get(route('employee.contracts'))->assertOk()->assertSee($contract->reference);
        $this->actingAs($employee->user)->get(route('employee.documents'))->assertOk()->assertSee('Attestation portail');
        $this->actingAs($employee->user)->get(route('employee.support'))->assertOk()->assertSee('Question portail');
    }

    private function employees(): array
    {
        Role::findOrCreate('Employee');

        $company = Company::query()->create(['name' => 'SmartRH Test']);

        $userA = User::query()->create(['name' => 'Amina', 'email' => 'amina@test.local', 'password' => 'password']);
        $userA->assignRole('Employee');
        $employeeA = Employee::query()->create([
            'company_id' => $company->id,
            'user_id' => $userA->id,
            'employee_number' => 'EMP-A',
            'first_name' => 'Amina',
            'last_name' => 'Bennani',
            'hire_date' => now()->toDateString(),
            'base_salary' => 6000,
        ]);

        $userB = User::query()->create(['name' => 'Youssef', 'email' => 'youssef@test.local', 'password' => 'password']);
        $userB->assignRole('Employee');
        $employeeB = Employee::query()->create([
            'company_id' => $company->id,
            'user_id' => $userB->id,
            'employee_number' => 'EMP-B',
            'first_name' => 'Youssef',
            'last_name' => 'El Fassi',
            'hire_date' => now()->toDateString(),
            'base_salary' => 7000,
        ]);

        return [$employeeA->refresh(), $employeeB->refresh()];
    }

    private function payslip(Employee $employee): Payslip
    {
        $period = PayrollPeriod::query()->create([
            'company_id' => $employee->company_id,
            'name' => 'Juin 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
        ]);

        return Payslip::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'reference' => 'BP-' . $employee->employee_number,
            'status' => 'generated',
            'net_to_pay' => 6000,
            'net_pay' => 6000,
        ]);
    }

    private function admin(int $companyId): User
    {
        Role::findOrCreate('Company Owner');

        $admin = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@test.local',
            'password' => 'password',
            'company_id' => $companyId,
        ]);
        $admin->assignRole('Company Owner');

        return $admin;
    }
}
