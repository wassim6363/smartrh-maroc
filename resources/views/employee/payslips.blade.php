@extends('employee.layout')
@section('content')
<h1 class="page-title">Mes bulletins de paie</h1>
<table><tr><th>Référence</th><th>Période</th><th>Net à payer</th><th></th></tr>@forelse($payslips as $payslip)<tr><td>{{ $payslip->reference }}</td><td>{{ $payslip->payrollPeriod?->name }}</td><td>{{ number_format((float) $payslip->net_pay, 2, ',', ' ') }} MAD</td><td><a class="link-btn" href="{{ route('payslips.download',$payslip) }}">Télécharger</a></td></tr>@empty<tr><td colspan="4">Aucun bulletin disponible.</td></tr>@endforelse</table>{{ $payslips->links() }}
@endsection
