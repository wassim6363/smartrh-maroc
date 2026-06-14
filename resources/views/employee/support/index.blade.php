@extends('employee.layout')

@section('title', 'Support')

@section('content')
    <h1 class="page-title">Tickets support</h1>
    <p class="subtitle">{{ $employee->company?->name }}</p>

    <div class="actions">
        <a class="btn" href="{{ route('employee.support.create') }}">Créer un ticket</a>
    </div>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Sujet</th>
                <th>Catégorie</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Mis à jour</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tickets as $ticket)
                <tr>
                    <td><a href="{{ route('employee.support.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                    <td>{{ $ticket->category_label }}</td>
                    <td><span class="badge">{{ $ticket->priority_label }}</span></td>
                    <td><span class="badge">{{ $ticket->status_label }}</span></td>
                    <td>{{ $ticket->updated_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun ticket support.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $tickets->links() }}</div>
    </div>
@endsection
