<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin de paie {{ $payslip->reference }}</title>
    <style>
        @page { margin: 12mm 11mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; line-height: 1.38; }
        h1 { margin: 0; font-size: 21px; color: #0B2E33; }
        h2 { margin: 12px 0 6px; font-size: 11px; color: #0F3D3E; text-transform: uppercase; letter-spacing: .3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #E7DED3; padding: 5px 6px; vertical-align: top; }
        th { background: #F7F3EE; color: #0F3D3E; text-align: left; font-weight: bold; }
        .header { border-bottom: 3px solid #0F766E; padding-bottom: 9px; margin-bottom: 10px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: top; }
        .brand-left { width: 46%; }
        .brand-right { width: 54%; text-align: right; }
        .logo { height: 42px; width: auto; margin-bottom: 3px; }
        .gold-line { height: 2px; width: 84px; background: #D4A72C; margin-top: 5px; }
        .muted { color: #5F6B7A; }
        .right { text-align: right; }
        .center { text-align: center; }
        .identity td { width: 50%; }
        .two { display: table; width: 100%; table-layout: fixed; margin-top: 10px; }
        .two > div { display: table-cell; width: 50%; vertical-align: top; }
        .two > div:first-child { padding-right: 5px; }
        .two > div:last-child { padding-left: 5px; }
        .badge { display: inline-block; border-radius: 10px; padding: 2px 6px; font-size: 8.5px; font-weight: bold; }
        .badge.taxable { background: #E0F2FE; color: #0F766E; }
        .badge.exempt { background: #FEF3C7; color: #92400E; }
        .note { margin-top: 7px; padding: 7px 9px; border: 1px solid #E7DED3; background: #FCFAF7; color: #5F6B7A; font-size: 9px; }
        .net { margin-top: 10px; border: 0; }
        .net td { border: 0; background: #0F3D3E; color: #FFFFFF; padding: 10px 12px; font-size: 17px; font-weight: bold; }
        .net td:last-child { background: #0F766E; }
        .signature { margin-top: 18px; }
        .signature td { height: 46px; text-align: center; color: #5F6B7A; }
        .footer { margin-top: 10px; padding-top: 7px; border-top: 1px solid #E7DED3; font-size: 8.5px; color: #5F6B7A; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/branding/smartrh-logo.png');
    $grossTotal = (float) ($payslip->gross_total ?: $payslip->gross_salary);
    $taxableGross = (float) ($payslip->taxable_gross ?: $payslip->gross_salary);
    $salaryAfterContributions = (float) ($payslip->salary_after_contributions ?: $payslip->taxable_before_professional_expenses);
    $taxableNetIncome = (float) ($payslip->taxable_net_income ?: $payslip->taxable_income);
    $irBrut = (float) ($payslip->ir_brut ?: $payslip->ir_gross);
    $exemptAllowances = (float) ($payslip->exempt_allowances ?: $payslip->total_non_taxable_indemnities);
    $netToPay = (float) ($payslip->net_to_pay ?: $payslip->net_pay);
    $employeeDepartment = $payslip->employee->getRelationValue('department')?->name ?: $payslip->employee->getAttribute('department');
    $manualDeductions = $payslip->lines
        ->where('type', 'deduction')
        ->reject(fn ($line) => in_array($line->code, ['CNSS-SAL', 'AMO-SAL', 'IR'], true));
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ') . ' MAD';
@endphp

<div class="header">
    <div class="brand">
        <div class="brand-left">
            @if(file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="SmartRH Maroc">
            @else
                <strong>SmartRH Maroc</strong>
            @endif
            <div class="gold-line"></div>
        </div>
        <div class="brand-right">
            <h1>Bulletin de paie</h1>
            <div class="muted">Référence: {{ $payslip->reference }}</div>
            <div class="muted">Période: {{ $payslip->payrollPeriod->starts_at->format('d/m/Y') }} au {{ $payslip->payrollPeriod->ends_at->format('d/m/Y') }}</div>
            <div class="muted">Généré le {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<table class="identity">
    <tr>
        <td>
            <strong>Employeur</strong><br>
            {{ $payslip->company->legal_name ?: $payslip->company->name }}<br>
            ICE: {{ $payslip->company->ice ?: '-' }} | RC: {{ $payslip->company->rc ?: '-' }}<br>
            IF: {{ $payslip->company->if_number ?: $payslip->company->if ?: '-' }} | CNSS: {{ $payslip->company->cnss_number ?: '-' }}<br>
            {{ $payslip->company->address ?: '-' }} {{ $payslip->company->city ?: '' }}
        </td>
        <td>
            <strong>Salarié</strong><br>
            {{ $payslip->employee->full_name }}<br>
            CIN: {{ $payslip->employee->cin ?: '-' }} | CNSS: {{ $payslip->employee->cnss_number ?: '-' }}<br>
            Poste: {{ $payslip->employee->position?->title ?: $payslip->employee->job_title ?: '-' }}<br>
            Département: {{ $employeeDepartment ?: '-' }}<br>
            Embauche: {{ $payslip->employee->hire_date?->format('d/m/Y') ?: '-' }} | Contrat: {{ strtoupper($payslip->employee->contract_type ?: '-') }}
        </td>
    </tr>
</table>

<div class="two">
    <div>
        <h2>Gains</h2>
        <table>
            <thead><tr><th>Code</th><th>Libellé</th><th>Statut</th><th class="right">Montant</th></tr></thead>
            <tbody>
            @forelse ($payslip->lines->where('type', 'earning') as $line)
                <tr>
                    <td>{{ $line->code }}</td>
                    <td>{{ $line->label }}</td>
                    <td><span class="badge {{ $line->is_tax_exempt ? 'exempt' : 'taxable' }}">{{ $line->is_tax_exempt ? 'Exonérée' : 'Imposable' }}</span></td>
                    <td class="right">{{ $money($line->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center muted">Aucun gain.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div>
        <h2>Retenues</h2>
        <table>
            <thead><tr><th>Code</th><th>Libellé</th><th class="right">Montant</th></tr></thead>
            <tbody>
            @forelse ($manualDeductions as $line)
                <tr><td>{{ $line->code }}</td><td>{{ $line->label }}</td><td class="right">{{ $money($line->amount) }}</td></tr>
            @empty
                <tr><td colspan="3" class="center muted">Aucune retenue manuelle.</td></tr>
            @endforelse
            <tr><td>CNSS</td><td>CNSS salarié</td><td class="right">{{ $money($payslip->cnss_employee) }}</td></tr>
            <tr><td>AMO</td><td>AMO salarié</td><td class="right">{{ $money($payslip->amo_employee) }}</td></tr>
            <tr><td>IR</td><td>Impôt sur le revenu</td><td class="right">{{ $money($payslip->ir_net) }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

<h2>Informations fiscales</h2>
<table>
    <tbody>
    <tr><td>Salaire brut total</td><td class="right">{{ $money($grossTotal) }}</td><td>Brut imposable</td><td class="right">{{ $money($taxableGross) }}</td></tr>
    <tr><td>Base CNSS</td><td class="right">{{ $money($payslip->cnss_base) }}</td><td>Base AMO</td><td class="right">{{ $money($payslip->amo_base) }}</td></tr>
    <tr><td>CNSS salarié</td><td class="right">{{ $money($payslip->cnss_employee) }}</td><td>AMO salarié</td><td class="right">{{ $money($payslip->amo_employee) }}</td></tr>
    <tr><td>Salaire après cotisations</td><td class="right">{{ $money($salaryAfterContributions) }}</td><td>Frais professionnels</td><td class="right">{{ $money($payslip->professional_expenses) }}</td></tr>
    <tr><td>Revenu net imposable</td><td class="right">{{ $money($taxableNetIncome) }}</td><td>IR brut / IR net</td><td class="right">{{ $money($irBrut) }} / {{ $money($payslip->ir_net) }}</td></tr>
    <tr><td>Indemnités exonérées</td><td class="right">{{ $money($exemptAllowances) }}</td><td>Total retenues</td><td class="right">{{ $money($payslip->total_deductions) }}</td></tr>
    </tbody>
</table>

<div class="note">
    Le transport est inclus dans le brut imposable uniquement si la rubrique est configurée comme imposable.
    Pour un transport exonéré, la rubrique doit être marquée Exonérée et non soumise à CNSS/AMO/IR.
</div>

<table class="net">
    <tr><td>Net à payer</td><td class="right">{{ $money($netToPay) }}</td></tr>
</table>

<h2>Cumuls annuels</h2>
<table>
    <tbody>
    <tr><td>Cumul brut</td><td class="right">{{ $money($payslip->ytd_gross_salary) }}</td><td>Cumul imposable</td><td class="right">{{ $money($payslip->ytd_taxable_income) }}</td></tr>
    <tr><td>Cumul IR</td><td class="right">{{ $money($payslip->ytd_ir) }}</td><td>Cumul net à payer</td><td class="right">{{ $money($payslip->ytd_net_pay) }}</td></tr>
    <tr><td>Cumul CNSS</td><td class="right">{{ $money($payslip->ytd_cnss) }}</td><td>Cumul AMO</td><td class="right">{{ $money($payslip->ytd_amo) }}</td></tr>
    </tbody>
</table>

<table class="signature">
    <tr>
        <td>Signature salarié</td>
        <td>Signature / cachet employeur</td>
    </tr>
</table>

<div class="footer">
    Document généré par {{ config('smartrh.product_name', 'SmartRH Maroc') }}.
    {{ config('smartrh.payroll_disclaimer', 'Les paramètres légaux et les résultats de paie doivent être validés par un expert-comptable marocain avant usage en production.') }}
</div>
</body>
</html>
