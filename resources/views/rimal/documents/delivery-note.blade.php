<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BL-{{ $sale->number }}</title>
    <style>
        *{box-sizing:border-box}body{font-family:'Segoe UI',system-ui,sans-serif;font-size:12px;color:#0f172a;margin:24px}
        header{display:flex;justify-content:space-between;border-bottom:2px solid #e2e8f0;padding-bottom:12px;margin-bottom:16px}
        h1{font-size:18px;margin:0}h2{font-size:16px;margin:0}.muted{color:#64748b}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}.box{border:1px solid #e2e8f0;border-radius:6px;padding:10px}
        .box h3{margin:0 0 6px;font-size:10px;text-transform:uppercase;color:#64748b}
        table{width:100%;border-collapse:collapse}th{background:#f1f5f9;text-align:left;padding:6px;font-size:10px;text-transform:uppercase}
        td{padding:6px;border-bottom:1px solid #e2e8f0}.r{text-align:right}
        .sign{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:40px}.sign div{border-top:1px solid #94a3b8;padding-top:6px;height:70px}
        .toolbar{margin-bottom:12px}@media print{.toolbar{display:none}body{margin:0}}@page{size:A4;margin:12mm}
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>
    <header>
        <div>
            <h1>{{ $brand['trade_name'] ?? $company->name }}</h1>
            <p class="muted">
                {{ $company->legal_name }}<br>
                {{ $sale->store?->address ?? $company->address }} {{ $sale->store?->city ?? $company->city }}<br>
                {{ $sale->store?->phone ?? $company->phone }}
                @if($company->ice)<br>ICE : {{ $company->ice }}@endif
                @if($company->rc) · RC : {{ $company->rc }}@endif
            </p>
        </div>
        <div style="text-align:right">
            <h2>BON DE LIVRAISON</h2>
            <p><strong>BL-{{ $sale->number }}</strong></p>
            <p class="muted">Commande : {{ $sale->number }}<br>
                Date : {{ optional($sale->delivered_at ?? now())->format('d/m/Y') }}<br>
                @if($sale->expected_delivery_at)Livraison prévue : {{ $sale->expected_delivery_at->format('d/m/Y') }}@endif
            </p>
        </div>
    </header>

    <div class="grid">
        <div class="box">
            <h3>Client</h3>
            <strong>{{ $sale->customer?->company_name ?: $sale->customer?->name }}</strong><br>
            {{ $sale->delivery_address ?: trim(($sale->customer?->address ?? '').' '.($sale->customer?->city ?? '')) }}<br>
            {{ $sale->contact_phone ?: ($sale->customer?->phone ?? $sale->customer?->mobile) }}
            @if($sale->customer?->tax_id)<br>ICE / IF : {{ $sale->customer->tax_id }}@endif
        </div>
        <div class="box">
            <h3>Livraison</h3>
            Boutique : {{ $sale->store?->name }}<br>
            Livreur : {{ $agent?->name ?? '—' }}<br>
            Commercial : {{ $sale->salesperson?->name ?? '—' }}
        </div>
    </div>

    <table>
        <thead><tr><th>Réf.</th><th>Désignation</th><th class="r">Quantité</th><th class="r">Qté livrée</th></tr></thead>
        <tbody>
            @foreach($sale->lines as $line)
                <tr><td>{{ $line->sku ?? $line->product?->sku ?? '—' }}</td><td>{{ $line->product_name }}@if($line->description)<br><span class="muted">{{ $line->description }}</span>@endif</td><td class="r">{{ rtrim(rtrim(number_format((float) $line->quantity, 3, ',', ' '), '0'), ',') }}</td><td class="r"></td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td colspan="2"><strong>Nombre d'articles</strong></td><td class="r"><strong>{{ rtrim(rtrim(number_format((float) $sale->lines->sum('quantity'), 3, ',', ' '), '0'), ',') }}</strong></td><td></td></tr></tfoot>
    </table>

    @if($sale->notes)<p style="margin-top:12px"><strong>Notes :</strong> {{ $sale->notes }}</p>@endif

    <div class="sign"><div>Signature livreur</div><div>Reçu par (nom, date, signature) — cachet client</div></div>
</body>
</html>
