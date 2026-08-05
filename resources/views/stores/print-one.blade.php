<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $store->name }} — Fiche boutique</title>
    <style>
        body { font-family: system-ui, sans-serif; color: #0f172a; padding: 32px; max-width: 720px; margin: 0 auto; }
        h1 { margin: 0 0 8px; }
        .meta { color: #64748b; font-size: 13px; margin-bottom: 24px; }
        dl { display: grid; grid-template-columns: 160px 1fr; gap: 8px 16px; font-size: 14px; }
        dt { color: #64748b; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>{{ $store->name }}</h1>
    <p class="meta">{{ $company->name }} · {{ $store->statusLabel() }} · {{ now()->format('d/m/Y') }}</p>
    <dl>
        <dt>Code</dt><dd>{{ $store->code ?: '—' }}</dd>
        <dt>Adresse</dt><dd>{{ $store->address ?: '—' }}</dd>
        <dt>Ville</dt><dd>{{ collect([$store->city, $store->region, $store->country])->filter()->implode(', ') }}</dd>
        <dt>Téléphone</dt><dd>{{ $store->phone ?: '—' }}</dd>
        <dt>Email</dt><dd>{{ $store->email ?: '—' }}</dd>
        <dt>Responsable</dt><dd>{{ $store->manager?->name ?: '—' }}</dd>
        <dt>Horaires</dt><dd>{{ $store->openingHoursSummary() }}</dd>
        <dt>Utilisateurs</dt><dd>{{ $store->users_count }}</dd>
        <dt>Produits</dt><dd>{{ $store->metric_products ?? $store->products_count }}</dd>
        <dt>CA</dt><dd>{{ number_format($store->metric_revenue ?? 0, 2, ',', ' ') }} MAD</dd>
    </dl>
</body>
</html>
