@extends('employee.layout')
@section('content')
<h1 class="page-title">Mes congés</h1>
<div class="card"><h2>Nouvelle demande</h2><form method="post" action="{{ route('employee.leave-requests.store') }}">@csrf<div class="grid"><div><label>Type</label><select name="leave_type_id">@foreach($leaveTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div><div><label>Début</label><input type="date" name="starts_at" required></div><div><label>Fin</label><input type="date" name="ends_at" required></div><div><label>Jours</label><input type="number" step="0.5" name="days" required></div></div><label>Motif</label><textarea name="reason"></textarea><p><button class="btn">Envoyer la demande</button></p></form></div>
<table><tr><th>Type</th><th>Début</th><th>Fin</th><th>Jours</th><th>Statut</th></tr>@forelse($leaveRequests as $leave)<tr><td>{{ $leave->leaveType?->name }}</td><td>{{ $leave->starts_at?->format('d/m/Y') }}</td><td>{{ $leave->ends_at?->format('d/m/Y') }}</td><td>{{ $leave->days }}</td><td>{{ $leave->status }}</td></tr>@empty<tr><td colspan="5">Aucune demande de congé.</td></tr>@endforelse</table>{{ $leaveRequests->links() }}
@endsection
