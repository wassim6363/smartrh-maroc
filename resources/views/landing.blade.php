@php
    $product = config('smartrh.product_name', 'SmartRH Maroc');
    $email = config('smartrh.support_email');
    $phone = config('smartrh.support_phone');
    $whatsapp = config('smartrh.whatsapp_number');
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product }} - Gestion RH & Paie marocaine en ligne</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        :root{--sr-deep-teal:#0F3D3E;--sr-primary:#0F766E;--sr-primary-hover:#0F5F59;--sr-mint:#14B8A6;--sr-page-bg:#F7F3EE;--sr-surface:#FFFFFF;--sr-surface-soft:#FCFAF7;--sr-text:#172033;--sr-secondary:#5F6B7A;--sr-muted:#8A94A3;--sr-border:#E7DED3;--sr-gold:#D4A72C}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--sr-text);background:var(--sr-page-bg)}body:before{content:"";position:fixed;inset:0;pointer-events:none;background:linear-gradient(135deg,rgba(212,167,44,.10),transparent 28%),radial-gradient(circle at 85% 8%,rgba(20,184,166,.10),transparent 28%)}a{text-decoration:none}.wrap{max-width:1180px;margin:auto;padding:0 22px}.nav{display:flex;align-items:center;justify-content:space-between;padding:20px 0}.brand{display:flex;align-items:center;gap:10px;font-size:22px;font-weight:900;color:#fff}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:11px 17px;border-radius:10px;font-weight:850;transition:background .16s ease,border-color .16s ease,color .16s ease}.primary{background:var(--sr-primary);color:#fff}.primary:hover{background:var(--sr-primary-hover)}.light{background:#fff;color:var(--sr-deep-teal)}.outline{background:#fff;color:var(--sr-deep-teal);border:1px solid var(--sr-border)}.outline:hover{border-color:var(--sr-primary);color:var(--sr-primary)}.hero{position:relative;overflow:hidden;background:var(--sr-deep-teal);color:#fff}.hero:after{content:"";position:absolute;right:-8%;top:0;width:42%;height:100%;background:linear-gradient(135deg,rgba(212,167,44,.18),rgba(20,184,166,.14));clip-path:polygon(16% 0,100% 0,84% 100%,0 100%);pointer-events:none}.hero .wrap{position:relative;z-index:1}.hero-inner{padding:72px 0 88px;max-width:900px}.hero h1{font-size:52px;line-height:1.06;margin:18px 0;letter-spacing:0}.hero p{font-size:19px;line-height:1.7;color:#DDE8E5;max-width:820px}.section{padding:64px 0}.band{background:var(--sr-surface-soft);border-block:1px solid var(--sr-border)}.section h2{font-size:34px;line-height:1.2;margin:0 0 14px;color:var(--sr-deep-teal);letter-spacing:0}.lead{color:var(--sr-secondary);font-size:17px;max-width:760px;line-height:1.7}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:24px}.plans{grid-template-columns:repeat(4,1fr)}.card{background:var(--sr-surface);border:1px solid var(--sr-border);border-radius:14px;padding:24px;box-shadow:0 10px 24px rgba(23,32,51,.055)}.card strong,.card h3{color:var(--sr-deep-teal)}.price{font-size:30px;font-weight:900;color:var(--sr-primary)}.split{display:grid;grid-template-columns:1.05fr .95fr;gap:30px;align-items:center}.panel{background:#fff;border:1px solid var(--sr-border);border-radius:14px;padding:22px;box-shadow:0 10px 24px rgba(23,32,51,.055)}.metric{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid var(--sr-border);padding:13px 0}.metric:last-child{border-bottom:0}.metric strong{color:var(--sr-primary)}.cta{background:linear-gradient(135deg,#FCFAF7,#EEF7F4);border-top:1px solid var(--sr-border);border-bottom:1px solid var(--sr-border)}.footer{background:var(--sr-deep-teal);color:#DDE8E5;padding:34px 0}.footer strong{color:#fff}@media(max-width:1000px){.grid,.plans,.split{grid-template-columns:1fr 1fr}}@media(max-width:700px){.grid,.plans,.split{grid-template-columns:1fr}.hero h1{font-size:34px}.hero-inner{padding:44px 0 58px}.hero:after{display:none}.nav{align-items:flex-start;gap:14px;flex-direction:column}.nav div{display:grid;gap:10px;width:100%}.brand-logo{height:42px;max-width:210px}.btn{width:100%}.section{padding:44px 0}}
    </style>
</head>
<body>
<header class="hero">
    <div class="wrap">
        <nav class="nav">
            <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="{{ $product }}"></a>
            <div>
                <a class="btn light" href="/pricing">Tarifs</a>
                <a class="btn light" href="/request-demo">Demander une démo</a>
                <a class="btn primary" href="/onboarding/company">Essai gratuit 14 jours</a>
            </div>
        </nav>
        <div class="hero-inner">
            <h1>Gestion RH & Paie marocaine en ligne</h1>
            <p>Une plateforme SaaS sécurisée pour les PME et cabinets comptables: salariés, bulletins de paie, contrats RH, documents, portail salarié et audit.</p>
            <p><a class="btn primary" href="/pricing">Voir les packs</a> <a class="btn light" href="/request-demo">Planifier une démo</a></p>
        </div>
    </div>
</header>
<main>
    <section class="section">
        <div class="wrap">
            <h2>Fonctionnalités</h2>
            <p class="lead">SmartRH Maroc centralise les workflows RH qui prennent trop de temps quand ils vivent dans Excel, PDF et WhatsApp.</p>
            <div class="grid">
                <div class="card"><strong>Dossiers salariés</strong><br>Informations personnelles, poste, salaire, banque, documents et historique.</div>
                <div class="card"><strong>Congés & absences</strong><br>Demandes, validation, impact paie et suivi opérationnel.</div>
                <div class="card"><strong>Audit & sécurité</strong><br>Isolation par société, rôles, stockage privé et journal d'activité.</div>
            </div>
        </div>
    </section>
    <section class="section band">
        <div class="wrap split">
            <div>
                <h2>Payroll Maroc</h2>
                <p class="lead">Générez vos bulletins de paie en quelques clics avec CNSS, AMO, IR, frais professionnels, indemnités exonérées et cumuls annuels configurables.</p>
            </div>
            <div class="panel">
                <div class="metric"><span>Salaire brut</span><strong>6 330,28 MAD</strong></div>
                <div class="metric"><span>CNSS + AMO</span><strong>330,28 MAD</strong></div>
                <div class="metric"><span>Net à payer</span><strong>6 000,00 MAD</strong></div>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="wrap">
            <h2>Contrats RH</h2>
            <p class="lead">Automatisez vos contrats CDI, CDD, ANAPEC et attestations avec modèles, variables société/salarié et PDF professionnel.</p>
            <div class="grid">
                <div class="card"><strong>Contrats</strong><br>CDI, CDD, stage, ANAPEC, avenants.</div>
                <div class="card"><strong>Documents</strong><br>Attestation de travail, certificat, solde de tout compte.</div>
                <div class="card"><strong>Signatures</strong><br>Suivi des contrats générés et signés.</div>
            </div>
        </div>
    </section>
    <section class="section band">
        <div class="wrap">
            <h2>Espace salarié</h2>
            <p class="lead">Chaque salarié accède à ses bulletins, contrats, documents RH et demandes depuis un portail simple et sécurisé.</p>
        </div>
    </section>
    <section class="section">
        <div class="wrap">
            <h2>Packs SaaS</h2>
            <p class="lead">Démarrez petit, puis évoluez vers un pack supérieur quand votre usage grandit.</p>
            <div class="grid plans">
                @foreach($plans as $plan)
                    <div class="card">
                        <h3>{{ $plan->name }}</h3>
                        <div class="price">{{ (float) $plan->monthly_price > 0 ? number_format((float) $plan->monthly_price, 0, ',', ' ') . ' MAD' : 'Sur devis' }}</div>
                        <p>{{ $plan->max_employees ?: 'Illimité' }} salariés · {{ $plan->max_payslips_per_month ?: 'Illimité' }} bulletins/mois</p>
                    </div>
                @endforeach
            </div>
            <p><a class="btn primary" href="/pricing">Comparer les packs</a> <a class="btn outline" href="/request-demo">Demander une démo</a></p>
        </div>
    </section>
    <section class="section band">
        <div class="wrap">
            <h2>Sécurité</h2>
            <p class="lead">Une plateforme SaaS sécurisée pour les PME et cabinets comptables: accès par rôle, isolation par company_id, PDFs privés et audit logs.</p>
        </div>
    </section>
    <section class="section cta">
        <div class="wrap">
            <h2>Prêt à lancer votre espace RH ?</h2>
            <p class="lead">Commencez l'essai de 14 jours ou planifiez une démonstration accompagnée.</p>
            <p><a class="btn primary" href="/onboarding/company">Essai gratuit 14 jours</a> <a class="btn outline" href="/request-demo">Demander une démo</a> <a class="btn outline" href="/request-demo">Parler à un conseiller</a></p>
        </div>
    </section>
</main>
<footer class="footer"><div class="wrap"><strong>{{ $product }}</strong><br>Email: {{ $email }} - WhatsApp: {{ $phone }}<br>{{ config('smartrh.payroll_disclaimer') }}</div></footer>
</body>
</html>
