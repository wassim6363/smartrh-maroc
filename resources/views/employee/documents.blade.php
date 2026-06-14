@extends('employee.layout')
@section('content')
<h1 class="page-title">Mes documents RH</h1>
<table><tr><th>Titre</th><th>Type</th><th>Date</th><th></th></tr>@forelse($documents as $document)<tr><td>{{ $document->title }}</td><td>{{ $document->type }}</td><td>{{ $document->created_at?->format('d/m/Y') }}</td><td><a class="link-btn" href="{{ route('documents.download',$document) }}">Télécharger</a></td></tr>@empty<tr><td colspan="4">Aucun document disponible.</td></tr>@endforelse</table>{{ $documents->links() }}
@endsection
