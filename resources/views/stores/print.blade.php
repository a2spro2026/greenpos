<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Boutiques — {{ $company->name }}</title>
    <style>
        body { font-family: system-ui, sans-serif; color: #0f172a; padding: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #64748b; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; letter-spacing: .04em; color: #64748b; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>Liste des boutiques</h1>
    <p class="meta">{{ $company->name }} · {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Nom</th><th>Ville</th><th>Responsable</th><th>Téléphone</th><th>Statut</th><th>Users</th><th>Produits</th><th>CA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stores as $store)
                <tr>
                    <td>{{ $store->name }}</td>
                    <td>{{ $store->city }}</td>
                    <td>{{ $store->manager?->name }}</td>
                    <td>{{ $store->phone }}</td>
                    <td>{{ $store->statusLabel() }}</td>
                    <td>{{ $store->users_count }}</td>
                    <td>{{ $store->metric_products ?? $store->products_count }}</td>
                    <td>{{ number_format($store->metric_revenue ?? 0, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
