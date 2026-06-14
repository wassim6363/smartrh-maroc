@extends('employee.layout')

@section('title', $ticket->subject)

@section('content')
    <h1 class="page-title">{{ $ticket->subject }}</h1>
    <p class="subtitle">{{ $ticket->category_label }} · {{ $ticket->priority_label }} · {{ $ticket->status_label }}</p>

    <div class="card">
        <h2>Message initial</h2>
        <p>{{ $ticket->message }}</p>
    </div>

    <div class="card">
        <h2>Réponses</h2>
        @forelse($publicReplies as $reply)
            <div class="card">
                <p>{{ $reply->message }}</p>
                <p class="muted">
                    {{ $reply->employee?->full_name ?: ($reply->user?->name ?: 'Support SmartRH Maroc') }}
                    · {{ $reply->created_at?->format('d/m/Y H:i') }}
                </p>
            </div>
        @empty
            <p class="muted">Aucune réponse pour le moment.</p>
        @endforelse
    </div>

    @if(! in_array($ticket->status, ['resolved', 'closed'], true))
        <div class="card">
            <h2>Ajouter une réponse</h2>
            <form method="post" action="{{ route('employee.support.reply', $ticket) }}">
                @csrf
                <textarea name="message" required>{{ old('message') }}</textarea>
                @error('message')<p class="muted">{{ $message }}</p>@enderror
                <button class="btn" type="submit">Envoyer la réponse</button>
            </form>
        </div>
    @else
        <div class="card">
            <p class="muted">Ce ticket est fermé ou résolu. Vous pouvez créer un nouveau ticket si nécessaire.</p>
        </div>
    @endif
@endsection
