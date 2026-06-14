<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - SmartRH Maroc</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        :root{--sr-deep-teal:#0F3D3E;--sr-primary:#0F766E;--sr-page-bg:#F7F3EE;--sr-surface:#FFFFFF;--sr-text:#172033;--sr-muted:#5F6B7A;--sr-border:#E7DED3}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--sr-text);background:var(--sr-page-bg)}.wrap{max-width:900px;margin:42px auto;padding:0 22px}.card{background:var(--sr-surface);border:1px solid var(--sr-border);border-radius:14px;padding:32px;line-height:1.75;box-shadow:0 10px 24px rgba(23,32,51,.055)}h1,h2{color:var(--sr-deep-teal);letter-spacing:0}.brand{display:inline-flex;align-items:center;text-decoration:none}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.brand:hover{opacity:.9}.muted{color:var(--sr-muted)}.footer{margin-top:24px;color:var(--sr-muted);font-size:14px;border-top:1px solid var(--sr-border);padding-top:18px}@media(max-width:640px){.wrap{margin:26px auto}.card{padding:24px}.brand-logo{height:42px;max-width:210px}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="SmartRH Maroc"></a>
    <article class="card">
        @yield('content')
        <div class="footer">
            Les réglages de paie, paramètres légaux, contrats et documents générés doivent être vérifiés par un expert-comptable marocain, juriste ou professionnel qualifié avant utilisation en production.
        </div>
    </article>
</main>
</body>
</html>
