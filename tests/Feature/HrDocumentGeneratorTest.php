<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Services\Documents\HrDocumentGenerator;
use Barryvdh\DomPDF\PDF as BasePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HrDocumentGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_hr_document_types_generate_without_exception(): void
    {
        Storage::fake('local');
        [$company, $employee, $user] = $this->fixture();
        $this->fakePdfOutput(7);

        foreach (['STAGE', 'CDI', 'CDD', 'AVENANT', 'ATTESTATION_TRAVAIL', 'CERTIFICAT_TRAVAIL', 'SOLDE_TOUT_COMPTE'] as $type) {
            $document = app(HrDocumentGenerator::class)->generate($employee->refresh(), $type, $user);

            $this->assertSame($company->id, $document->company_id);
            $this->assertSame($employee->id, $document->employee_id);
            $this->assertSame($type, $document->type);
            Storage::disk('local')->assertExists($document->file_path);
        }

        $this->assertDatabaseCount('generated_documents', 7);
    }

    public function test_old_quick_aliases_are_still_supported(): void
    {
        Storage::fake('local');
        [, $employee, $user] = $this->fixture();
        $this->fakePdfOutput(7);

        foreach ([
            'attestation' => 'ATTESTATION_TRAVAIL',
            'certificat' => 'CERTIFICAT_TRAVAIL',
            'contract_cdi' => 'CDI',
            'contract_cdd' => 'CDD',
            'contract-stage' => 'STAGE',
            'solde' => 'SOLDE_TOUT_COMPTE',
            'avenant' => 'AVENANT',
        ] as $alias => $expectedType) {
            $document = app(HrDocumentGenerator::class)->generate($employee->refresh(), $alias, $user);

            $this->assertSame($expectedType, $document->type);
            Storage::disk('local')->assertExists($document->file_path);
        }
    }

    public function test_stage_document_receives_company_object_not_string(): void
    {
        Storage::fake('local');
        [$company, $employee, $user] = $this->fixture();
        $pdf = $this->fakePdfOutput();

        app(HrDocumentGenerator::class)->generate($employee, 'STAGE', $user);

        $this->assertSame('pdf.documents.contract-stage', $pdf->calls[0]['view']);
        $this->assertInstanceOf(Company::class, $pdf->calls[0]['data']['company']);
        $this->assertTrue($pdf->calls[0]['data']['company']->is($company));
        $this->assertInstanceOf(Employee::class, $pdf->calls[0]['data']['employee']);
        $this->assertTrue($pdf->calls[0]['data']['employee']->is($employee));
        $this->assertIsArray($pdf->calls[0]['data']['variables']);
        $this->assertSame('STAGE', $pdf->calls[0]['data']['documentType']);
    }

    public function test_solde_de_tout_compte_uses_safe_default_variables(): void
    {
        Storage::fake('local');
        [, $employee, $user] = $this->fixture();
        $this->fakePdfOutput();

        $document = app(HrDocumentGenerator::class)->generate($employee, 'SOLDE_TOUT_COMPTE', $user);

        $this->assertSame('-', data_get($document->metadata, 'variables.last_working_day'));
        $this->assertSame('0,00 MAD', data_get($document->metadata, 'variables.gross_amount'));
        $this->assertSame('0,00 MAD', data_get($document->metadata, 'variables.deductions_amount'));
        $this->assertSame('0,00 MAD', data_get($document->metadata, 'variables.net_amount'));
        $this->assertSame('-', data_get($document->metadata, 'variables.payment_method'));
        $this->assertSame('-', data_get($document->metadata, 'variables.reason_for_departure'));
    }

    public function test_all_document_templates_render_with_normal_and_missing_optional_fields(): void
    {
        [$company, $employee, $user] = $this->fixture();
        $sparseEmployee = new Employee([
            'company_id' => $company->id,
            'employee_number' => 'SPARSE',
            'first_name' => 'Sans',
            'last_name' => 'Option',
            'hire_date' => null,
            'base_salary' => 0,
        ]);
        $sparseEmployee->setRelation('company', $company);

        foreach (['attestation-travail', 'certificat-travail', 'contract-cdi', 'contract-cdd', 'contract-stage', 'avenant', 'solde-tout-compte', 'generic'] as $view) {
            $this->assertNotEmpty($this->renderDocumentView($view, $company, $employee, $user));
            $this->assertNotEmpty($this->renderDocumentView($view, $company, $sparseEmployee, $user));
        }
    }

    public function test_employee_table_document_actions_are_registered(): void
    {
        Role::findOrCreate('Company Owner');
        [, , $user] = $this->fixture();
        $user->assignRole('Company Owner');

        $this->actingAs($user)
            ->get('/admin/employees')
            ->assertOk()
            ->assertSee('Attestation')
            ->assertSee('Certificat')
            ->assertSee('CDI')
            ->assertSee('CDD')
            ->assertSee('Stage')
            ->assertSee('Solde')
            ->assertSee('Avenant');
    }

    public function test_employee_table_quick_document_actions_generate_documents_without_livewire_error(): void
    {
        Role::findOrCreate('Company Owner');
        [, $employee, $user] = $this->fixture();
        $user->assignRole('Company Owner');
        Storage::fake('local');
        $this->fakePdfOutput(7);

        $this->actingAs($user);

        foreach ([
            'attestation' => 'ATTESTATION_TRAVAIL',
            'certificat' => 'CERTIFICAT_TRAVAIL',
            'contractCdi' => 'CDI',
            'contractCdd' => 'CDD',
            'contractStage' => 'STAGE',
            'solde' => 'SOLDE_TOUT_COMPTE',
            'avenant' => 'AVENANT',
        ] as $action => $type) {
            Livewire::test(ListEmployees::class)
                ->callTableAction($action, $employee)
                ->assertHasNoTableActionErrors()
                ->assertNotified('Document généré avec succès');

            $this->assertDatabaseHas('generated_documents', [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'type' => $type,
                'status' => 'generated',
            ]);
        }

        $this->assertDatabaseCount('generated_documents', 7);
    }

    public function test_generated_document_belongs_to_employee_company(): void
    {
        Storage::fake('local');
        [$company, $employee, $user] = $this->fixture();
        $this->fakePdfOutput();

        $document = app(HrDocumentGenerator::class)->generate($employee, 'ATTESTATION_TRAVAIL', $user);

        $this->assertSame($company->id, $document->company_id);
        $this->assertSame($employee->id, $document->employee_id);
        $this->assertDatabaseHas('generated_documents', [
            'id' => $document->id,
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_generic_hr_document_types_are_supported(): void
    {
        Storage::fake('local');
        [, $employee, $user] = $this->fixture();
        $types = [
            'ATTESTATION_SALAIRE',
            'ATTESTATION_CONGE',
            'RECU_PAIEMENT',
            'DECISION_SANCTION',
            'LETTRE_DEMISSION',
            'LETTRE_LICENCIEMENT',
            'CONVOCATION_ENTRETIEN',
            'AUTORISATION_ABSENCE',
        ];
        $this->fakePdfOutput(count($types));

        foreach ($types as $type) {
            $document = app(HrDocumentGenerator::class)->generate($employee->refresh(), $type, $user);

            $this->assertSame($type, $document->type);
            $this->assertSame('pdf.documents.generic', data_get($document->metadata, 'template'));
            Storage::disk('local')->assertExists($document->file_path);
        }
    }

    private function renderDocumentView(string $view, Company $company, Employee $employee, ?User $user): string
    {
        return view('pdf.documents.' . $view, [
            'company' => $company,
            'employee' => $employee->loadMissing(['company', 'department', 'position']),
            'user' => $user,
            'city' => $company->city ?: 'Casablanca',
            'generatedAt' => now(),
            'documentType' => 'TEST',
            'documentTitle' => 'Document test',
            'variables' => [
                'last_working_day' => '-',
                'gross_amount' => '0,00 MAD',
                'deductions_amount' => '0,00 MAD',
                'net_amount' => '0,00 MAD',
                'payment_method' => '-',
                'reason_for_departure' => '-',
            ],
            'values' => [],
        ])->render();
    }

    private function fakePdfOutput(int $times = 1): BasePdf
    {
        $pdf = new class ($times) extends BasePdf {
            public array $calls = [];

            public function __construct(private int $remainingCalls)
            {
            }

            public function loadView(string $view, array $data = [], array $mergeData = [], ?string $encoding = null): self
            {
                if ($this->remainingCalls < 1) {
                    throw new \RuntimeException('Unexpected PDF rendering call.');
                }

                $this->remainingCalls--;
                $this->calls[] = [
                    'view' => $view,
                    'data' => $data,
                    'mergeData' => $mergeData,
                    'encoding' => $encoding,
                ];

                return $this;
            }

            public function setPaper(string|array $paper, string $orientation = 'portrait'): self
            {
                return $this;
            }

            public function output(array $options = []): string
            {
                return '%PDF-1.4 test';
            }
        };

        $this->app->instance('dompdf.wrapper', $pdf);

        return $pdf;
    }

    private function fixture(): array
    {
        $company = Company::query()->create([
            'name' => 'SmartRH Docs',
            'ice' => 'ICE-DOCS',
            'cnss_number' => 'CNSS-DOCS',
            'address' => 'Boulevard Test',
            'city' => 'Casablanca',
        ]);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'name' => 'Ressources Humaines',
        ]);

        $position = Position::query()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'title' => 'Responsable RH',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin Docs',
            'email' => 'admin-docs@test.local',
            'password' => 'password',
        ]);

        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employee_number' => 'DOC-001',
            'first_name' => 'Amina',
            'last_name' => 'Bennani',
            'cin' => 'BK123',
            'cnss_number' => 'CNSS-E',
            'hire_date' => '2026-01-01',
            'base_salary' => 7000,
            'status' => 'active',
        ]);

        return [$company, $employee->refresh(), $user];
    }
}
