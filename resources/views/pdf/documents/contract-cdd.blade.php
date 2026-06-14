@include('pdf.documents.generic', [
    'documentTitle' => 'Contrat de travail CDD',
    'bodyText' => '<p>Le présent contrat à durée déterminée est conclu pour le poste de <strong>' . e($employee?->position?->title ?? $employee?->job_title ?? '-') . '</strong>. Les dates, motifs et conditions définitives doivent être complétés et validés par le service RH.</p><p>La rémunération mensuelle brute de base est fixée à <strong>' . e(number_format((float) ($employee?->base_salary ?? 0), 2, ',', ' ')) . ' MAD</strong>.</p>',
])
