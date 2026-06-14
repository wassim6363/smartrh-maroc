<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenue - SmartRH Maroc</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:#F7F3EE;color:#172033}.wrap{max-width:720px;margin:70px auto;padding:0 22px}.brand{display:inline-flex;align-items:center;margin-bottom:18px}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.card{background:white;border:1px solid #E7DED3;border-radius:14px;padding:34px;box-shadow:0 10px 24px rgba(23,32,51,.055)}h1{color:#0F3D3E}.btn{display:inline-flex;margin-top:18px;background:#0F766E;color:white;border-radius:10px;padding:12px 18px;font-weight:850;text-decoration:none}.muted{color:#5F6B7A}@media(max-width:640px){.brand-logo{height:42px;max-width:210px}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="SmartRH Maroc"></a>
    <div class="card">
        <h1>Votre espace SmartRH Maroc est prêt</h1>
        <p class="muted">Votre société et votre abonnement d'essai de 14 jours ont été créés.</p>
        <p>Connecté en tant que <strong>{{ $user->name }}</strong>.</p>
        <a class="btn" href="/admin">Accéder au tableau de bord</a>
    </div>
</main>
</body>
</html>
