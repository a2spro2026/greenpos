<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal d'audit</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 24px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #64748b; font-size: 12px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; color: #64748b; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; background: #e2e8f0; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>Journal d'audit GreenPOS</h1>
    <p class="meta">{{ now()->format('d/m/Y H:i') }} · {{ $events->count() }} événement(s)</p>
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Heure</th><th>Utilisateur</th><th>Module</th><th>Action</th>
                <th>Élément</th><th>IP</th><th>Criticité</th><th>Résultat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $e)
                <tr>
                    <td>{{ $e->occurred_at->format('d/m/Y') }}</td>
                    <td>{{ $e->occurred_at->format('H:i:s') }}</td>
                    <td>{{ $e->user?->displayName() ?? 'Système' }}</td>
                    <td>{{ $e->module }}</td>
                    <td>{{ $e->actionLabel() }}</td>
                    <td>{{ $e->subject_label ?: '—' }}</td>
                    <td>{{ $e->ip_address }}</td>
                    <td><span class="badge">{{ $e->severityLabel() }}</span></td>
                    <td>{{ $e->resultLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
