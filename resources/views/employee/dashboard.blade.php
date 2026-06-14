@extends('employee.layout')

@section('title', 'Mon espace salarié')

@section('content')
    <h1 class="page-title">Mon espace salarié</h1>
    <p class="subtitle">Bienvenue {{ $employee->full_name }} · {{ $employee->company?->name }}</p>

    <div class="grid">
        <div class="card stat">
            <div class="stat-icon blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
            </div>
            <span class="muted">Bulletins disponibles</span>
            <strong>{{ $payslipsCount }}</strong>
        </div>
        <div class="card stat">
            <div class="stat-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <span class="muted">Contrats disponibles</span>
            <strong>{{ $contractsCount }}</strong>
        </div>
        <div class="card stat">
            <div class="stat-icon blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <span class="muted">Documents RH</span>
            <strong>{{ $documentsCount }}</strong>
        </div>
        <div class="card stat">
            <div class="stat-icon amber">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 12v4"/><path d="M12 20h0"/></svg>
            </div>
            <span class="muted">Tickets support ouverts</span>
            <strong>{{ $openSupportTicketsCount }}</strong>
        </div>
        <div class="card stat">
            <div class="stat-icon amber">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </div>
            <span class="muted">Demandes documents en attente</span>
            <strong>{{ $pendingRequestsCount }}</strong>
        </div>
    </div>

    <div class="card">
        <h2>Actions rapides</h2>
        <div class="actions">
            @if($latestPayslip)
                <a class="link-btn" href="{{ route('employee.payslips.download', $latestPayslip) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Télécharger dernier bulletin
                </a>
            @endif
            <a class="link-btn secondary" href="{{ route('employee.payslips') }}">Voir mes bulletins</a>
            <a class="link-btn secondary" href="{{ route('employee.contracts') }}">Voir mes contrats</a>
            <a class="link-btn secondary" href="{{ route('employee.documents') }}">Voir mes documents RH</a>
            <a class="link-btn secondary" href="{{ route('employee.support.create') }}">Créer un ticket support</a>
        </div>
    </div>

    <div class="grid two">
        <div class="card">
            <h2>Dernier bulletin de paie</h2>
            @if($latestPayslip)
                <div class="details">
                    <span class="muted">Référence</span><strong>{{ $latestPayslip->reference }}</strong>
                    <span class="muted">Période</span><span>{{ $latestPayslip->payrollPeriod?->name }}</span>
                    <span class="muted">Net à payer</span><span>{{ number_format((float) $latestPayslip->net_to_pay ?: (float) $latestPayslip->net_pay, 2, ',', ' ') }} MAD</span>
                    <span class="muted">Statut</span><span class="badge @if(in_array($latestPayslip->status, ['generated','validated','sent'])) green @else amber @endif">{{ $latestPayslip->status_label ?? $latestPayslip->status }}</span>
                </div>
                <p><a class="link-btn light sm" href="{{ route('employee.payslips.show', $latestPayslip) }}">Voir le détail</a></p>
            @else
                <p class="muted">Aucun bulletin disponible.</p>
            @endif
        </div>

        <div class="card">
            <h2>Dernier contrat</h2>
            @if($latestContract)
                <div class="details">
                    <span class="muted">Référence</span><strong>{{ $latestContract->reference }}</strong>
                    <span class="muted">Type</span><span>{{ $latestContract->type }}</span>
                    <span class="muted">Date génération</span><span>{{ $latestContract->generated_at?->format('d/m/Y') }}</span>
                    <span class="muted">Statut</span><span class="badge @if($latestContract->status === 'signed') green @elseif($latestContract->status === 'draft') amber @else blue @endif">{{ $latestContract->status_label ?? $latestContract->status }}</span>
                </div>
                <p><a class="link-btn light sm" href="{{ route('employee.contracts.show', $latestContract) }}">Voir le détail</a></p>
            @else
                <p class="muted">Aucun contrat disponible.</p>
            @endif
        </div>
    </div>

    <div class="grid two">
        <div class="card">
            <h2>Documents récents</h2>
            @forelse($recentDocuments as $document)
                <p style="margin:8px 0"><a href="{{ route('employee.documents.show', $document) }}" style="color:#0F766E;text-decoration:none;font-weight:600">{{ $document->title }}</a> <span class="muted">· {{ $document->created_at?->format('d/m/Y') }}</span></p>
            @empty
                <p class="muted">Aucun document RH disponible.</p>
            @endforelse
        </div>

        <div class="card">
            <h2>Demandes de documents</h2>
            <p class="muted" style="margin-bottom:8px">{{ $requestsCount }} demande(s) enregistrée(s).</p>
            @forelse($recentRequests as $request)
                <p style="margin:6px 0"><a href="{{ route('employee.documents.requests.show', $request) }}" style="color:#0F766E;text-decoration:none;font-weight:600">{{ $request->title }}</a> <span class="badge @if($request->status === 'approved') green @elseif($request->status === 'pending') amber @else blue @endif">{{ $request->status_label ?? $request->status }}</span></p>
            @empty
                <p class="muted">Aucune demande récente.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2>Support</h2>
        <p class="muted" style="margin-bottom:8px">{{ $openSupportTicketsCount }} ticket(s) ouvert(s).</p>
        @forelse($recentSupportTickets as $ticket)
            <p style="margin:6px 0"><a href="{{ route('employee.support.show', $ticket) }}" style="color:#0F766E;text-decoration:none;font-weight:600">{{ $ticket->subject }}</a> <span class="badge @if($ticket->status === 'resolved') green @elseif($ticket->status === 'open' || $ticket->status === 'pending') amber @else blue @endif">{{ $ticket->status_label ?? $ticket->status }}</span></p>
        @empty
            <p class="muted">Aucun ticket support ouvert.</p>
        @endforelse
    </div>
@endsection
