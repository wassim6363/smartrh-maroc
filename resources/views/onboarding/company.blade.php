<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer votre société - SmartRH Maroc</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        :root{--sr-deep-teal:#0F3D3E;--sr-primary:#0F766E;--sr-primary-hover:#0F5F59;--sr-page-bg:#F7F3EE;--sr-surface:#FFFFFF;--sr-text:#172033;--sr-muted:#8A94A3;--sr-border:#E7DED3;--sr-gold:#D4A72C}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:linear-gradient(135deg,#F7F3EE 0%,#FCFAF7 55%,#EEF7F4 100%);color:var(--sr-text)}.wrap{max-width:780px;margin:48px auto;padding:0 22px}.brand{display:inline-flex;align-items:center;margin-bottom:18px}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.card{background:var(--sr-surface);border:1px solid var(--sr-border);border-radius:14px;padding:30px;box-shadow:0 18px 42px rgba(23,32,51,.08)}h1{color:var(--sr-deep-teal);margin:10px 0 6px;font-size:34px;letter-spacing:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:block;font-weight:750;margin:12px 0 6px;color:#172033}input{width:100%;min-height:44px;border:1px solid var(--sr-border);border-radius:10px;padding:10px 12px;background:#fff;color:var(--sr-text)}input:focus{outline:3px solid rgba(20,184,166,.16);border-color:var(--sr-primary)}.btn{margin-top:18px;border:0;background:var(--sr-primary);color:white;border-radius:10px;padding:13px 18px;font-weight:850;cursor:pointer}.btn:hover{background:var(--sr-primary-hover)}.muted{color:#5F6B7A;line-height:1.6}.card>.muted:first-child{display:inline-flex;border:1px solid rgba(212,167,44,.42);background:#FFF8E1;color:var(--sr-deep-teal);border-radius:999px;padding:4px 10px;font-size:12px;font-weight:850}@media(max-width:700px){.grid{grid-template-columns:1fr}.card{padding:24px}.wrap{margin:28px auto}.brand-logo{height:42px;max-width:210px}.btn{width:100%}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="SmartRH Maroc"></a>
    <div class="card">
        <p class="muted">Étape 1 / 3</p>
        <h1>Créer votre société</h1>
        <p class="muted">Renseignez les informations principales de votre organisation.</p>
        <form method="post" action="{{ route('onboarding.company.store') }}">
            @csrf
            <label>Nom commercial *</label>
            <input name="name" value="{{ old('name') }}" required>
            @error('name')<p class="muted">{{ $message }}</p>@enderror
            <div class="grid">
                <div><label>Raison sociale</label><input name="legal_name" value="{{ old('legal_name') }}"></div>
                <div><label>ICE</label><input name="ice" value="{{ old('ice') }}"></div>
                <div><label>Ville</label><input name="city" value="{{ old('city') }}"></div>
                <div><label>Téléphone</label><input name="phone" value="{{ old('phone') }}"></div>
            </div>
            <label>Email société</label>
            <input type="email" name="email" value="{{ old('email') }}">
            <button class="btn" type="submit">Continuer</button>
        </form>
    </div>
</main>
</body>
</html>
