<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport {{ ucfirst($type) }} — {{ $company->name }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',system-ui,sans-serif;font-size:12px;color:#1e293b;padding:24px}
        .header{display:flex;justify-content:space-between;align-items:start;margin-bottom:24px;border-bottom:2px solid #16a34a;padding-bottom:16px}
        .header h1{font-size:20px;color:#16a34a}
        .meta{color:#64748b;font-size:11px;margin-top:4px}
        table{width:100%;border-collapse:collapse;margin-bottom:20px}
        th{background:#f8fafc;border-bottom:2px solid #e2e8f0;padding:8px;text-align:left;font-size:11px;text-transform:uppercase;color:#64748b}
        td{padding:8px;border-bottom:1px solid #f1f5f9}
        .text-right{text-align:right}
        .kpi{display:inline-block;margin-right:24px;margin-bottom:12px}
        .kpi-label{font-size:10px;text-transform:uppercase;color:#64748b}
        .kpi-value{font-size:18px;font-weight:700;color:#16a34a}
        @media print{body{padding:0}button{display:none!important}}
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Rapport {{ ucfirst($type) }}</h1>
            <p class="meta">{{ $company->name }} — Période : {{ $filters['from']->format('d/m/Y') }} au {{ $filters['to']->format('d/m/Y') }}</p>
            <p class="meta">Généré le {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <button onclick="window.print()" style="padding:6px 16px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">Imprimer</button>
    </div>

    @if($type === 'sales')
        <div style="margin-bottom:20px">
            <div class="kpi"><div class="kpi-label">CA total</div><div class="kpi-value">{{ number_format($totalRevenue, 2, ',', ' ') }} MAD</div></div>
            <div class="kpi"><div class="kpi-label">Ventes</div><div class="kpi-value">{{ $totalCount }}</div></div>
        </div>
        <table>
            <thead><tr><th>Réf</th><th>Date</th><th>Client</th><th>Boutique</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @foreach($sales as $s)
                    <tr><td>{{ $s->number }}</td><td>{{ optional($s->sold_at)->format('d/m/Y') }}</td><td>{{ $s->customer?->name ?? 'Passage' }}</td><td>{{ $s->store?->name }}</td><td class="text-right">{{ number_format($s->total_ttc, 2, ',', ' ') }}</td></tr>
                @endforeach
                @foreach($posOnly as $s)
                    <tr><td>{{ $s->number }} (POS)</td><td>{{ optional($s->completed_at)->format('d/m/Y') }}</td><td>{{ $s->customer?->name ?? 'Passage' }}</td><td>{{ $s->store?->name }}</td><td class="text-right">{{ number_format($s->total_ttc, 2, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @elseif($type === 'products')
        <table>
            <thead><tr><th>Produit</th><th class="text-right">Qté vendue</th><th class="text-right">CA</th></tr></thead>
            <tbody>
                @foreach($topProducts as $p)
                    <tr><td>{{ $p['product_name'] }}</td><td class="text-right">{{ number_format($p['qty']) }}</td><td class="text-right">{{ number_format($p['total'], 2, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @elseif($type === 'customers')
        <table>
            <thead><tr><th>Client</th><th class="text-right">Ventes</th><th class="text-right">CA</th></tr></thead>
            <tbody>
                @foreach($bestCustomers as $c)
                    <tr><td>{{ $c['customer']?->name ?? '—' }}</td><td class="text-right">{{ $c['count'] }}</td><td class="text-right">{{ number_format($c['total'], 2, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @elseif($type === 'payments')
        <div style="margin-bottom:20px">
            <div class="kpi"><div class="kpi-label">Encaissé</div><div class="kpi-value">{{ number_format($validatedTotal, 2, ',', ' ') }} MAD</div></div>
            <div class="kpi"><div class="kpi-label">En attente</div><div class="kpi-value">{{ number_format($pendingInvoices, 2, ',', ' ') }} MAD</div></div>
        </div>
        <table>
            <thead><tr><th>Mode</th><th class="text-right">Transactions</th><th class="text-right">Montant</th></tr></thead>
            <tbody>
                @foreach($byMethod as $m)
                    <tr><td>{{ $m['label'] }}</td><td class="text-right">{{ $m['count'] }}</td><td class="text-right">{{ number_format($m['total'], 2, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @elseif($type === 'stock')
        <div style="margin-bottom:20px">
            <div class="kpi"><div class="kpi-label">Valorisation</div><div class="kpi-value">{{ number_format($valuation, 2, ',', ' ') }} MAD</div></div>
            <div class="kpi"><div class="kpi-label">Sous seuil</div><div class="kpi-value">{{ $belowThreshold->count() }}</div></div>
        </div>
        <table>
            <thead><tr><th>Produit</th><th>Boutique</th><th class="text-right">Qté</th><th class="text-right">Min</th><th>Statut</th></tr></thead>
            <tbody>
                @foreach($belowThreshold as $l)
                    <tr><td>{{ $l->product?->name }}</td><td>{{ $l->store?->name }}</td><td class="text-right">{{ $l->quantity }}</td><td class="text-right">{{ $l->min_quantity }}</td><td>{{ $l->statusLabel() }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="margin-bottom:20px">
            <div class="kpi"><div class="kpi-label">CA</div><div class="kpi-value">{{ number_format($revenue ?? 0, 2, ',', ' ') }} MAD</div></div>
            <div class="kpi"><div class="kpi-label">Ventes</div><div class="kpi-value">{{ $count ?? 0 }}</div></div>
            <div class="kpi"><div class="kpi-label">Ticket moyen</div><div class="kpi-value">{{ number_format($avgTicket ?? 0, 2, ',', ' ') }}</div></div>
        </div>
    @endif
</body>
</html>
