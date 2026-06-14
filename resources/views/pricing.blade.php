@php($product = config('smartrh.product_name', 'SmartRH Maroc'))
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tarifs - {{ $product }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        :root{--sr-deep-teal:#0F3D3E;--sr-primary:#0F766E;--sr-primary-hover:#0F5F59;--sr-page-bg:#F7F3EE;--sr-surface:#FFFFFF;--sr-soft:#FCFAF7;--sr-text:#172033;--sr-secondary:#5F6B7A;--sr-muted:#8A94A3;--sr-border:#E7DED3;--sr-gold:#D4A72C;--sr-danger:#DC2626}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--sr-text);background:var(--sr-page-bg)}a{text-decoration:none}.wrap{max-width:1180px;margin:auto;padding:0 22px}.nav{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:22px 0}.brand{display:flex;align-items:center;color:var(--sr-deep-teal)}.brand-logo{width:auto;height:44px;max-width:230px;object-fit:contain}.nav>div{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:11px 16px;border-radius:10px;font-weight:800;transition:background .15s ease,border-color .15s ease,color .15s ease}.primary{background:var(--sr-primary);color:white}.primary:hover{background:var(--sr-primary-hover)}.outline{border:1px solid var(--sr-border);color:var(--sr-deep-teal);background:white}.outline:hover{border-color:var(--sr-primary);color:var(--sr-primary)}.hero{padding:70px 0;background:var(--sr-deep-teal);border-block:1px solid rgba(255,255,255,.1);color:#fff}.hero h1{font-size:44px;line-height:1.1;margin:0 0 14px;color:#fff;letter-spacing:0}.lead{font-size:18px;line-height:1.7;color:#DDE8E5;max-width:760px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin:34px 0 70px}.card{border:1px solid var(--sr-border);border-radius:14px;padding:24px;background:white;box-shadow:0 10px 24px rgba(23,32,51,.055);display:flex;flex-direction:column;gap:12px}.card h2{margin:0;color:var(--sr-text)}.card.featured{border-color:var(--sr-gold);box-shadow:0 14px 32px rgba(212,167,44,.14)}.price{font-size:32px;font-weight:900;color:var(--sr-primary)}.muted{color:var(--sr-muted)}.spec{display:flex;justify-content:space-between;gap:12px;border-top:1px solid var(--sr-border);padding-top:10px;font-size:14px}.yes,.no{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:12px;font-weight:850}.yes{color:#15803D;background:#DCFCE7}.no{color:#991B1B;background:#FEE2E2}.footer{background:var(--sr-deep-teal);color:#DDE8E5;padding:30px 0}@media(max-width:1000px){.grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){.grid{grid-template-columns:1fr}.nav{align-items:flex-start;flex-direction:column}.nav>div{width:100%;justify-content:stretch}.brand-logo{height:42px;max-width:210px}.hero h1{font-size:34px}.btn{width:100%}}
    </style>
</head>
<body>
<header class="wrap nav">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="{{ $product }}"></a>
    <div><a class="btn outline" href="/request-demo">Demander une démo</a> <a class="btn primary" href="/onboarding/company">Essai gratuit 14 jours</a> <a class="btn outline" href="/request-demo">Parler à un conseiller</a></div>
</header>
<section class="hero">
    <div class="wrap">
        <h1>Des packs SaaS clairs pour gérer RH, paie et documents au Maroc</h1>
        <p class="lead">Choisissez un pack, démarrez avec 14 jours d'essai, puis activez manuellement votre abonnement depuis l'espace admin.</p>
    </div>
</section>
<main class="wrap">
    <div class="grid">
        @foreach($plans as $plan)
            <article class="card {{ $plan->slug === 'business' ? 'featured' : '' }}">
                <h2>{{ $plan->name }}</h2>
                <div class="price">{{ (float) $plan->monthly_price > 0 ? number_format((float) $plan->monthly_price, 0, ',', ' ') . ' MAD' : 'Sur devis' }}</div>
                <p class="muted">par mois</p>
                <div class="spec"><span>Sociétés</span><strong>{{ $plan->max_companies ?: 'Illimité' }}</strong></div>
                <div class="spec"><span>Salariés</span><strong>{{ $plan->max_employees ?: 'Illimité' }}</strong></div>
                <div class="spec"><span>Bulletins / mois</span><strong>{{ $plan->max_payslips_per_month ?: 'Illimité' }}</strong></div>
                <div class="spec"><span>Contrats / mois</span><strong>{{ $plan->max_contracts_per_month ?: 'Illimité' }}</strong></div>
                <div class="spec"><span>Portail salarié</span><span class="{{ $plan->employee_portal_enabled ? 'yes' : 'no' }}">{{ $plan->employee_portal_enabled ? 'Inclus' : 'Non inclus' }}</span></div>
                <div class="spec"><span>Demandes documents</span><span class="{{ $plan->document_requests_enabled ? 'yes' : 'no' }}">{{ $plan->document_requests_enabled ? 'Inclus' : 'Non inclus' }}</span></div>
                <div class="spec"><span>Audit logs</span><span class="{{ $plan->audit_logs_enabled ? 'yes' : 'no' }}">{{ $plan->audit_logs_enabled ? 'Inclus' : 'Non inclus' }}</span></div>
                <div class="spec"><span>Accès API</span><span class="{{ $plan->api_access_enabled ? 'yes' : 'no' }}">{{ $plan->api_access_enabled ? 'Inclus' : 'Non inclus' }}</span></div>
                <a class="btn {{ $plan->slug === 'enterprise' ? 'outline' : 'primary' }}" href="{{ $plan->slug === 'enterprise' ? '/request-demo' : '/onboarding/company' }}">
                    {{ $plan->slug === 'enterprise' ? 'Nous contacter' : ($plan->slug === 'starter' ? 'Essai gratuit' : 'Choisir ce pack') }}
                </a>
            </article>
        @endforeach
    </div>
</main>
<footer class="footer"><div class="wrap">SmartRH Maroc - SaaS RH & Paie marocaine. Paiement manuel pendant la phase MVP.</div></footer>
</body>
</html>
