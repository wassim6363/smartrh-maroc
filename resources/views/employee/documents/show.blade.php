@extends('employee.layout')

@section('title', 'Document RH')

@section('content')
    <h1 class="page-title">{{ $document->title }}</h1>
    <p class="subtitle">Référence {{ $document->reference ?: '-' }}</p>

    <div class="card">
        <div class="actions">
            <a class="link-btn" href="{{ route('employee.documents.download', $document) }}">Télécharger PDF</a>
            <a class="link-btn light" href="{{ route('employee.documents') }}">Retour aux documents</a>
        </div>
    </div>

    <div class="card">
        <h2>Informations</h2>
        <div class="details">
            <span class="muted">Employeur</span><span>{{ $document->company?->name }}</span>
            <span class="muted">Salarié</span><span>{{ $document->employee?->full_name }}</span>
            <span class="muted">Type</span><span>{{ $document->type }}</span>
            <span class="muted">Statut</span><span class="badge">{{ $document->status ?: 'generated' }}</span>
            <span class="muted">Date de génération</span><span>{{ $document->generated_at?->format('d/m/Y H:i') ?: $document->created_at?->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <div class="card document-body">
        <h2>Aperçu</h2>
        {!! $document->content_html ?: '<p>Aucun aperçu disponible. Le document PDF peut être téléchargé.</p>' !!}
    </div>
@endsection
