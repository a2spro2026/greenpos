<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $company->name }}</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 32px; max-width: 720px; margin: 0 auto; }
        dl { display: grid; grid-template-columns: 160px 1fr; gap: 8px 16px; font-size: 14px; }
        dt { color: #64748b; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>{{ $company->name }}</h1>
    <p style="color:#64748b">{{ $company->statusLabel() }} · {{ optional($company->created_at)->format('d/m/Y') }}</p>
    <dl>
        <dt>Raison sociale</dt><dd>{{ $company->legal_name ?: '—' }}</dd>
        <dt>Secteur</dt><dd>{{ $company->activity ?: '—' }}</dd>
        <dt>Pays</dt><dd>{{ $company->country ?: '—' }}</dd>
        <dt>Devise</dt><dd>{{ $company->currency }}</dd>
        <dt>Langue</dt><dd>{{ $company->locale }}</dd>
        <dt>Boutiques</dt><dd>{{ $company->stores_count }}</dd>
        <dt>Utilisateurs</dt><dd>{{ $company->users_count }}</dd>
        <dt>CA</dt><dd>{{ number_format($company->metric_revenue ?? 0, 2, ',', ' ') }}</dd>
    </dl>
</body>
</html>
