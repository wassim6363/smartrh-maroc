@include('pdf.documents.generic', [
    'documentTitle' => 'Contrat de travail CDI',
    'bodyText' => '<p>Le présent contrat à durée indéterminée est conclu entre les parties ci-dessus. Le salarié occupe le poste de <strong>' . e($employee?->position?->title ?? $employee?->job_title ?? '-') . '</strong> à compter du <strong>' . e(optional($employee?->hire_date)->format('d/m/Y') ?? '-') . '</strong>.</p><p>La rémunération mensuelle brute de base est fixée à <strong>' . e(number_format((float) ($employee?->base_salary ?? 0), 2, ',', ' ')) . ' MAD</strong>.</p>',
])
