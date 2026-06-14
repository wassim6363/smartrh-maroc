@extends('employee.layout')

@section('title', 'Créer un ticket support')

@section('content')
    <h1 class="page-title">Créer un ticket support</h1>
    <p class="subtitle">Décrivez votre demande. Le support SmartRH Maroc vous répondra depuis ce fil.</p>

    <div class="card">
        <form method="post" action="{{ route('employee.support.store') }}">
            @csrf
            <label for="subject">Sujet</label>
            <input id="subject" name="subject" value="{{ old('subject') }}" required>
            @error('subject')<p class="muted">{{ $message }}</p>@enderror

            <div class="grid two">
                <div>
                    <label for="category">Catégorie</label>
                    <select id="category" name="category" required>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="priority">Priorité</label>
                    <select id="priority" name="priority" required>
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label for="message">Message</label>
            <textarea id="message" name="message" required>{{ old('message') }}</textarea>
            @error('message')<p class="muted">{{ $message }}</p>@enderror

            <button class="btn" type="submit">Envoyer le ticket</button>
            <a class="link-btn light" href="{{ route('employee.support') }}">Annuler</a>
        </form>
    </div>
@endsection
