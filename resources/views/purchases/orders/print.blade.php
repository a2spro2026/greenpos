<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $order->number }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #0f172a; margin: 32px; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color: #64748b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 13px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { font-size: 11px; text-transform: uppercase; color: #64748b; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 280px; margin-left: auto; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .actions { margin-bottom: 20px; }
        @media print { .actions { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer / Enregistrer PDF</button>
        <a href="{{ route('purchases.orders.show', $order) }}">Retour</a>
    </div>
    <h1>Bon de commande {{ $order->number }}</h1>
    <p class="muted">{{ $workspaceCompany->name ?? 'GreenPOS' }} · {{ optional($order->ordered_at)->format('d/m/Y') }} · {{ $order->statusLabel() }}</p>
    <p><strong>Fournisseur :</strong> {{ $order->supplier?->name }}<br>
       <strong>Boutique :</strong> {{ $order->store?->name }}<br>
       <strong>Référence :</strong> {{ $order->reference ?: '—' }}</p>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class="right">Qté</th>
                <th class="right">Prix</th>
                <th class="right">Remise</th>
                <th class="right">TVA</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->lines as $line)
                <tr>
                    <td>{{ $line->product?->name }} <span class="muted">{{ $line->product?->sku }}</span></td>
                    <td class="right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                    <td class="right">{{ number_format($line->unit_price, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($line->discount_percent, 1, ',', ' ') }}%</td>
                    <td class="right">{{ number_format($line->tax_rate, 1, ',', ' ') }}%</td>
                    <td class="right">{{ number_format($line->line_total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div><span>Total HT</span><strong>{{ number_format($order->subtotal_ht, 2, ',', ' ') }}</strong></div>
        <div><span>TVA</span><strong>{{ number_format($order->tax_total, 2, ',', ' ') }}</strong></div>
        <div><span>Total TTC</span><strong>{{ number_format($order->total_ttc, 2, ',', ' ') }} {{ $order->currency }}</strong></div>
    </div>
    @if($order->notes)
        <p class="muted" style="margin-top:24px">Notes : {{ $order->notes }}</p>
    @endif
</body>
</html>
