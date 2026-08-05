<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $supplier->name }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 32px; color: #0f172a; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color: #64748b; font-size: 13px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; }
        .label { font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .actions { margin-bottom: 20px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer / PDF</button>
        <a href="{{ route('suppliers.show', $supplier) }}">Retour</a>
    </div>
    <h1>{{ $supplier->name }}</h1>
    <p class="muted">{{ $supplier->code }} · {{ $supplier->statusLabel() }} · {{ $workspaceCompany->name ?? 'GreenPOS' }}</p>
    <div class="grid">
        <div><div class="label">Société</div>{{ $supplier->company_name ?: '—' }}</div>
        <div><div class="label">Catégorie</div>{{ $supplier->categoryLabel() }}</div>
        <div><div class="label">Email</div>{{ $supplier->email ?: '—' }}</div>
        <div><div class="label">Téléphone</div>{{ $supplier->phone ?: '—' }}</div>
        <div><div class="label">Adresse</div>{{ $supplier->address ?: '—' }}</div>
        <div><div class="label">Ville / Pays</div>{{ collect([$supplier->city, $supplier->country])->filter()->implode(', ') ?: '—' }}</div>
        <div><div class="label">Conditions</div>{{ $supplier->payment_terms ?: '—' }}</div>
        <div><div class="label">N° fiscal</div>{{ $supplier->tax_id ?: '—' }}</div>
        <div><div class="label">Produits liés</div>{{ $supplier->products_count }}</div>
        <div><div class="label">Commandes</div>{{ $supplier->purchase_orders_count }}</div>
    </div>
    @if($supplier->notes)
        <p class="muted" style="margin-top:24px">Notes : {{ $supplier->notes }}</p>
    @endif
</body>
</html>
