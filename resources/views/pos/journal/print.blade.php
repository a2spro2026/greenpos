<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal de caisse — {{ $company?->name }}</title>
    <style>
        body { font-family: system-ui, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 16px; margin: 0 0 4px; } h2 { font-size: 13px; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; } th, td { border-bottom: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        .r { text-align: right; } .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Journal de caisse — {{ $company?->name }}</h1>
    <p class="muted">Période : {{ $filters['from'] ?? '…' }} → {{ $filters['to'] ?? '…' }} · imprimé le {{ now()->format('d/m/Y H:i') }}</p>
    @foreach($rows as $row)
        @php $s = $row['session']; @endphp
        <h2>{{ $s->number }} · {{ $s->store?->name }} — initial {{ number_format($row['opening'], 2, ',', ' ') }} · final {{ number_format($row['final'], 2, ',', ' ') }}@if($row['counted'] !== null) · compté {{ number_format($row['counted'], 2, ',', ' ') }} (écart {{ number_format((float) $row['difference'], 2, ',', ' ') }})@endif</h2>
        <table>
            <tr><th>Heure</th><th>Opération</th><th>Utilisateur</th><th class="r">Entrée</th><th class="r">Sortie</th><th class="r">Solde</th></tr>
            @foreach($row['operations'] as $op)
                <tr><td>{{ optional($op['at'])->format('d/m H:i') }}</td><td>{{ $op['label'] }}</td><td>{{ $op['user'] }}</td><td class="r">{{ $op['in'] > 0 ? number_format($op['in'], 2, ',', ' ') : '' }}</td><td class="r">{{ $op['out'] > 0 ? number_format($op['out'], 2, ',', ' ') : '' }}</td><td class="r">{{ number_format($op['balance'], 2, ',', ' ') }}</td></tr>
            @endforeach
        </table>
    @endforeach
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>
</body>
</html>
