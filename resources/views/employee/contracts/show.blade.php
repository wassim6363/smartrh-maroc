@extends('employee.layout')

@section('title', 'Contrat')

@section('content')
    <h1 class="page-title">{{ $contract->title }}</h1>
    <p class="subtitle">Référence {{ $contract->reference }}</p>

    <div class="card">
        <div class="actions">
            <a class="link-btn" href="{{ route('employee.contracts.download', $contract) }}">Télécharger PDF</a>
            <a class="link-btn light" href="{{ route('employee.contracts') }}">Retour aux contrats</a>
        </div>
    </div>

    <div class="grid two">
        <div class="card">
            <h2>Employeur</h2>
            <div class="details">
                <span class="muted">Société</span><span>{{ $contract->company?->name }}</span>
                <span class="muted">ICE</span><span>{{ $contract->company?->ice ?: '-' }}</span>
                <span class="muted">Adresse</span><span>{{ $contract->company?->address ?: '-' }}</span>
            </div>
        </div>
        <div class="card">
            <h2>Salarié</h2>
            <div class="details">
                <span class="muted">Nom</span><span>{{ $contract->employee?->full_name }}</span>
                <span class="muted">Matricule</span><span>{{ $contract->employee?->employee_number }}</span>
                <span class="muted">Poste</span><span>{{ $contract->job_title ?: $contract->employee?->position_label }}</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Informations</h2>
        <div class="details">
            <span class="muted">Type</span><span>{{ $contract->type }}</span>
            <span class="muted">Statut</span><span class="badge {{ $contract->status === 'signed' ? 'success' : '' }}">{{ $contract->status }}</span>
            <span class="muted">Début</span><span>{{ $contract->start_date?->format('d/m/Y') }}</span>
            <span class="muted">Fin</span><span>{{ $contract->end_date?->format('d/m/Y') ?: '-' }}</span>
            <span class="muted">Salaire</span><span>{{ $contract->salary ? number_format((float) $contract->salary, 2, ',', ' ') . ' MAD' : '-' }}</span>
            <span class="muted">PDF signé</span><span>{{ $contract->signed_pdf_path ? 'Disponible' : 'Non disponible' }}</span>
        </div>
    </div>

    <div class="card document-body">
        <h2>Contenu</h2>
        {!! $contract->content_html ?: '<p>Aucun contenu disponible.</p>' !!}
    </div>
@endsection
