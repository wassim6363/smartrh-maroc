<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->title }}</title>
    <style>
        @page { margin: 15mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10.8px; line-height: 1.62; }
        h1 { color: #0B2E33; font-size: 21px; margin: 0 0 16px; text-align: center; text-transform: uppercase; }
        .header { border-bottom: 3px solid #0F766E; padding-bottom: 10px; margin-bottom: 22px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: top; }
        .brand-right { text-align: right; }
        .logo { height: 42px; width: auto; margin-bottom: 4px; }
        .gold-line { height: 2px; width: 84px; background: #D4A72C; margin-top: 5px; }
        .muted { color: #5F6B7A; font-size: 9px; }
        .box { border: 1px solid #E7DED3; background: #FCFAF7; padding: 10px; margin-bottom: 18px; page-break-inside: avoid; }
        .content { margin-top: 18px; }
        .content h2, .content h3 { color: #0F3D3E; }
        .signature { width: 100%; margin-top: 34px; border-collapse: collapse; page-break-inside: avoid; }
        .signature td { width: 50%; height: 68px; border: 1px solid #E7DED3; text-align: center; vertical-align: bottom; padding: 10px; color: #5F6B7A; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #E7DED3; color: #5F6B7A; font-size: 9px; }
    </style>
</head>
<body>
@php($logoPath = public_path('images/branding/smartrh-logo.png'))
<div class="header">
    <div class="brand">
        <div class="brand-left">
            @if(file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="SmartRH Maroc">
            @else
                <strong>SmartRH Maroc</strong>
            @endif
            <div class="gold-line"></div>
            <span class="muted">Référence: {{ $contract->reference }}</span>
        </div>
        <div class="brand-right">
            <strong>{{ $contract->company->legal_name ?: $contract->company->name }}</strong><br>
            ICE: {{ $contract->company->ice ?: '-' }} | RC: {{ $contract->company->rc ?: '-' }}<br>
            IF: {{ $contract->company->if_number ?: $contract->company->if ?: '-' }} | CNSS: {{ $contract->company->cnss_number ?: '-' }}<br>
            {{ $contract->company->address ?: '-' }} {{ $contract->company->city ?: '' }}
        </div>
    </div>
</div>

<h1>{{ $contract->title }}</h1>

<div class="box">
    <strong>Salarié:</strong> {{ $contract->employee->full_name }} |
    <strong>CIN:</strong> {{ $contract->employee->cin ?: '-' }} |
    <strong>Poste:</strong> {{ $contract->job_title ?: $contract->employee->position_label ?: '-' }}<br>
    <strong>Date début:</strong> {{ $contract->start_date?->format('d/m/Y') ?: '-' }} |
    <strong>Date fin:</strong> {{ $contract->end_date?->format('d/m/Y') ?: '-' }} |
    <strong>Salaire:</strong> {{ $contract->salary !== null ? number_format((float) $contract->salary, 2, ',', ' ') . ' MAD' : '-' }}
</div>

<div class="content">
    {!! $contract->content_html !!}
</div>

<table class="signature">
    <tr>
        <td>Signature salarié</td>
        <td>Signature / cachet employeur</td>
    </tr>
</table>

<div class="footer">
    Document généré par SmartRH Maroc. Ce modèle doit être vérifié par un juriste ou expert compétent avant utilisation officielle.
</div>
</body>
</html>
