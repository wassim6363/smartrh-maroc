<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;
use App\Services\Documents\ContractGeneratorService;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_replace_variables_correctly(): void
    {
        [$company, $employee, $template] = $this->fixture('CDI');

        $html = app(ContractGeneratorService::class)->renderTemplate($template, $employee, [
            'type' => 'CDI',
            'reference' => 'CDI-TEST',
            'start_date' => '2026-06-01',
            'salary' => 7000,
            'job_title' => 'Responsable RH',
            'city' => 'Casablanca',
        ]);

        $this->assertStringContainsString($company->name, $html);
        $this->assertStringContainsString($employee->full_name, $html);
        $this->assertStringContainsString('CDI-TEST', $html);
        $this->assertStringContainsString('Responsable RH', $html);
    }

    public function test_can_generate_cdi_contract(): void
    {
        [$company, $employee, $template] = $this->fixture('CDI');

        $contract = app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $template, 'CDI'));

        $this->assertInstanceOf(EmployeeContract::class, $contract);
        $this->assertSame('CDI', $contract->type);
        $this->assertSame($employee->id, $contract->employee_id);
        $this->assertNotNull($contract->pdf_path);
    }

    public function test_can_generate_attestation_de_travail(): void
    {
        [$company, $employee, $template] = $this->fixture('ATTESTATION_TRAVAIL');

        $contract = app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $template, 'ATTESTATION_TRAVAIL'));

        $this->assertSame('ATTESTATION_TRAVAIL', $contract->type);
        $this->assertStringContainsString('attestons', $contract->content_html);
    }

    public function test_can_create_pdf_file(): void
    {
        [$company, $employee, $template] = $this->fixture('CDI');

        $contract = app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $template, 'CDI'));

        Storage::disk('local')->assertExists($contract->pdf_path);
    }

    public function test_cannot_use_employee_from_another_company(): void
    {
        [$company, , $template] = $this->fixture('CDI');
        $otherCompany = Company::query()->create(['name' => 'Other']);
        $otherEmployee = Employee::query()->create([
            'company_id' => $otherCompany->id,
            'employee_number' => 'O-001',
            'first_name' => 'Other',
            'last_name' => 'Employee',
            'hire_date' => '2026-01-01',
            'base_salary' => 5000,
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(ContractGeneratorService::class)->generate($this->payload($company, $otherEmployee, $template, 'CDI'));
    }

    public function test_cannot_use_template_from_another_company(): void
    {
        [$company, $employee] = $this->fixture('CDI');
        $otherCompany = Company::query()->create(['name' => 'Other']);
        $otherTemplate = $this->template($otherCompany, 'CDI');

        $this->expectException(ModelNotFoundException::class);

        app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $otherTemplate, 'CDI'));
    }

    public function test_filament_contract_resources_load_and_contract_pdf_downloads(): void
    {
        [$company, $employee, $template] = $this->fixture('CDI');
        Role::query()->create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = User::query()->create(['name' => 'Admin', 'email' => 'admin-contracts@test.local', 'password' => 'password']);
        $admin->assignRole('Super Admin');

        $contract = app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $template, 'CDI'));

        $this->actingAs($admin)->get('/admin/contract-templates')->assertOk();
        $this->actingAs($admin)->get('/admin/employee-contracts')->assertOk();
        $this->actingAs($admin)->get(route('contracts.download', $contract))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_all_required_contract_and_document_templates_are_seeded(): void
    {
        [$company] = $this->fixture('CDI');

        $this->seed(ContractTemplateSeeder::class);

        $required = [
            'CDI',
            'CDD',
            'FREELANCE',
            'ANAPEC',
            'STAGE',
            'AVENANT',
            'ATTESTATION_TRAVAIL',
            'CERTIFICAT_TRAVAIL',
            'SOLDE_TOUT_COMPTE',
            'ATTESTATION_SALAIRE',
            'ATTESTATION_CONGE',
            'RECU_PAIEMENT',
            'DECISION_SANCTION',
            'LETTRE_DEMISSION',
            'LETTRE_LICENCIEMENT',
            'CONVOCATION_ENTRETIEN',
            'AUTORISATION_ABSENCE',
        ];

        foreach ($required as $type) {
            $this->assertDatabaseHas('contract_templates', [
                'company_id' => $company->id,
                'type' => $type,
                'is_active' => true,
            ]);
        }
    }

    public function test_can_generate_solde_de_tout_compte_pdf(): void
    {
        [$company, $employee, $template] = $this->fixture('SOLDE_TOUT_COMPTE');
        $template->update([
            'title' => 'Solde de tout compte',
            'content_html' => '<p>{{employee_name}} - net {{net_amount}} MAD - depart {{last_working_day}} - {{payment_method}} - {{reason_for_departure}}</p>',
        ]);

        $contract = app(ContractGeneratorService::class)->generate([
            ...$this->payload($company, $employee, $template, 'SOLDE_TOUT_COMPTE'),
            'last_working_day' => '2026-06-30',
            'gross_amount' => 10000,
            'deductions_amount' => 1200,
            'net_amount' => 8800,
            'payment_method' => 'virement',
            'reason_for_departure' => 'Fin de contrat',
        ]);

        $this->assertSame('SOLDE_TOUT_COMPTE', $contract->type);
        $this->assertStringContainsString('8 800,00 MAD', $contract->content_html);
        Storage::disk('local')->assertExists($contract->pdf_path);
    }

    public function test_can_generate_attestation_de_salaire_pdf(): void
    {
        [$company, $employee, $template] = $this->fixture('ATTESTATION_SALAIRE');
        $template->update([
            'title' => 'Attestation de salaire',
            'content_html' => '<p>Attestation salaire {{employee_name}} {{contract_salary}} MAD</p>',
        ]);

        $contract = app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $template, 'ATTESTATION_SALAIRE'));

        $this->assertSame('ATTESTATION_SALAIRE', $contract->type);
        $this->assertSame($company->id, $contract->company_id);
        $this->assertSame($employee->id, $contract->employee_id);
        Storage::disk('local')->assertExists($contract->pdf_path);
    }

    public function test_can_generate_certificat_de_travail_pdf(): void
    {
        [$company, $employee, $template] = $this->fixture('CERTIFICAT_TRAVAIL');
        $template->update([
            'title' => 'Certificat de travail',
            'content_html' => '<p>Certificat {{employee_name}} {{company_name}}</p>',
        ]);

        $contract = app(ContractGeneratorService::class)->generate($this->payload($company, $employee, $template, 'CERTIFICAT_TRAVAIL'));

        $this->assertSame('CERTIFICAT_TRAVAIL', $contract->type);
        $this->assertSame($company->id, $contract->company_id);
        $this->assertSame($employee->id, $contract->employee_id);
        Storage::disk('local')->assertExists($contract->pdf_path);
    }

    private function fixture(string $type): array
    {
        $company = Company::query()->create([
            'name' => 'SmartRH Test',
            'ice' => '001',
            'rc' => 'RC1',
            'if' => 'IF1',
            'cnss_number' => 'CNSS1',
            'address' => 'Casablanca',
            'city' => 'Casablanca',
            'phone' => '+212',
            'email' => 'contact@test.local',
        ]);

        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'employee_number' => 'EMP-001',
            'first_name' => 'Amina',
            'last_name' => 'Bennani',
            'cin' => 'BK123',
            'cnss_number' => 'CNSS-E',
            'hire_date' => '2026-01-01',
            'base_salary' => 7000,
            'contract_type' => 'cdi',
        ]);

        return [$company, $employee, $this->template($company, $type)];
    }

    private function template(Company $company, string $type): ContractTemplate
    {
        return ContractTemplate::query()->create([
            'company_id' => $company->id,
            'type' => $type,
            'title' => $type === 'ATTESTATION_TRAVAIL' ? 'Attestation de travail' : 'Contrat CDI',
            'name' => $type,
            'language' => 'fr',
            'content_html' => $type === 'ATTESTATION_TRAVAIL'
                ? '<p>Nous attestons que {{employee_name}} travaille chez {{company_name}}. Réf {{contract_reference}}.</p>'
                : '<p>{{company_name}} embauche {{employee_name}} comme {{contract_job_title}} sous la référence {{contract_reference}}.</p>',
            'body' => 'Template',
            'contract_type' => strtolower($type),
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function payload(Company $company, Employee $employee, ContractTemplate $template, string $type): array
    {
        return [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'contract_template_id' => $template->id,
            'type' => $type,
            'reference' => $type . '-TEST-' . $employee->id,
            'title' => $template->title,
            'start_date' => '2026-06-01',
            'end_date' => $type === 'CDI' ? null : '2026-12-31',
            'salary' => 7000,
            'job_title' => 'Responsable RH',
            'city' => 'Casablanca',
        ];
    }
}
