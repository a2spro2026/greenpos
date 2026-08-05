<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Événement #{{ $event->id }}</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 24px; color: #0f172a; max-width: 800px; }
        h1 { font-size: 18px; }
        .row { display: flex; gap: 24px; margin-bottom: 8px; font-size: 13px; }
        .label { width: 160px; color: #64748b; font-weight: 600; }
        pre { background: #f8fafc; padding: 12px; border-radius: 8px; font-size: 11px; overflow: auto; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>Événement d'audit #{{ $event->id }}</h1>
    <div class="row"><div class="label">Date</div><div>{{ $event->occurred_at->format('d/m/Y H:i:s') }}</div></div>
    <div class="row"><div class="label">Utilisateur</div><div>{{ $event->user?->displayName() ?? 'Système' }}</div></div>
    <div class="row"><div class="label">Action</div><div>{{ $event->actionLabel() }} ({{ $event->module }})</div></div>
    <div class="row"><div class="label">Criticité</div><div>{{ $event->severityLabel() }}</div></div>
    <div class="row"><div class="label">IP / Navigateur</div><div>{{ $event->ip_address }} · {{ $event->browser }} / {{ $event->platform }}</div></div>
    <div class="row"><div class="label">Description</div><div>{{ $event->description }}</div></div>
    <h3>Ancienne valeur</h3>
    <pre>{{ $event->old_values ? json_encode($event->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
    <h3>Nouvelle valeur</h3>
    <pre>{{ $event->new_values ? json_encode($event->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
    @if($event->system_notes)
        <h3>Commentaires système</h3>
        <p>{{ $event->system_notes }}</p>
    @endif
</body>
</html>
