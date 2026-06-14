@extends('employee.layout')

@section('title', 'Mes contrats')

@section('content')
    <h1 class="page-title">Mes contrats</h1>
    <p class="subtitle">{{ $employee->company?->name }}</p>

    <table>
        <thead>
        <tr>
            <th>Référence</th>
            <th>Type</th>
            <th>Statut</th>
            <th>Date de génération</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($contracts as $contract)
            <tr>
                <td><a href="{{ route('employee.contracts.show', $contract) }}">{{ $contract->reference }}</a></td>
                <td>{{ $contract->type }}</td>
                <td><span class="badge {{ $contract->status === 'signed' ? 'success' : '' }}">{{ $contract->status }}</span></td>
                <td>{{ $contract->generated_at?->format('d/m/Y') ?: $contract->created_at?->format('d/m/Y') }}</td>
                <td><a class="link-btn" href="{{ route('employee.contracts.download', $contract) }}">Télécharger PDF</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun contrat disponible.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $contracts->links() }}</div>
@endsection
