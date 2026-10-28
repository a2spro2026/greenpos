@extends('layouts.app')

@section('title', 'Règlements à encaisser')
@section('breadcrumb', 'Ventes / POS / À encaisser')
@section('heading', 'Chèques, effets et règlements à encaisser')
@section('subtitle', 'Règlements différés des tickets POS et des commandes, par date d’échéance.')

@section('content')
    @include('pos._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="gp-card"><p class="text-xs text-gp-muted">Total à encaisser</p><p class="text-xl font-bold">{{ number_format($total, 2, ',', ' ') }} {{ $currency }}</p></div>
        <div class="gp-card"><p class="text-xs text-gp-muted">Dont échu</p><p class="text-xl font-bold text-rose-600">{{ number_format($overdue, 2, ',', ' ') }} {{ $currency }}</p></div>
        <form method="get" class="gp-card flex items-end gap-2">
            <label class="flex-1 text-xs"><span class="gp-label">Mode</span>
                <select name="method" class="gp-select w-full"><option value="">Tous</option>@foreach($methods as $k => $l)<option value="{{ $k }}" @selected(($filters['method'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
            </label>
            <label class="flex-1 text-xs"><span class="gp-label">Échéance ≤</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="gp-input"></label>
            <button class="gp-btn-secondary">OK</button>
        </form>
    </div>

    <div class="gp-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-gp-muted"><tr><th class="py-2">Échéance</th><th>Pièce</th><th>Client</th><th>Mode</th><th>N°</th><th class="text-right">Montant</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($items as $it)
                @php $p = $it['payment']; $isPos = $it['kind'] === 'pos'; @endphp
                <tr class="border-t border-gp-border align-top dark:border-white/10">
                    <td class="py-2 {{ $p->due_date && $p->due_date->isPast() ? 'font-bold text-rose-600' : '' }}">{{ optional($p->due_date)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $it['number'] }} <span class="text-xs text-gp-muted">{{ $isPos ? 'Ticket' : 'Commande' }} · {{ $it['store'] }}</span></td>
                    <td>{{ $it['customer'] ?? '—' }}</td>
                    <td>{{ $methods[$p->method] ?? $p->method }}</td>
                    <td>{{ $p->reference ?? $p->piece_number ?? '—' }}</td>
                    <td class="text-right font-semibold">{{ number_format((float) $p->amount, 2, ',', ' ') }}</td>
                    <td class="whitespace-nowrap">
                        <form method="post" action="{{ $isPos ? route('pos.payments.collect', $p) : route('sales.payments.collect', $p) }}" class="inline">@csrf<button class="gp-btn-primary !px-2 !py-1 text-xs">Encaissé</button></form>
                        <form method="post" action="{{ $isPos ? route('pos.payments.reschedule', $p) : route('sales.payments.reschedule', $p) }}" class="inline-flex items-center gap-1">@csrf<input type="date" name="due_date" required class="gp-input h-8 w-32 text-xs"><button class="gp-btn-secondary !px-2 !py-1 text-xs">Reporter</button></form>
                        <form method="post" action="{{ $isPos ? route('pos.payments.cancel', $p) : route('sales.payments.cancel', $p) }}" class="inline" onsubmit="return confirm('Marquer ce règlement comme impayé / annulé ?')">@csrf<button class="gp-btn-secondary !px-2 !py-1 text-xs !text-rose-600">Impayé</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-gp-muted">Aucun règlement en attente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
