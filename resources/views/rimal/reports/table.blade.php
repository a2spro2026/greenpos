@extends('layouts.app')

@section('title', $report['title'])
@section('breadcrumb', 'Rapports / '.$report['title'])
@section('heading', $report['title'])
@section('subtitle', 'Période du '.$filters['from']->format('d/m/Y').' au '.$filters['to']->format('d/m/Y'))

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('reports.export')<a href="{{ route('reports.extra.export', ['type' => $type] + $query) }}" class="gp-btn-secondary">Export Excel</a>@endcan
        @can('reports.print')<a href="{{ route('reports.extra.print', ['type' => $type] + $query) }}" target="_blank" class="gp-btn-secondary">PDF / Imprimer</a>@endcan
    </div>
@endsection

@section('content')
    @include('reports._nav')
    @include('rimal.reports._filters', $type === 'commandes' ? ['withUser' => true] : [])

    <article class="gp-card overflow-x-auto">
        @if(empty($report['rows']))
            <p class="py-6 text-center text-sm text-gp-muted">Aucune donnée sur cette période.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs uppercase text-gp-muted">@foreach($report['columns'] as $k => $label)<th class="px-2 py-2 {{ in_array($k, $report['money']) || array_key_exists($k, $report['totals']) ? 'text-right' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($report['rows'] as $row)
                        <tr class="border-b border-gp-border/50">
                            @foreach($report['columns'] as $k => $label)
                                @php $v = $row[$k] ?? ''; @endphp
                                <td class="px-2 py-1 {{ is_numeric($v) && ! is_string($v) ? 'text-right' : '' }} {{ is_numeric($v) && ! is_string($v) && $v < 0 ? 'text-rose-600' : '' }}">{{ in_array($k, $report['money']) && is_numeric($v) ? number_format((float) $v, 2, ',', ' ') : $v }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                @if($report['totals'])
                    <tfoot><tr class="font-bold">
                        @foreach($report['columns'] as $k => $label)
                            <td class="px-2 py-2 text-right">{{ $loop->first ? 'Total' : (array_key_exists($k, $report['totals']) ? (in_array($k, $report['money']) ? number_format($report['totals'][$k], 2, ',', ' ') : $report['totals'][$k]) : '') }}</td>
                        @endforeach
                    </tr></tfoot>
                @endif
            </table>
        @endif
    </article>
@endsection
