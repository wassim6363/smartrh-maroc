@extends('employee.layout')

@section('title', 'Mes bulletins de paie')

@section('content')
    <h1 class="page-title">Mes bulletins de paie</h1>
    <p class="subtitle">{{ $employee->company?->name }}</p>

    <table>
        <thead>
        <tr>
            <th>Référence</th>
            <th>Période</th>
            <th>Statut</th>
            <th>Net à payer</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($payslips as $payslip)
            <tr>
                <td><a href="{{ route('employee.payslips.show', $payslip) }}">{{ $payslip->reference }}</a></td>
                <td>{{ $payslip->payrollPeriod?->name }}</td>
                <td><span class="badge">{{ $payslip->status }}</span></td>
                <td>{{ number_format((float) $payslip->net_to_pay ?: (float) $payslip->net_pay, 2, ',', ' ') }} MAD</td>
                <td><a class="link-btn" href="{{ route('employee.payslips.download', $payslip) }}">Télécharger PDF</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun bulletin disponible.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $payslips->links() }}</div>
@endsection
