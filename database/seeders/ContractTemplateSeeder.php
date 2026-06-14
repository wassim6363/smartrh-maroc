<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocumentRequest;
use App\Services\Documents\ContractGeneratorService;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['CDI', 'Contrat de travail CDI', '<p>Entre <strong>{{company_name}}</strong>, ICE {{company_ice}}, sise à {{company_address}}, représentée par son représentant légal, et <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, il est convenu un contrat de travail à durée indéterminée.</p><p>Le salarié occupe le poste de <strong>{{contract_job_title}}</strong> à compter du <strong>{{contract_start_date}}</strong>, avec une rémunération mensuelle brute de <strong>{{contract_salary}} MAD</strong>.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['CDD', 'Contrat de travail CDD', '<p>La société <strong>{{company_name}}</strong> engage <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, dans le cadre d’un contrat à durée déterminée.</p><p>Le contrat prend effet le <strong>{{contract_start_date}}</strong> et prend fin le <strong>{{contract_end_date}}</strong>. Poste: <strong>{{contract_job_title}}</strong>. Salaire mensuel brut: <strong>{{contract_salary}} MAD</strong>.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['FREELANCE', 'Contrat freelance / prestation', '<p>La société <strong>{{company_name}}</strong> confie à <strong>{{employee_name}}</strong> une mission de prestation de services.</p><p>La prestation débute le <strong>{{contract_start_date}}</strong>. Les honoraires convenus sont de <strong>{{contract_salary}} MAD</strong>, selon les modalités validées entre les parties.</p><p>Le prestataire agit en toute indépendance et s’engage à respecter la confidentialité des informations communiquées.</p>'],
            ['ANAPEC', 'Contrat ANAPEC', '<p>Le présent contrat ANAPEC est conclu entre <strong>{{company_name}}</strong> et <strong>{{employee_name}}</strong>, CIN {{employee_cin}}.</p><p>Le bénéficiaire est affecté au poste de <strong>{{contract_job_title}}</strong> à compter du <strong>{{contract_start_date}}</strong>. Les conditions du contrat doivent respecter les dispositions ANAPEC applicables.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['STAGE', 'Convention de stage', '<p>La société <strong>{{company_name}}</strong> accueille <strong>{{employee_name}}</strong> dans le cadre d’une convention de stage.</p><p>Le stage débute le <strong>{{contract_start_date}}</strong> et se termine le <strong>{{contract_end_date}}</strong>. Le stagiaire intervient au poste de <strong>{{contract_job_title}}</strong>.</p><p>Les missions confiées ont un objectif pédagogique et doivent être encadrées par un responsable désigné.</p>'],
            ['AVENANT', 'Avenant au contrat', '<p>Le présent avenant modifie le contrat liant <strong>{{company_name}}</strong> et <strong>{{employee_name}}</strong>.</p><p>À compter du <strong>{{contract_start_date}}</strong>, le salarié occupe le poste de <strong>{{contract_job_title}}</strong> avec une rémunération de <strong>{{contract_salary}} MAD</strong>.</p><p>Les autres clauses du contrat initial demeurent inchangées.</p>'],
            ['ATTESTATION_TRAVAIL', 'Attestation de travail', '<p>Nous soussignés, <strong>{{company_name}}</strong>, attestons que <strong>{{employee_name}}</strong>, titulaire de la CIN {{employee_cin}}, travaille au sein de notre société depuis le <strong>{{employee_hire_date}}</strong> en qualité de <strong>{{employee_job_title}}</strong>.</p><p>La présente attestation est délivrée à l’intéressé(e) pour servir et valoir ce que de droit.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['CERTIFICAT_TRAVAIL', 'Certificat de travail', '<p>Nous certifions que <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, a été employé(e) par <strong>{{company_name}}</strong> au poste de <strong>{{employee_job_title}}</strong>.</p><p>Ce certificat est établi à la demande de l’intéressé(e), sous réserve des vérifications administratives nécessaires.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['SOLDE_TOUT_COMPTE', 'Solde de tout compte', '<p>Référence: {{document_reference}}</p><p>La société <strong>{{company_name}}</strong> atteste avoir établi le solde de tout compte de <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, au titre de son départ le <strong>{{last_working_day}}</strong>.</p><p>Motif du départ: {{reason_for_departure}}.</p><p>Montant brut: <strong>{{gross_amount}} MAD</strong>. Retenues: <strong>{{deductions_amount}} MAD</strong>. Net à payer: <strong>{{net_amount}} MAD</strong>, réglé par {{payment_method}}.</p><p>Ce document doit être vérifié et signé par les parties conformément au droit marocain applicable.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['ATTESTATION_SALAIRE', 'Attestation de salaire', '<p>Nous attestons que <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, occupe le poste de <strong>{{employee_job_title}}</strong> au sein de <strong>{{company_name}}</strong>.</p><p>Sa rémunération mensuelle brute déclarée est de <strong>{{contract_salary}} MAD</strong>.</p><p>Attestation délivrée pour servir et valoir ce que de droit.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['ATTESTATION_CONGE', 'Attestation de congé', '<p>La société <strong>{{company_name}}</strong> atteste que <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, bénéficie d’une autorisation de congé selon les dates validées par l’administration RH.</p><p>Cette attestation est délivrée à la demande de l’intéressé(e).</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['RECU_PAIEMENT', 'Reçu de paiement', '<p>Reçu de paiement établi pour <strong>{{employee_name}}</strong>, CIN {{employee_cin}}.</p><p>Montant net réglé: <strong>{{net_amount}} MAD</strong>. Mode de paiement: {{payment_method}}.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['DECISION_SANCTION', 'Décision de sanction', '<p>La société <strong>{{company_name}}</strong> notifie à <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, une décision disciplinaire conformément au règlement intérieur et aux dispositions applicables.</p><p>Les faits, motifs et voies de recours doivent être complétés par le service RH.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['LETTRE_DEMISSION', 'Lettre de démission', '<p>Je soussigné(e) <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, occupant le poste de <strong>{{employee_job_title}}</strong>, informe <strong>{{company_name}}</strong> de ma démission.</p><p>La date de dernier jour travaillé prévue est le <strong>{{last_working_day}}</strong>, sous réserve du respect du préavis applicable.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['LETTRE_LICENCIEMENT', 'Lettre de licenciement', '<p>La société <strong>{{company_name}}</strong> notifie à <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, la rupture de son contrat de travail.</p><p>Le dernier jour travaillé est prévu le <strong>{{last_working_day}}</strong>. Les motifs et références de procédure doivent être complétés par le service RH.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['CONVOCATION_ENTRETIEN', 'Convocation à entretien', '<p><strong>{{employee_name}}</strong>, CIN {{employee_cin}}, est convoqué(e) à un entretien avec le service RH de <strong>{{company_name}}</strong>.</p><p>L’objet, la date, l’heure et le lieu de l’entretien doivent être complétés par le service RH.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
            ['AUTORISATION_ABSENCE', 'Autorisation d’absence', '<p>La société <strong>{{company_name}}</strong> autorise <strong>{{employee_name}}</strong>, CIN {{employee_cin}}, à s’absenter selon les modalités validées par le service RH.</p><p>Cette autorisation est délivrée sous réserve des justificatifs et règles internes applicables.</p><p>Fait à {{city}}, le {{today_date}}.</p>'],
        ];

        Company::query()->each(function (Company $company) use ($templates): void {
            foreach ($templates as [$type, $title, $content]) {
                ContractTemplate::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'type' => $type,
                        'language' => 'fr',
                        'is_default' => true,
                    ],
                    [
                        'title' => $title,
                        'name' => $title,
                        'contract_type' => strtolower($type),
                        'content_html' => $content,
                        'body' => strip_tags($content),
                        'is_active' => true,
                    ],
                );
            }

            $employee = $company->employees()->oldest()->first();
            if (! $employee) {
                return;
            }

            $reference = 'CDI-DEMO-' . $company->id . '-' . $employee->id;

            if (! EmployeeContract::query()->where('reference', $reference)->exists()) {
                app(ContractGeneratorService::class)->generate([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'type' => 'CDI',
                    'reference' => $reference,
                    'title' => 'Contrat de travail CDI',
                    'start_date' => $employee->hire_date?->toDateString() ?: now()->toDateString(),
                    'salary' => $employee->base_salary,
                    'job_title' => $employee->position_label ?: 'Salarié',
                    'city' => $company->city ?: 'Casablanca',
                ]);
            }

            EmployeeDocumentRequest::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'type' => 'ATTESTATION_TRAVAIL',
                    'title' => 'Demande attestation de travail',
                ],
                [
                    'message' => 'Demande créée pour la démonstration.',
                    'status' => 'pending',
                    'requested_at' => now(),
                ],
            );
        });
    }
}
