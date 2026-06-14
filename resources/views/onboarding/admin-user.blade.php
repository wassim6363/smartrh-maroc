<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer l'administrateur - SmartRH Maroc</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:#F7F3EE;color:#172033}.wrap{max-width:720px;margin:48px auto;padding:0 22px}.brand{display:inline-flex;align-items:center;margin-bottom:18px}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.card{background:white;border:1px solid #E7DED3;border-radius:14px;padding:28px;box-shadow:0 10px 24px rgba(23,32,51,.055)}h1{color:#0F3D3E;margin-top:0}label{display:block;font-weight:750;margin:12px 0 6px}input{width:100%;min-height:42px;border:1px solid #E7DED3;border-radius:10px;padding:10px}.btn{margin-top:18px;border:0;background:#0F766E;color:white;border-radius:10px;padding:12px 18px;font-weight:850}.muted{color:#5F6B7A}@media(max-width:640px){.brand-logo{height:42px;max-width:210px}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="SmartRH Maroc"></a>
    <div class="card">
        <p class="muted">Étape 3 / 3</p>
        <h1>Créer le compte administrateur</h1>
        <p class="muted">Ce compte accédera au tableau de bord Filament de votre société.</p>
        <form method="post" action="{{ route('onboarding.admin-user.store') }}">
            @csrf
            <label>Nom complet *</label>
            <input name="name" value="{{ old('name') }}" required>
            <label>Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <label>Mot de passe *</label>
            <input type="password" name="password" required>
            <label>Confirmer le mot de passe *</label>
            <input type="password" name="password_confirmation" required>
            @if($errors->any())<p class="muted">{{ $errors->first() }}</p>@endif
            <button class="btn" type="submit">Créer mon espace</button>
        </form>
    </div>
</main>
</body>
</html>
