@php($product = config('smartrh.product_name', 'SmartRH Maroc'))
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mon espace salarié') - {{ $product }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/branding/smartrh-icon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/smartrh-icon.png') }}">
    <style>
        :root{--sr-deep-teal:#0F3D3E;--sr-deep:#0B2E33;--sr-primary:#0F766E;--sr-primary-hover:#0F5F59;--sr-page-bg:#0B1220;--sr-surface:#182235;--sr-soft:#111827;--sr-text:#F8FAFC;--sr-secondary-text:#CBD5E1;--sr-muted:#94A3B8;--sr-border:#2B3A52;--sr-gold:#D4A72C;--sr-success:#16A34A;--sr-warning:#D4A72C;--sr-danger:#DC2626}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:radial-gradient(circle at top left,rgba(20,184,166,.08),transparent 32rem),var(--sr-page-bg);color:var(--sr-text);-webkit-font-smoothing:antialiased}
        .top{background:linear-gradient(90deg,var(--sr-deep-teal),var(--sr-deep));color:#fff;border-bottom:1px solid rgba(243,201,105,.14)}
        .wrap{max-width:1120px;margin:auto;padding:18px 20px}
        .nav{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
        .brand{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:700;margin-right:auto}
        .brand-logo{width:38px;height:38px;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.22))}
        .nav a,.nav button{color:#DDE8E5;background:transparent;border:0;border-radius:8px;font:inherit;font-weight:580;text-decoration:none;padding:7px 12px;font-size:0.875rem;transition:all .15s ease}
        .nav a:hover,.nav button:hover{background:rgba(255,255,255,.08);color:#fff}
        .nav a.active{background:var(--sr-primary);color:#fff}
        .nav form{margin:0}
        .page-title{margin:24px 0 2px;font-size:1.375rem;font-weight:740;color:var(--sr-text);letter-spacing:-0.01em}
        .subtitle{margin:0 0 20px;color:var(--sr-secondary-text);font-size:0.875rem;line-height:1.6}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        .grid.two{grid-template-columns:repeat(2,minmax(0,1fr))}
        .grid.four{grid-template-columns:repeat(4,minmax(0,1fr))}
        .card{background:var(--sr-surface);border:1px solid var(--sr-border);border-radius:14px;padding:20px;margin:16px 0;box-shadow:0 16px 34px rgba(0,0,0,.22)}
        .stat{display:flex;flex-direction:column;gap:4px;padding:20px;margin:0}
        .stat .stat-icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;margin-bottom:4px;font-size:18px}
        .stat .stat-icon.blue{background:rgba(20,184,166,.15);color:#5EEAD4}
        .stat .stat-icon.green{background:rgba(22,163,74,.15);color:#86EFAC}
        .stat .stat-icon.amber{background:rgba(212,167,44,.15);color:#F3C969}
        .stat .stat-icon.red{background:rgba(220,38,38,.15);color:#FCA5A5}
        .stat .muted{font-size:0.8125rem;color:var(--sr-secondary-text);font-weight:500}
        .stat strong{font-size:1.5rem;font-weight:700;color:var(--sr-text);line-height:1.2}
        .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
        .btn,.link-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:var(--sr-primary);color:#fff;border:0;border-radius:10px;padding:9px 16px;font-weight:650;font-size:0.875rem;text-decoration:none;cursor:pointer;transition:all .15s ease}
        .btn:hover,.link-btn:hover{background:var(--sr-primary-hover)}
        .btn.secondary,.link-btn.secondary{background:var(--sr-soft);border:1px solid var(--sr-border);color:var(--sr-text)}
        .btn.secondary:hover,.link-btn.secondary:hover{background:var(--sr-soft)}
        .btn.light,.link-btn.light{background:rgba(20,184,166,.15);color:#5EEAD4}
        .btn.light:hover,.link-btn.light:hover{background:rgba(20,184,166,.22)}
        .btn.sm,.link-btn.sm{padding:6px 12px;font-size:0.8125rem}
        .badge{display:inline-flex;border-radius:9999px;padding:3px 10px;font-size:0.75rem;font-weight:600;line-height:1.5}
        .badge.blue{background:rgba(20,184,166,.15);color:#5EEAD4}
        .badge.green{background:rgba(22,163,74,.15);color:#86EFAC}
        .badge.amber{background:rgba(212,167,44,.15);color:#F3C969}
        .badge.red{background:rgba(220,38,38,.15);color:#FCA5A5}
        .badge.gray{background:rgba(148,163,184,.14);color:var(--sr-secondary-text)}
        .muted{color:var(--sr-muted);font-size:0.875rem}
        .details{display:grid;grid-template-columns:auto 1fr;gap:4px 16px;font-size:0.875rem;margin:12px 0}
        .details .muted{color:var(--sr-secondary-text)}
        .details strong{font-weight:600}
        h2{font-size:1rem;font-weight:600;color:var(--sr-text);margin:0 0 4px}
        .card h2{margin-bottom:8px}
        .alert{padding:14px 18px;background:rgba(20,184,166,.15);border:1px solid rgba(20,184,166,.3);border-radius:12px;color:#5EEAD4;font-size:0.875rem}
        .footer{margin:32px 0 0;padding:16px 0 0;border-top:1px solid var(--sr-border);font-size:0.8125rem;color:var(--sr-secondary-text)}
        .doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin:12px 0}
        .doc-card{background:var(--sr-surface);border:1px solid var(--sr-border);border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:8px}
        .doc-card .doc-icon{font-size:24px}
        .doc-card .doc-title{font-weight:600;font-size:0.875rem}
        .doc-card .doc-meta{font-size:0.75rem;color:var(--sr-secondary-text)}
        @media(max-width:768px){
            .grid,.grid.two,.grid.four{grid-template-columns:1fr}
            .wrap{padding:14px 16px}
            .nav{gap:4px}
            .nav a,.nav button{padding:6px 10px;font-size:0.8125rem}
            .page-title{font-size:1.125rem}
            .stat{padding:14px}
            .stat strong{font-size:1.25rem}
        }
    </style>
</head>
<body>
<div class="top">
    <div class="wrap nav">
        <div class="brand"><img class="brand-logo" src="{{ asset('images/branding/smartrh-icon.png') }}" alt="{{ $product }}">{{ $product }}</div>
        <a href="{{ route('employee.dashboard') }}" @if(request()->routeIs('employee.dashboard')) class="active" @endif>Accueil</a>
        <a href="{{ route('employee.payslips') }}" @if(request()->routeIs('employee.payslips*')) class="active" @endif>Bulletins</a>
        <a href="{{ route('employee.contracts') }}" @if(request()->routeIs('employee.contracts*')) class="active" @endif>Contrats</a>
        <a href="{{ route('employee.documents') }}" @if(request()->routeIs('employee.documents*')) class="active" @endif>Documents RH</a>
        <a href="{{ route('employee.leave-requests') }}" @if(request()->routeIs('employee.leave-requests*')) class="active" @endif>Congés</a>
        <a href="{{ route('employee.support') }}" @if(request()->routeIs('employee.support*')) class="active" @endif>Support</a>
        <form method="post" action="{{ route('employee.logout') }}">
            @csrf
            <button type="submit">Déconnexion</button>
        </form>
    </div>
</div>
<main class="wrap">
    @if(session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif
    @yield('content')
    <div class="footer">Support: {{ config('smartrh.support_email') }} · WhatsApp {{ config('smartrh.support_phone') }}</div>
</main>
</body>
</html>
