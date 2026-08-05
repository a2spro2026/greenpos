<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Entreprises</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 24px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; color: #64748b; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>Liste des entreprises</h1>
    <p style="color:#64748b;font-size:12px">{{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr><th>Nom</th><th>Secteur</th><th>Pays</th><th>Devise</th><th>Boutiques</th><th>Users</th><th>Statut</th><th>CA</th></tr>
        </thead>
        <tbody>
            @foreach($companies as $company)
                <tr>
                    <td>{{ $company->name }}</td>
                    <td>{{ $company->activity }}</td>
                    <td>{{ $company->country }}</td>
                    <td>{{ $company->currency }}</td>
                    <td>{{ $company->stores_count }}</td>
                    <td>{{ $company->users_count }}</td>
                    <td>{{ $company->statusLabel() }}</td>
                    <td>{{ number_format($company->metric_revenue ?? 0, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
