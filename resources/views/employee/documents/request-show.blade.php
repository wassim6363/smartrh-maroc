@extends('employee.layout')

@section('title', 'Demande de document')

@section('content')
    <h1 class="page-title">{{ $request->title }}</h1>
    <p class="subtitle">Demande de document RH</p>

    <div class="card">
        <div class="actions">
            <a class="link-btn light" href="{{ route('employee.documents') }}">Retour aux documents</a>
            @if($request->generatedDocument)
                <a class="link-btn" href="{{ route('employee.documents.show', $request->generatedDocument) }}">Voir le document généré</a>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Suivi</h2>
        <div class="details">
            <span class="muted">Type</span><span>{{ $requestTypes[$request->type] ?? $request->type }}</span>
            <span class="muted">Statut</span><span class="badge">{{ $request->status }}</span>
            <span class="muted">Demandé le</span><span>{{ $request->requested_at?->format('d/m/Y H:i') }}</span>
            <span class="muted">Traité le</span><span>{{ $request->processed_at?->format('d/m/Y H:i') ?: '-' }}</span>
            <span class="muted">Message</span><span>{{ $request->message ?: '-' }}</span>
            <span class="muted">Réponse RH</span><span>{{ $request->response_message ?: '-' }}</span>
        </div>
    </div>
@endsection
