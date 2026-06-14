@include('pdf.documents.generic', [
    'documentTitle' => 'Certificat de travail',
    'bodyText' => '<p>Nous certifions que <strong>' . e($employee?->full_name ?? '-') . '</strong>, CIN <strong>' . e($employee?->cin ?? '-') . '</strong>, a été employé(e) par <strong>' . e($company?->name ?? '-') . '</strong> au poste de <strong>' . e($employee?->position?->title ?? $employee?->job_title ?? '-') . '</strong>.</p><p>Ce certificat est établi à la demande de l’intéressé(e), sous réserve des vérifications administratives nécessaires.</p>',
])
