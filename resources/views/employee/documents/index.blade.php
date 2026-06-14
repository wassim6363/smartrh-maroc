@extends('employee.layout')

@section('title', 'Mes documents RH')

@section('content')
    <h1 class="page-title">Mes documents RH</h1>
    <p class="subtitle">{{ $employee->company?->name }}</p>

    <div class="card">
        <h2>Nouvelle demande</h2>
        @if($documentRequestsEnabled)
            <form method="post" action="{{ route('employee.documents.requests.store') }}">
                @csrf
                <div class="grid">
                    <div>
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            @foreach($requestTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="muted">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="title">Titre</label>
                        <input id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')<p class="muted">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label>&nbsp;</label>
                        <button class="btn" type="submit">Envoyer la demande</button>
                    </div>
                </div>
                <label for="message">Message</label>
                <textarea id="message" name="message">{{ old('message') }}</textarea>
                @error('message')<p class="muted">{{ $message }}</p>@enderror
            </form>
        @else
            <p class="subtitle">Les demandes de documents ne sont pas incluses dans le plan actuel.</p>
        @endif
    </div>

    <div class="card">
        <h2>Documents disponibles</h2>
        <table>
            <thead>
            <tr>
                <th>Référence</th>
                <th>Type</th>
                <th>Titre</th>
                <th>Date de génération</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($documents as $document)
                <tr>
                    <td><a href="{{ route('employee.documents.show', $document) }}">{{ $document->reference ?: '-' }}</a></td>
                    <td>{{ $document->type }}</td>
                    <td>{{ $document->title }}</td>
                    <td>{{ $document->generated_at?->format('d/m/Y') ?: $document->created_at?->format('d/m/Y') }}</td>
                    <td><a class="link-btn" href="{{ route('employee.documents.download', $document) }}">Télécharger PDF</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun document disponible.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $documents->links() }}</div>
    </div>

    <div class="card">
        <h2>Mes demandes</h2>
        <table>
            <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Demandé le</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $request)
                <tr>
                    <td><a href="{{ route('employee.documents.requests.show', $request) }}">{{ $request->title }}</a></td>
                    <td>{{ $requestTypes[$request->type] ?? $request->type }}</td>
                    <td><span class="badge">{{ $request->status }}</span></td>
                    <td>{{ $request->requested_at?->format('d/m/Y H:i') }}</td>
                    <td><a class="link-btn light" href="{{ route('employee.documents.requests.show', $request) }}">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Aucune demande enregistrée.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $requests->links() }}</div>
    </div>
@endsection
