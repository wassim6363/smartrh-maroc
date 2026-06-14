<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choisir un plan - SmartRH Maroc</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:#F7F3EE;color:#172033}.wrap{max-width:1100px;margin:42px auto;padding:0 22px}.brand{display:inline-flex;align-items:center;margin-bottom:18px}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.card{background:white;border:1px solid #E7DED3;border-radius:14px;padding:22px;display:flex;flex-direction:column;gap:10px;box-shadow:0 10px 24px rgba(23,32,51,.055)}.card.business{border-color:#D4A72C}.price{font-size:30px;font-weight:900;color:#0F766E}.btn{border:0;background:#0F766E;color:white;border-radius:10px;padding:12px 16px;font-weight:850}.muted{color:#5F6B7A}.spec{font-size:14px;border-top:1px solid #E7DED3;padding-top:8px}@media(max-width:960px){.grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){.grid{grid-template-columns:1fr}.brand-logo{height:42px;max-width:210px}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="SmartRH Maroc"></a>
    <p class="muted">Étape 2 / 3</p>
    <h1>Choisir votre plan</h1>
    <p class="muted">Votre abonnement démarre en période d'essai de 14 jours.</p>
    <div class="grid">
        @foreach($plans as $plan)
            <form class="card {{ $plan->slug }}" method="post" action="{{ route('onboarding.plan.store') }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <h2>{{ $plan->name }}</h2>
                <div class="price">{{ (float) $plan->monthly_price > 0 ? number_format((float) $plan->monthly_price, 0, ',', ' ') . ' MAD' : 'Sur devis' }}</div>
                <div class="spec">{{ $plan->max_employees ?: 'Illimité' }} salariés</div>
                <div class="spec">{{ $plan->max_payslips_per_month ?: 'Illimité' }} bulletins / mois</div>
                <div class="spec">{{ $plan->document_requests_enabled ? 'Demandes documents incluses' : 'Demandes documents non incluses' }}</div>
                <button class="btn" type="submit">{{ $plan->slug === 'enterprise' ? 'Demander un devis' : 'Choisir ce pack' }}</button>
            </form>
        @endforeach
    </div>
</main>
</body>
</html>
