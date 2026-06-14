@include('pdf.documents.generic', [
    'documentTitle' => 'Convention de stage',
    'bodyText' => '<p>La présente convention encadre un stage au poste de <strong>' . e($employee?->position?->title ?? $employee?->job_title ?? '-') . '</strong>, au sein du département <strong>' . e($employee?->department?->name ?? $employee?->department_label ?? '-') . '</strong>.</p><p>Les conditions pédagogiques, l’encadrement et la durée du stage doivent être complétés selon les documents officiels applicables.</p>',
])
