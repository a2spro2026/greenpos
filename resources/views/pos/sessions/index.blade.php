@extends('layouts.app')

@section('title', 'Sessions caisse')
@section('breadcrumb', 'Ventes / POS / Sessions')
@section('heading', 'Ouverture / Fermeture')
@section('subtitle', 'Gestion du fond de caisse et historique des sessions.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @if($current)
            @can('pos.close')
                <a href="{{ route('pos.sessions.close.form', $current) }}" class="gp-btn-secondary">Clôturer {{ $current->number }}</a>
            @endcan
            @can('pos.sell')
                <a href="{{ route('pos.terminal') }}" class="gp-btn-primary">Continuer la caisse</a>
            @endcan
        @else
            @can('pos.open')
                <a href="{{ route('pos.sessions.create') }}" class="gp-btn-primary">Ouvrir une caisse</a>
            @endcan
        @endif
    </div>
@endsection

@section('content')
    @include('pos._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif

    @if($current)
        <article class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">Caisse ouverte</p>
            <p class="mt-1 text-2xl font-bold">{{ $current->number }}</p>
            <p class="mt-1 text-sm text-gp-muted">Fond : {{ number_format($current->opening_float, 2, ',', ' ') }} · Ouverte le {{ optional($current->opened_at)->format('d/m/Y H:i') }}</p>
        </article>
    @endif

    <div class="gp-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="gp-table">
                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Boutique</th>
                        <th>Ouverture</th>
                        <th>Clôture</th>
                        <th class="text-right">Ventes</th>
                        <th class="text-right">Écart</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td class="font-semibold">{{ $session->number }}</td>
                            <td>{{ $session->store?->name }}</td>
                            <td class="text-sm text-gp-muted">{{ optional($session->opened_at)->format('d/m/Y H:i') }}<br><span class="text-xs">{{ $session->opener?->name }}</span></td>
                            <td class="text-sm text-gp-muted">{{ optional($session->closed_at)->format('d/m/Y H:i') ?: '—' }}<br><span class="text-xs">{{ $session->closer?->name }}</span></td>
                            <td class="text-right font-bold">{{ number_format($session->total_sales, 2, ',', ' ') }}</td>
                            <td class="text-right {{ abs((float) $session->cash_difference) > 0.01 ? 'text-amber-600 font-bold' : '' }}">
                                {{ $session->status === 'closed' ? number_format($session->cash_difference, 2, ',', ' ') : '—' }}
                            </td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $session->isOpen() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $session->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('pos.sessions.show', $session) }}" class="text-sm font-semibold text-gp-primary hover:underline">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gp-muted">Aucune session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $sessions->links() }}</div>
        @endif
    </div>
@endsection
