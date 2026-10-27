<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['title'] }} — {{ $brand['trade_name'] ?? $company->name }}</title>
    <style>
        *{box-sizing:border-box}body{font-family:'Segoe UI',system-ui,sans-serif;font-size:11px;color:#0f172a;margin:24px}
        header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #e2e8f0;padding-bottom:12px;margin-bottom:16px}
        h1{font-size:18px;margin:0}h2{font-size:14px;margin:0 0 4px}.muted{color:#64748b}
        table{width:100%;border-collapse:collapse}th{background:#f1f5f9;text-align:left;padding:6px;font-size:10px;text-transform:uppercase}
        td{padding:5px 6px;border-bottom:1px solid #e2e8f0}.r{text-align:right}tfoot td{font-weight:700;border-top:2px solid #cbd5e1}
        .toolbar{margin-bottom:12px}@media print{.toolbar{display:none}body{margin:0}}
        @page{size:A4 {{ count($report['columns']) > 7 ? 'landscape' : 'portrait' }};margin:12mm}
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>
    <header>
        <div>
            <h1>{{ $brand['trade_name'] ?? $company->name }}</h1>
            <p class="muted">{{ $company->legal_name ?? '' }}</p>
        </div>
        <div style="text-align:right">
            <h2>{{ $report['title'] }}</h2>
            <p class="muted">Du {{ $filters['from']->format('d/m/Y') }} au {{ $filters['to']->format('d/m/Y') }}<br>Édité le {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </header>
    <table>
        <thead><tr>@foreach($report['columns'] as $k => $label)<th class="{{ in_array($k, $report['money']) ? 'r' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>@foreach($report['columns'] as $k => $label)@php $v = $row[$k] ?? ''; @endphp<td class="{{ is_numeric($v) && ! is_string($v) ? 'r' : '' }}">{{ in_array($k, $report['money']) && is_numeric($v) ? number_format((float) $v, 2, ',', ' ') : $v }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($report['columns']) }}" class="muted">Aucune donnée.</td></tr>
            @endforelse
        </tbody>
        @if($report['totals'])
            <tfoot><tr>@foreach($report['columns'] as $k => $label)<td class="r">{{ $loop->first ? 'Total' : (array_key_exists($k, $report['totals']) ? (in_array($k, $report['money']) ? number_format($report['totals'][$k], 2, ',', ' ') : $report['totals'][$k]) : '') }}</td>@endforeach</tr></tfoot>
        @endif
    </table>
    <p class="muted" style="margin-top:8px">Montants en {{ $currency }}.</p>
    <script>if (new URLSearchParams(location.search).get('auto') === '1') window.print();</script>
</body>
</html>
