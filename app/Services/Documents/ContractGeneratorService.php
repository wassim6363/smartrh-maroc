<?php

namespace App\Services\Documents;

use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\GeneratedDocument;
use App\Services\Audit\AuditLogger;
use App\Services\Saas\SubscriptionLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractGeneratorService
{
    public function __construct(private readonly ContractPdfService $pdfService) {}

    public function generate(array $data): EmployeeContract
    {
        return DB::transaction(function () use ($data): EmployeeContract {
            $companyId = (int) $data['company_id'];
            $employee = Employee::query()->where('company_id', $companyId)->findOrFail((int) $data['employee_id']);
            app(SubscriptionLimitService::class)->assertCanGenerateContract($employee->company);
            $template = $this->template($data, $companyId);

            $type = $data['type'] ?? $template->type ?? 'CDI';
            $reference = $data['reference'] ?? $this->reference($type, $employee->id);
            $title = $data['title'] ?? $template->title ?? $template->name ?? $this->label($type);
            $content = $this->renderTemplate($template, $employee, [
                ...$data,
                'type' => $type,
                'reference' => $reference,
                'title' => $title,
            ]);

            $contract = EmployeeContract::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'contract_template_id' => $template->id,
                'type' => $type,
                'reference' => $reference,
                'title' => $title,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? $employee->base_salary,
                'job_title' => $data['job_title'] ?? $employee->position_label,
                'city' => $data['city'] ?? $employee->company?->city ?? 'Casablanca',
                'status' => 'generated',
                'content_html' => $content,
                'generated_at' => now(),
            ]);

            $path = $this->pdfService->generate($contract);

            GeneratedDocument::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'reference' => $reference,
                ],
                [
                    'employee_id' => $employee->id,
                    'documentable_type' => EmployeeContract::class,
                    'documentable_id' => $contract->id,
                    'type' => $type,
                    'title' => $title,
                    'content_html' => $content,
                    'file_path' => $path,
                    'pdf_path' => $path,
                    'status' => 'generated',
                    'generated_at' => now(),
                    'metadata' => [
                        'contract_id' => $contract->id,
                        'template_id' => $template->id,
                    ],
                ],
            );

            app(AuditLogger::class)->log('contract_generated', $contract, [], [], [
                'employee_id' => $employee->id,
                'reference' => $reference,
                'type' => $type,
            ]);
            app(SubscriptionLimitService::class)->incrementContractUsage($employee->company);

            return $contract->refresh()->load(['company', 'employee', 'contractTemplate']);
        });
    }

    public function renderTemplate(ContractTemplate $template, Employee $employee, array $data): string
    {
        $employee->loadMissing(['company', 'position']);
        $company = $employee->company;

        $salary = $data['salary'] ?? $employee->base_salary;
        $jobTitle = $data['job_title'] ?? $employee->position_label;

        $variables = [
            'company_name' => $company->legal_name ?: $company->name,
            'company_ice' => $company->ice,
            'company_rc' => $company->rc,
            'company_if' => $company->if_number ?: $company->if,
            'company_cnss' => $company->cnss_number,
            'company_address' => trim(($company->address ?: '') . ' ' . ($company->city ?: '')),
            'company_phone' => $company->phone,
            'company_email' => $company->email,
            'employee_name' => $employee->full_name,
            'employee_first_name' => $employee->first_name,
            'employee_last_name' => $employee->last_name,
            'employee_cin' => $employee->cin,
            'employee_cnss' => $employee->cnss_number,
            'employee_address' => $employee->address,
            'employee_job_title' => $jobTitle,
            'employee_contract_type' => $employee->contract_type,
            'employee_hire_date' => $employee->hire_date?->format('d/m/Y'),
            'employee_salary' => number_format((float) $employee->base_salary, 2, ',', ' '),
            'contract_reference' => $data['reference'] ?? '',
            'contract_type' => $data['type'] ?? $template->type,
            'contract_start_date' => filled($data['start_date'] ?? null) ? date('d/m/Y', strtotime($data['start_date'])) : '',
            'contract_end_date' => filled($data['end_date'] ?? null) ? date('d/m/Y', strtotime($data['end_date'])) : '',
            'contract_salary' => number_format((float) $salary, 2, ',', ' '),
            'contract_job_title' => $jobTitle,
            'city' => $data['city'] ?? $company->city ?? 'Casablanca',
            'today_date' => now()->format('d/m/Y'),
            'document_reference' => $data['document_reference'] ?? $data['reference'] ?? '',
            'last_working_day' => filled($data['last_working_day'] ?? null) ? date('d/m/Y', strtotime($data['last_working_day'])) : '',
            'gross_amount' => isset($data['gross_amount']) ? number_format((float) $data['gross_amount'], 2, ',', ' ') : '',
            'deductions_amount' => isset($data['deductions_amount']) ? number_format((float) $data['deductions_amount'], 2, ',', ' ') : '',
            'net_amount' => isset($data['net_amount']) ? number_format((float) $data['net_amount'], 2, ',', ' ') : '',
            'payment_method' => $data['payment_method'] ?? '',
            'reason_for_departure' => $data['reason_for_departure'] ?? '',
        ];

        foreach (($data['extra_variables'] ?? []) as $key => $value) {
            if (is_scalar($value)) {
                $variables[(string) $key] = (string) $value;
            }
        }

        $html = $template->content_html ?: $template->body ?: '';

        foreach ($variables as $key => $value) {
            $html = str_replace('{{' . $key . '}}', e((string) ($value ?? '')), $html);
        }

        return $html;
    }

    private function template(array $data, int $companyId): ContractTemplate
    {
        $query = ContractTemplate::query()
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->orWhereNull('company_id');
            });

        if (! empty($data['contract_template_id'])) {
            return (clone $query)->whereKey((int) $data['contract_template_id'])->firstOrFail();
        }

        return $query
            ->where('type', $data['type'] ?? 'CDI')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->latest('id')
            ->firstOrFail();
    }

    private function reference(string $type, int $employeeId): string
    {
        return Str::upper($type) . '-' . $employeeId . '-' . now()->format('YmdHis');
    }

    private function label(string $type): string
    {
        return match ($type) {
            'CDD' => 'Contrat de travail CDD',
            'FREELANCE' => 'Contrat de prestation freelance',
            'ANAPEC' => 'Contrat ANAPEC',
            'STAGE' => 'Convention de stage',
            'AVENANT' => 'Avenant au contrat',
            'ATTESTATION_TRAVAIL' => 'Attestation de travail',
            'CERTIFICAT_TRAVAIL' => 'Certificat de travail',
            'SOLDE_TOUT_COMPTE' => 'Solde de tout compte',
            'ATTESTATION_SALAIRE' => 'Attestation de salaire',
            'ATTESTATION_CONGE' => 'Attestation de congé',
            'RECU_PAIEMENT' => 'Reçu de paiement',
            'DECISION_SANCTION' => 'Décision de sanction',
            'LETTRE_DEMISSION' => 'Lettre de démission',
            'LETTRE_LICENCIEMENT' => 'Lettre de licenciement',
            'CONVOCATION_ENTRETIEN' => 'Convocation à entretien',
            'AUTORISATION_ABSENCE' => 'Autorisation d’absence',
            default => 'Contrat de travail CDI',
        };
    }
}
