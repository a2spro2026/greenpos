<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Audit PDF</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; padding: 28px; color: #0f172a; }
        h1 { font-size: 22px; margin: 0 0 6px; color: #166534; }
        .meta { color: #64748b; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; font-family: system-ui, sans-serif; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #ecfdf5; font-size: 10px; text-transform: uppercase; color: #166534; }
        @media print { button { display: none; } @page { margin: 12mm; } }
    </style>
</head>
<body onload="window.print()">
    <button onclick="window.print()">Imprimer / Enregistrer PDF</button>
    <h1>Export PDF — Journal d'audit</h1>
    <p class="meta">GreenPOS · Généré le {{ now()->format('d/m/Y à H:i') }} · {{ $events->count() }} lignes</p>
    <table>
        <thead>
            <tr>
                <th>Date/Heure</th><th>Utilisateur</th><th>Entreprise</th><th>Module</th>
                <th>Action</th><th>Description</th><th>IP</th><th>Niveau</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $e)
                <tr>
                    <td>{{ $e->occurred_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $e->user?->displayName() ?? 'Système' }}</td>
                    <td>{{ $e->company?->name }}</td>
                    <td>{{ $e->module }}</td>
                    <td>{{ $e->actionLabel() }}</td>
                    <td>{{ $e->description }}</td>
                    <td>{{ $e->ip_address }}</td>
                    <td>{{ $e->severityLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
