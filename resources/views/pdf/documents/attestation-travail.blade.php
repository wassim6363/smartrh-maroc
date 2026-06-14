@include('pdf.documents.generic', [
    'documentTitle' => 'Attestation de travail',
    'bodyText' => '<p>Nous soussignés, <strong>' . e($company?->name ?? '-') . '</strong>, attestons que <strong>' . e($employee?->full_name ?? '-') . '</strong>, titulaire de la CIN <strong>' . e($employee?->cin ?? '-') . '</strong>, travaille au sein de notre société depuis le <strong>' . e(optional($employee?->hire_date)->format('d/m/Y') ?? '-') . '</strong> en qualité de <strong>' . e($employee?->position?->title ?? $employee?->job_title ?? '-') . '</strong>.</p><p>La présente attestation est délivrée à l’intéressé(e) pour servir et valoir ce que de droit.</p>',
])
