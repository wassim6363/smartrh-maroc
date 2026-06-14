<?php

namespace App\Services\Documents;

use App\Models\Company;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Notifications\SimpleFrenchNotification;
use App\Services\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HrDocumentGenerator
{
    private const TEMPLATES = [
        'CDI' => 'pdf.documents.contract-cdi',
        'CDD' => 'pdf.documents.contract-cdd',
        'STAGE' => 'pdf.documents.contract-stage',
        'AVENANT' => 'pdf.documents.avenant',
        'ATTESTATION_TRAVAIL' => 'pdf.documents.attestation-travail',
        'CERTIFICAT_TRAVAIL' => 'pdf.documents.certificat-travail',
        'SOLDE_TOUT_COMPTE' => 'pdf.documents.solde-tout-compte',
        'ATTESTATION_SALAIRE' => 'pdf.documents.generic',
        'ATTESTATION_CONGE' => 'pdf.documents.generic',
        'RECU_PAIEMENT' => 'pdf.documents.generic',
        'DECISION_SANCTION' => 'pdf.documents.generic',
        'LETTRE_DEMISSION' => 'pdf.documents.generic',
        'LETTRE_LICENCIEMENT' => 'pdf.documents.generic',
        'CONVOCATION_ENTRETIEN' => 'pdf.documents.generic',
        'AUTORISATION_ABSENCE' => 'pdf.documents.generic',
    ];

    private const ALIASES = [
        'attestation' => 'ATTESTATION_TRAVAIL',
        'attestation_travail' => 'ATTESTATION_TRAVAIL',
        'certificat' => 'CERTIFICAT_TRAVAIL',
        'certificat_travail' => 'CERTIFICAT_TRAVAIL',
        'contract_cdi' => 'CDI',
        'contract_cdd' => 'CDD',
        'contract_stage' => 'STAGE',
        'solde' => 'SOLDE_TOUT_COMPTE',
        'solde_tout_compte' => 'SOLDE_TOUT_COMPTE',
        'avenant' => 'AVENANT',
    ];

    public function generate(Employee $employee, string $type, ?User $user = null, array $variables = []): GeneratedDocument
    {
        $type = $this->normalizeType($type);
        abort_unless(isset(self::TEMPLATES[$type]), 422, 'Document type unsupported.');

        $employee->loadMissing(['company', 'department', 'position']);
        $company = $employee->company;
        abort_unless($company instanceof Company, 422, 'Entreprise introuvable pour ce salarié.');

        $generatedAt = now();
        $variables = $this->defaultVariables($variables);
        $title = $this->title($type, $employee);
        $filename = Str::slug($title) . '-' . $generatedAt->format('YmdHis') . '.pdf';
        $path = "companies/{$employee->company_id}/employees/{$employee->id}/documents/{$filename}";

        $viewData = [
            'company' => $company,
            'employee' => $employee,
            'user' => $user,
            'city' => $company->city ?: config('smartrh.default_city', 'Casablanca'),
            'generatedAt' => $generatedAt,
            'documentType' => $type,
            'documentTitle' => $title,
            'variables' => $variables,
            'values' => $variables,
        ];

        $view = view()->exists(self::TEMPLATES[$type]) ? self::TEMPLATES[$type] : 'pdf.documents.generic';

        $pdf = Pdf::loadView($view, $viewData)->setPaper('a4');
        Storage::disk(config('filesystems.private_disk'))->put($path, $pdf->output());

        $document = GeneratedDocument::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'generated_by' => $user?->id,
            'type' => $type,
            'title' => $title,
            'file_path' => $path,
            'pdf_path' => $path,
            'status' => 'generated',
            'generated_at' => $generatedAt,
            'metadata' => [
                'template' => $view,
                'generated_at' => $generatedAt->toIso8601String(),
                'variables' => $variables,
            ],
        ]);

        app(AuditLogger::class)->log('document_generated', $document, [], [
            'type' => $type,
            'employee_id' => $employee->id,
        ]);

        $employee->user?->notify(new SimpleFrenchNotification(
            'Document RH généré',
            'Un nouveau document RH est disponible dans votre portail salarié: ' . $title,
        ));

        return $document;
    }

    public static function supportedTypes(): array
    {
        return array_keys(self::TEMPLATES);
    }

    public static function templateFor(string $type): string
    {
        $type = self::normalizeDocumentType($type);

        return self::TEMPLATES[$type] ?? 'pdf.documents.generic';
    }

    public static function labelFor(string $type): string
    {
        return match (self::normalizeDocumentType($type)) {
            'CDI' => 'Contrat de travail CDI',
            'CDD' => 'Contrat de travail CDD',
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
            default => 'Document RH',
        };
    }

    private function normalizeType(string $type): string
    {
        return self::normalizeDocumentType($type);
    }

    private static function normalizeDocumentType(string $type): string
    {
        $aliasKey = (string) Str::of($type)->trim()->lower()->replace(['-', ' '], '_');

        return self::ALIASES[$aliasKey]
            ?? (string) Str::of($type)->trim()->upper()->replace(['-', ' '], '_');
    }

    private function defaultVariables(array $variables): array
    {
        return [
            'last_working_day' => $variables['last_working_day'] ?? '-',
            'gross_amount' => $variables['gross_amount'] ?? '0,00 MAD',
            'deductions_amount' => $variables['deductions_amount'] ?? '0,00 MAD',
            'net_amount' => $variables['net_amount'] ?? '0,00 MAD',
            'payment_method' => $variables['payment_method'] ?? '-',
            'reason_for_departure' => $variables['reason_for_departure'] ?? '-',
            ...$variables,
        ];
    }

    private function title(string $type, Employee $employee): string
    {
        return self::labelFor($type) . ' - ' . $employee->full_name;
    }
}
