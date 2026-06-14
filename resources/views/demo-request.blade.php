<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demander une démo - SmartRH Maroc</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        :root{--sr-deep-teal:#0F3D3E;--sr-primary:#0F766E;--sr-primary-hover:#0F5F59;--sr-page-bg:#F7F3EE;--sr-surface:#FFFFFF;--sr-soft:#FCFAF7;--sr-text:#172033;--sr-muted:#8A94A3;--sr-border:#E7DED3;--sr-danger:#DC2626}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:linear-gradient(135deg,#F7F3EE 0%,#FCFAF7 56%,#EEF7F4 100%);color:var(--sr-text)}.wrap{max-width:880px;margin:44px auto;padding:0 22px}.card{background:var(--sr-surface);border:1px solid var(--sr-border);border-radius:14px;padding:30px;box-shadow:0 18px 42px rgba(23,32,51,.08)}h1{color:var(--sr-deep-teal);margin:18px 0 8px;font-size:34px;letter-spacing:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:block;font-weight:750;margin:12px 0 6px;color:#172033}input,select,textarea{width:100%;min-height:44px;border:1px solid var(--sr-border);border-radius:10px;padding:10px 12px;background:#fff;color:var(--sr-text)}input:focus,select:focus,textarea:focus{outline:3px solid rgba(20,184,166,.16);border-color:var(--sr-primary)}textarea{min-height:120px}.btn{margin-top:18px;border:0;background:var(--sr-primary);color:white;border-radius:10px;padding:13px 18px;font-weight:850;cursor:pointer}.btn:hover{background:var(--sr-primary-hover)}.muted{color:#5F6B7A;line-height:1.6}.brand{display:inline-flex;align-items:center;text-decoration:none}.brand-logo{width:auto;height:46px;max-width:230px;object-fit:contain}.errors{margin:0 0 16px;padding:12px 16px;border:1px solid #FECACA;background:#FEF2F2;color:#991B1B;border-radius:10px}.errors ul{margin:8px 0 0 18px;padding:0}@media(max-width:720px){.grid{grid-template-columns:1fr}.card{padding:24px}.wrap{margin:26px auto}.brand-logo{height:42px;max-width:210px}.btn{width:100%}}
    </style>
</head>
<body>
<main class="wrap">
    <a class="brand" href="/"><img class="brand-logo" src="{{ asset('images/branding/smartrh-logo.png') }}" alt="SmartRH Maroc"></a>
    <div class="card">
        <h1>Demander une démo</h1>
        <p class="muted">Présentez-nous votre besoin RH & paie. Un conseiller vous contacte rapidement.</p>
        @if($errors->any())
            <div class="errors" role="alert">
                <strong>Merci de corriger les informations suivantes :</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="post" action="{{ route('request-demo.store') }}">
            @csrf
            <div class="grid">
                <div><label>Nom complet *</label><input name="full_name" value="{{ old('full_name') }}" required></div>
                <div><label>Société *</label><input name="company_name" value="{{ old('company_name') }}" required></div>
                <div><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                <div><label>Téléphone *</label><input name="phone" value="{{ old('phone') }}" required></div>
                <div><label>Taille de l’entreprise</label><input name="company_size" value="{{ old('company_size') }}" placeholder="Ex: 25 salariés"></div>
                <div>
                    <label>Pack souhaité</label>
                    <select name="target_plan">
                        <option value="">À définir</option>
                        @foreach(['Starter','Business','Cabinet','Enterprise'] as $plan)
                            <option value="{{ $plan }}" @selected(old('target_plan', $targetPlan ?? null) === $plan)>{{ $plan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label>Message</label>
            <textarea name="message">{{ old('message') }}</textarea>
            <button class="btn" type="submit">Envoyer ma demande</button>
        </form>
    </div>
</main>
</body>
</html>
