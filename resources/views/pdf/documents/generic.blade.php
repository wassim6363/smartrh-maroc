<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle ?? 'Document RH' }}</title>
    <style>
        @page { margin: 17mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11.4px; line-height: 1.68; }
        h1 { text-align: center; color: #0B2E33; margin: 28px 0 22px; font-size: 21px; text-transform: uppercase; }
        .head { border-bottom: 3px solid #0F766E; padding-bottom: 10px; margin-bottom: 20px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: top; }
        .brand-right { text-align: right; }
        .logo { height: 42px; width: auto; margin-bottom: 4px; }
        .gold-line { height: 2px; width: 84px; background: #D4A72C; margin-top: 5px; }
        .box { border: 1px solid #E7DED3; background: #FCFAF7; padding: 12px; margin: 18px 0; page-break-inside: avoid; }
        .muted { color: #5F6B7A; }
        .content { margin-top: 18px; }
        .signature { width: 100%; margin-top: 44px; border-collapse: collapse; page-break-inside: avoid; }
        .signature td { width: 50%; height: 66px; border: 1px solid #E7DED3; text-align: center; vertical-align: bottom; color: #5F6B7A; padding: 10px; }
        .footer { margin-top: 28px; color: #5F6B7A; font-size: 9.5px; border-top: 1px solid #E7DED3; padding-top: 8px; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/branding/smartrh-logo.png');
    $companyName = $company?->legal_name ?: ($company?->name ?? '-');
    $companyIce = $company?->ice ?? '-';
    $companyCnss = $company?->cnss_number ?? '-';
    $companyAddress = trim(($company?->address ?? '') . ' ' . ($company?->city ?? '')) ?: '-';
    $employeeName = $employee?->full_name ?? '-';
    $employeeCin = $employee?->cin ?? '-';
    $employeeCnss = $employee?->cnss_number ?? '-';
    $employeePosition = $employee?->position?->title ?? $employee?->job_title ?? '-';
    $employeeDepartment = $employee?->department?->name ?? $employee?->department_name ?? $employee?->department_label ?? '-';
    $employeeHireDate = optional($employee?->hire_date)->format('d/m/Y') ?? '-';
    $date = optional($generatedAt)->format('d/m/Y') ?? now()->format('d/m/Y');
    $title = $documentTitle ?? \App\Services\Documents\HrDocumentGenerator::labelFor($documentType ?? 'DOCUMENT');
    $text = $bodyText ?? null;
@endphp

<div class="head">
    <div class="brand">
        <div class="brand-left">
            @if(file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="SmartRH Maroc">
            @else
                <strong>SmartRH Maroc</strong>
            @endif
            <div class="gold-line"></div>
            <div class="muted">Référence: {{ $reference ?? ($document?->reference ?? '-') }}</div>
            <div class="muted">Généré le {{ $date }}</div>
        </div>
        <div class="brand-right">
            <strong>{{ $companyName }}</strong><br>
            ICE {{ $companyIce }} | CNSS {{ $companyCnss }}<br>
            {{ $companyAddress }}
        </div>
    </div>
</div>

<h1>{{ $title }}</h1>

<div class="box">
    <strong>Société:</strong> {{ $companyName }}<br>
    <strong>Salarié:</strong> {{ $employeeName }} - CIN {{ $employeeCin }} - CNSS {{ $employeeCnss }}<br>
    <strong>Poste:</strong> {{ $employeePosition }}<br>
    <strong>Département:</strong> {{ $employeeDepartment }}<br>
    <strong>Date d'embauche:</strong> {{ $employeeHireDate }}
</div>

<div class="content">
@if($text)
    {!! $text !!}
@else
    <p>Le présent document est établi par <strong>{{ $companyName }}</strong> au bénéfice de <strong>{{ $employeeName }}</strong>.</p>
@endif
</div>

@if(($documentType ?? null) === 'SOLDE_TOUT_COMPTE')
    <div class="box">
        <strong>Dernier jour travaillé:</strong> {{ data_get($variables ?? [], 'last_working_day', '-') }}<br>
        <strong>Montant brut:</strong> {{ data_get($variables ?? [], 'gross_amount', '0,00 MAD') }}<br>
        <strong>Retenues:</strong> {{ data_get($variables ?? [], 'deductions_amount', '0,00 MAD') }}<br>
        <strong>Net à payer:</strong> {{ data_get($variables ?? [], 'net_amount', '0,00 MAD') }}<br>
        <strong>Mode de paiement:</strong> {{ data_get($variables ?? [], 'payment_method', '-') }}<br>
        <strong>Motif du départ:</strong> {{ data_get($variables ?? [], 'reason_for_departure', '-') }}
    </div>
@endif

<p>Fait à {{ $city ?? ($company?->city ?? 'Casablanca') }}, le {{ $date }}.</p>

<table class="signature">
    <tr>
        <td>Signature salarié</td>
        <td>Signature / cachet employeur</td>
    </tr>
</table>

<div class="footer">
    Document généré par SmartRH Maroc. Modèle à valider juridiquement avant usage en production.
</div>
</body>
</html>
