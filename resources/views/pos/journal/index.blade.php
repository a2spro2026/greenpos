@extends('layouts.app')

@section('title', 'Journal de caisse')
@section('breadcrumb', 'Ventes / POS / Journal de caisse')
@section('heading', 'Journal de caisse')
@section('subtitle', 'Opérations en espèces par session : solde initial, ventes, entrées, sorties, solde final.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('pos.journal.print', $filters) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        <a href="{{ route('pos.journal.export', $filters) }}" class="gp-btn-secondary">Export Excel (CSV)</a>
    </div>
@endsection

@section('content')
    @include('pos._nav')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <form method="get" class="gp-card mb-4 grid gap-3 sm:grid-cols-5">
        <label class="block text-xs"><span class="gp-label">Du</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="gp-input"></label>
        <label class="block text-xs"><span class="gp-label">Au</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="gp-input"></label>
        <label class="block text-xs"><span class="gp-label">Utilisateur</span>
            <select name="user_id" class="gp-select w-full"><option value="">Tous</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach</select>
        </label>
        <label class="block text-xs"><span class="gp-label">Boutique</span>
            <select name="store_id" class="gp-select w-full"><option value="">Toutes</option>@foreach($stores as $st)<option value="{{ $st->id }}" @selected(($filters['store_id'] ?? '') == $st->id)>{{ $st->name }}</option>@endforeach</select>
        </label>
        <div class="flex items-end"><button class="gp-btn-primary w-full">Filtrer</button></div>
    </form>

    @if($openSession)
        <form method="post" action="{{ route('pos.cash-movements.store') }}" class="gp-card mb-4 grid gap-3 sm:grid-cols-5">
            @csrf
            <p class="text-sm font-bold sm:col-span-5">Mouvement d’espèces — {{ $openSession->number }}</p>
            <select name="direction" class="gp-select"><option value="in">Entrée (encaissement)</option><option value="out">Sortie (décaissement)</option></select>
            <input type="number" name="amount" min="0.01" step="0.01" required class="gp-input" placeholder="Montant">
            <input type="text" name="reason" maxlength="200" required class="gp-input sm:col-span-2" placeholder="Motif (ex. achat petit matériel, apport monnaie)">
            <button class="gp-btn-secondary">Enregistrer</button>
        </form>
    @endif

    @forelse($rows as $row)
        @php $s = $row['session']; @endphp
        <article class="gp-card mb-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold">{{ $s->number }} · {{ $s->store?->name }}</h2>
                    <p class="text-xs text-gp-muted">Ouverte {{ optional($s->opened_at)->format('d/m/Y H:i') }} par {{ $s->opener?->name ?? '—' }}@if($s->closed_at) · clôturée {{ $s->closed_at->format('d/m/Y H:i') }} par {{ $s->closer?->name ?? '—' }}@else · <span class="font-semibold text-emerald-600">ouverte</span>@endif</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold dark:bg-white/10">Solde initial {{ number_format($row['opening'], 2, ',', ' ') }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold dark:bg-white/10">Solde final {{ number_format($row['final'], 2, ',', ' ') }}</span>
                    @if($row['counted'] !== null)
                        <span class="rounded-full px-3 py-1 font-semibold {{ abs((float) $row['difference']) < 0.01 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">Compté {{ number_format($row['counted'], 2, ',', ' ') }} · écart {{ number_format((float) $row['difference'], 2, ',', ' ') }}</span>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gp-muted"><tr><th class="py-2">Heure</th><th>Opération</th><th>Utilisateur</th><th class="text-right">Entrée</th><th class="text-right">Sortie</th><th class="text-right">Solde</th></tr></thead>
                    <tbody>
                        @foreach($row['operations'] as $op)
                            <tr class="border-t border-gp-border dark:border-white/10">
                                <td class="py-1.5">{{ optional($op['at'])->format('d/m H:i') }}</td>
                                <td>{{ $op['label'] }}</td>
                                <td>{{ $op['user'] ?? '—' }}</td>
                                <td class="text-right">{{ $op['in'] > 0 ? number_format($op['in'], 2, ',', ' ') : '' }}</td>
                                <td class="text-right">{{ $op['out'] > 0 ? number_format($op['out'], 2, ',', ' ') : '' }}</td>
                                <td class="text-right font-semibold">{{ number_format($op['balance'], 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @empty
        <p class="gp-card text-sm text-gp-muted">Aucune session sur cette période.</p>
    @endforelse
@endsection
