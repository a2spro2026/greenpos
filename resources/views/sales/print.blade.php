<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $sale->number }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',system-ui,sans-serif;font-size:12px;color:#1e293b;padding:24px}
        .header{display:flex;justify-content:space-between;margin-bottom:32px}
        .header h1{font-size:22px;color:#16a34a}
        .meta{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
        .meta dt{color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.5px}
        .meta dd{font-weight:600;margin-top:2px}
        table{width:100%;border-collapse:collapse;margin-bottom:24px}
        th{background:#f8fafc;border-bottom:2px solid #e2e8f0;padding:8px;text-align:left;font-size:11px;text-transform:uppercase;color:#64748b}
        td{padding:8px;border-bottom:1px solid #f1f5f9}
        .text-right{text-align:right}
        .totals{width:280px;margin-left:auto}
        .totals tr td{padding:4px 8px}
        .totals .grand{font-size:16px;font-weight:700;color:#16a34a;border-top:2px solid #e2e8f0}
        .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600}
        @media print{body{padding:0}button{display:none!important}}
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $sale->number }}</h1>
            <p style="color:#64748b">{{ $sale->originLabel() }} — {{ $sale->statusLabel() }}</p>
        </div>
        <button onclick="window.print()" style="padding:6px 16px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">Imprimer</button>
    </div>

    <dl class="meta">
        <div><dt>Date</dt><dd>{{ optional($sale->sold_at)->format('d/m/Y') }}</dd></div>
        <div><dt>Client</dt><dd>{{ $sale->customer?->name ?? 'Client de passage' }}</dd></div>
        <div><dt>Boutique</dt><dd>{{ $sale->store?->name }}</dd></div>
        <div><dt>Commercial</dt><dd>{{ $sale->salesperson?->name ?? '—' }}</dd></div>
    </dl>

    <table>
        <thead><tr><th>Produit</th><th>SKU</th><th class="text-right">Qté</th><th class="text-right">PU</th><th class="text-right">Remise</th><th class="text-right">TVA</th><th class="text-right">Total</th></tr></thead>
        <tbody>
            @foreach($sale->lines as $line)
                <tr>
                    <td>{{ $line->product_name }}</td>
                    <td>{{ $line->sku ?? '—' }}</td>
                    <td class="text-right">{{ $line->quantity }}</td>
                    <td class="text-right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ $line->discount_percent > 0 ? $line->discount_percent.'%' : '—' }}</td>
                    <td class="text-right">{{ $line->tax_rate }}%</td>
                    <td class="text-right" style="font-weight:600">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td style="color:#64748b">Total HT</td><td class="text-right" style="font-weight:600">{{ number_format($sale->subtotal_ht, 2, ',', ' ') }}</td></tr>
        <tr><td style="color:#64748b">TVA</td><td class="text-right" style="font-weight:600">{{ number_format($sale->tax_total, 2, ',', ' ') }}</td></tr>
        @if($sale->discount_total > 0)<tr><td style="color:#64748b">Remise</td><td class="text-right" style="font-weight:600;color:#e11d48">-{{ number_format($sale->discount_total, 2, ',', ' ') }}</td></tr>@endif
        <tr class="grand"><td>Total TTC</td><td class="text-right">{{ number_format($sale->total_ttc, 2, ',', ' ') }} {{ $sale->currency }}</td></tr>
    </table>

    @if($sale->payments->isNotEmpty())
        <h3 style="margin:24px 0 8px;font-size:13px">Paiements</h3>
        <table>
            <thead><tr><th>Date</th><th>Mode</th><th class="text-right">Montant</th></tr></thead>
            <tbody>
                @foreach($sale->payments as $p)
                    <tr>
                        <td>{{ optional($p->paid_at)->format('d/m/Y') }}</td>
                        <td>{{ $p->methodLabel() }}</td>
                        <td class="text-right" style="font-weight:600">{{ number_format($p->amount, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($sale->notes)
        <div style="margin-top:24px;padding:12px;background:#f8fafc;border-radius:8px">
            <p style="font-size:11px;color:#64748b;text-transform:uppercase">Notes</p>
            <p style="margin-top:4px">{{ $sale->notes }}</p>
        </div>
    @endif
</body>
</html>
