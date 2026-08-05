<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: 'Segoe UI', ui-sans-serif, system-ui, sans-serif; color: #0f172a; margin: 40px; }
        .brand { font-size: 12px; letter-spacing: .18em; text-transform: uppercase; color: #0ea5e9; font-weight: 700; }
        h1 { margin: 8px 0 4px; font-size: 28px; }
        .muted { color: #64748b; font-size: 13px; }
        .grid { display: flex; justify-content: space-between; gap: 24px; margin-top: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 32px; font-size: 13px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 10px 8px; text-align: left; }
        th { font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: .06em; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 260px; margin-left: auto; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .total { font-weight: 700; font-size: 16px; border-top: 2px solid #0f172a; margin-top: 8px; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700; }
        .actions { margin-bottom: 24px; }
        @media print { .actions { display: none; } body { margin: 16px; } }
    </style>
</head>
<body>
<div class="actions">
    <button onclick="window.print()">Imprimer / Enregistrer PDF</button>
    <a href="{{ route('superadmin.invoices.show', $invoice) }}">Retour</a>
</div>
<p class="brand">GreenPOS Enterprise · SaaS Invoice</p>
<h1>{{ $invoice->number }}</h1>
<p class="muted"><span class="badge">{{ $invoice->statusLabel() }}</span> · Émise {{ optional($invoice->issued_on)->format('d/m/Y') }} · Échéance {{ optional($invoice->due_on)->format('d/m/Y') }}</p>
<div class="grid">
    <div>
        <p class="muted">Facturé à</p>
        <p><strong>{{ $invoice->tenant?->name }}</strong><br>
            {{ $invoice->tenant?->email }}<br>
            {{ $invoice->tenant?->city }} {{ $invoice->tenant?->country }}
        </p>
    </div>
    <div>
        <p class="muted">Abonnement</p>
        <p><strong>{{ $invoice->subscription?->plan?->name ?? '—' }}</strong><br>
            Cycle {{ $invoice->subscription?->billing_cycle === 'yearly' ? 'annuel' : 'mensuel' }}
        </p>
    </div>
</div>
<table>
    <thead><tr><th>Description</th><th class="right">Montant</th></tr></thead>
    <tbody>
    @foreach($invoice->line_items ?? [] as $line)
        <tr>
            <td>{{ $line['label'] ?? 'Ligne' }}</td>
            <td class="right">{{ number_format($line['amount'] ?? 0, 2, ',', ' ') }} {{ $invoice->currency }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="totals">
    <div><span>Sous-total</span><span>{{ number_format($invoice->subtotal, 2, ',', ' ') }}</span></div>
    <div><span>TVA</span><span>{{ number_format($invoice->tax, 2, ',', ' ') }}</span></div>
    <div class="total"><span>Total</span><span>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency }}</span></div>
</div>
</body>
</html>
