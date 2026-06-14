<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 15mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; line-height: 1.48; }
        h1 { color: #0B2E33; font-size: 24px; margin: 0 0 6px; }
        h2 { color: #0F3D3E; font-size: 12px; margin: 16px 0 7px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #E7DED3; padding: 9px; vertical-align: top; }
        th { background: #F7F3EE; color: #0F3D3E; text-align: left; }
        .header { border-bottom: 3px solid #0F766E; padding-bottom: 12px; margin-bottom: 20px; }
        .brand { display: table; width: 100%; }
        .brand-left, .brand-right { display: table-cell; vertical-align: top; }
        .brand-right { text-align: right; }
        .logo { height: 44px; width: auto; margin-bottom: 4px; }
        .gold-line { height: 2px; width: 90px; background: #D4A72C; margin-top: 5px; }
        .muted { color: #5F6B7A; }
        .grid { display: table; width: 100%; margin: 18px 0; table-layout: fixed; }
        .col { display: table-cell; width: 50%; vertical-align: top; }
        .col:first-child { padding-right: 8px; }
        .col:last-child { padding-left: 8px; }
        .status { display: inline-block; padding: 4px 9px; border-radius: 12px; background: #ECFDF5; color: #0F766E; font-weight: bold; }
        .total td { background: #0F3D3E; color: #FFFFFF; font-size: 15px; font-weight: bold; }
        .total td:last-child { background: #0F766E; text-align: right; }
        .right { text-align: right; }
        .footer { border-top: 1px solid #E7DED3; margin-top: 26px; padding-top: 11px; color: #5F6B7A; font-size: 9.5px; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/branding/smartrh-logo.png');
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ') . ' MAD';
@endphp

<div class="header">
    <div class="brand">
        <div class="brand-left">
            @if(file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="SmartRH Maroc">
            @else
                <strong>SmartRH Maroc</strong>
            @endif
            <div class="gold-line"></div>
        </div>
        <div class="brand-right">
            <h1>Facture</h1>
            <div><strong>{{ $invoice->invoice_number }}</strong></div>
            <div class="muted">Émise le {{ $invoice->issued_at?->format('d/m/Y') ?: '-' }}</div>
            <div class="muted">Échéance {{ $invoice->due_at?->format('d/m/Y') ?: '-' }}</div>
            <div style="margin-top:6px"><span class="status">{{ strtoupper($invoice->status) }}</span></div>
        </div>
    </div>
</div>

<div class="grid">
    <div class="col">
        <h2>Émetteur</h2>
        <strong>{{ config('smartrh.brand_legal_name', 'SmartRH Maroc') }}</strong><br>
        {{ config('smartrh.brand_address', 'Casablanca, Maroc') }}<br>
        @if(config('smartrh.brand_ice')) ICE: {{ config('smartrh.brand_ice') }}<br>@endif
        @if(config('smartrh.brand_tax_id')) IF: {{ config('smartrh.brand_tax_id') }}<br>@endif
        Contact: {{ config('smartrh.support_email') }} @if(config('smartrh.support_phone')) - {{ config('smartrh.support_phone') }} @endif
    </div>
    <div class="col">
        <h2>Client</h2>
        <strong>{{ $invoice->company?->legal_name ?: $invoice->company?->name }}</strong><br>
        ICE: {{ $invoice->company?->ice ?: '-' }}<br>
        IF: {{ $invoice->company?->if_number ?: $invoice->company?->if ?: '-' }}<br>
        {{ $invoice->company?->address }} {{ $invoice->company?->city }}
    </div>
</div>

<h2>Détail de facturation</h2>
<table>
    <thead>
    <tr>
        <th>Description</th>
        <th>Période</th>
        <th class="right">Montant</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Abonnement {{ $invoice->subscription?->plan?->name ?: 'SmartRH Maroc' }}</td>
        <td>{{ $invoice->billing_period_start?->format('d/m/Y') ?: '-' }} - {{ $invoice->billing_period_end?->format('d/m/Y') ?: '-' }}</td>
        <td class="right">{{ $money($invoice->amount) }}</td>
    </tr>
    <tr class="total">
        <td colspan="2">Total à payer</td>
        <td>{{ $money($invoice->amount) }}</td>
    </tr>
    </tbody>
</table>

<h2>Informations de paiement</h2>
<p class="muted">Paiement manuel pour cette version MVP. Le rapprochement bancaire et les passerelles de paiement seront ajoutés ultérieurement.</p>

<div class="footer">
    {{ config('smartrh.product_name', 'SmartRH Maroc') }} fournit des outils RH, paie et facturation SaaS. Cette facture est générée automatiquement.
    Les informations fiscales et comptables doivent être validées par votre expert-comptable avant production.
</div>
</body>
</html>
