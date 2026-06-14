@extends('employee.layout')

@section('title', 'Bulletin de paie')

@section('content')
    <h1 class="page-title">Bulletin de paie</h1>
    <p class="subtitle">Référence {{ $payslip->reference }}</p>

    <div class="card">
        <div class="actions">
            <a class="link-btn" href="{{ route('employee.payslips.download', $payslip) }}">Télécharger PDF</a>
            <a class="link-btn light" href="{{ route('employee.payslips') }}">Retour aux bulletins</a>
        </div>
    </div>

    <div class="grid two">
        <div class="card">
            <h2>Employeur</h2>
            <div class="details">
                <span class="muted">Société</span><span>{{ $payslip->company?->name }}</span>
                <span class="muted">ICE</span><span>{{ $payslip->company?->ice ?: '-' }}</span>
                <span class="muted">CNSS</span><span>{{ $payslip->company?->cnss_number ?: '-' }}</span>
            </div>
        </div>
        <div class="card">
            <h2>Salarié</h2>
            <div class="details">
                <span class="muted">Nom</span><span>{{ $payslip->employee?->full_name }}</span>
                <span class="muted">Matricule</span><span>{{ $payslip->employee?->employee_number }}</span>
                <span class="muted">Poste</span><span>{{ $payslip->employee?->position_label ?: '-' }}</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Synthèse</h2>
        <div class="details">
            <span class="muted">Période</span><span>{{ $payslip->payrollPeriod?->name }}</span>
            <span class="muted">Salaire brut</span><span>{{ number_format((float) $payslip->gross_total ?: (float) $payslip->gross_salary, 2, ',', ' ') }} MAD</span>
            <span class="muted">Brut imposable</span><span>{{ number_format((float) $payslip->taxable_gross ?: (float) $payslip->taxable_salary, 2, ',', ' ') }} MAD</span>
            <span class="muted">CNSS salarié</span><span>{{ number_format((float) $payslip->cnss_employee, 2, ',', ' ') }} MAD</span>
            <span class="muted">AMO salarié</span><span>{{ number_format((float) $payslip->amo_employee, 2, ',', ' ') }} MAD</span>
            <span class="muted">IR</span><span>{{ number_format((float) $payslip->ir_net, 2, ',', ' ') }} MAD</span>
            <span class="muted">Net à payer</span><strong>{{ number_format((float) $payslip->net_to_pay ?: (float) $payslip->net_pay, 2, ',', ' ') }} MAD</strong>
        </div>
    </div>

    <div class="card">
        <h2>Lignes du bulletin</h2>
        <table>
            <thead><tr><th>Type</th><th>Libellé</th><th>Base</th><th>Taux</th><th>Montant</th></tr></thead>
            <tbody>
            @forelse($payslip->lines as $line)
                <tr>
                    <td>{{ $line->type }}</td>
                    <td>{{ $line->label }}</td>
                    <td>{{ number_format((float) $line->base, 2, ',', ' ') }}</td>
                    <td>{{ $line->rate ? number_format((float) $line->rate, 2, ',', ' ') . '%' : '-' }}</td>
                    <td>{{ number_format((float) $line->amount, 2, ',', ' ') }} MAD</td>
                </tr>
            @empty
                <tr><td colspan="5">Aucune ligne disponible.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
