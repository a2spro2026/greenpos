<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $return->number }}</title>
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
        .sign { margin-top: 48px; display: flex; justify-content: space-between; font-size: 13px; }
        @media print { .actions { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer / Enregistrer PDF</button>
        <a href="{{ route('purchases.returns.show', $return) }}">Retour</a>
    </div>
    <h1>Bon de retour fournisseur {{ $return->number }}</h1>
    <p class="muted">{{ $workspaceCompany->name ?? config('app.name') }} · {{ optional($return->returned_at)->format('d/m/Y') }} · {{ $return->statusLabel() }}</p>
    <p><strong>Fournisseur :</strong> {{ $return->supplier?->name }}<br>
       <strong>Boutique :</strong> {{ $return->store?->name }}<br>
       <strong>Réception :</strong> {{ $return->receipt?->number ?: '—' }} · <strong>Commande :</strong> {{ $return->order?->number ?: '—' }}<br>
       <strong>Motif :</strong> {{ $return->reason ?: '—' }}</p>
    <table>
        <thead><tr><th>Produit</th><th class="right">Qté</th><th class="right">Prix HT</th><th class="right">TVA %</th><th class="right">Total HT</th></tr></thead>
        <tbody>
        @foreach($return->lines as $line)
            <tr>
                <td>{{ $line->product?->name }}</td>
                <td class="right">{{ number_format((float) $line->quantity, 3, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $line->unit_price, 2, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $line->tax_rate, 2, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $line->line_total, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div><span>Total HT</span><span>{{ number_format((float) $return->total_ht, 2, ',', ' ') }}</span></div>
        <div><span>TVA</span><span>{{ number_format((float) $return->total_tax, 2, ',', ' ') }}</span></div>
        <div><strong>Total TTC</strong><strong>{{ number_format((float) $return->total_ttc, 2, ',', ' ') }}</strong></div>
        @if($return->credit_note_number)
            <div><span>Avoir {{ $return->credit_note_number }}</span><span>{{ number_format((float) $return->credit_note_amount, 2, ',', ' ') }}</span></div>
        @endif
    </div>
    @if($return->notes)<p class="muted">{{ $return->notes }}</p>@endif
    <div class="sign"><span>Remis par : ____________________</span><span>Reçu par le fournisseur : ____________________</span></div>
</body>
</html>
