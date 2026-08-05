@extends('layouts.superadmin')
@section('title', 'Journal global')
@section('breadcrumb', 'Platform / Journal')
@section('heading', 'Journal global')
@section('content')
<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="sa-kpi"><p class="text-xs uppercase tracking-wide text-slate-500">Connexions 24h</p><p class="mt-2 text-3xl font-bold text-sky-300">{{ $stats['logins_24h'] }}</p></article>
    <article class="sa-kpi"><p class="text-xs uppercase tracking-wide text-slate-500">Erreurs 7j</p><p class="mt-2 text-3xl font-bold text-amber-300">{{ $stats['errors_7d'] }}</p></article>
    <article class="sa-kpi"><p class="text-xs uppercase tracking-wide text-slate-500">Incidents 7j</p><p class="mt-2 text-3xl font-bold text-rose-300">{{ $stats['incidents_7d'] }}</p></article>
    <article class="sa-kpi"><p class="text-xs uppercase tracking-wide text-slate-500">Stats globales</p><p class="mt-2 text-sm font-semibold text-white">{{ $stats['tenants'] }} clients · {{ $stats['users'] }} users · {{ $stats['subs'] }} abos</p>
        @if($stats['latest_snapshot'])
            <p class="mt-1 text-[11px] text-slate-500">Santé {{ strtoupper($stats['latest_snapshot']->overall_status) }}</p>
        @endif
    </article>
</section>

<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="sa-input max-w-xs">
    <select name="category" class="sa-select max-w-[12rem]">
        <option value="">Toutes catégories</option>
        @foreach($categories as $k => $v)
            <option value="{{ $k }}" {{ ($filters['category'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <select name="severity" class="sa-select max-w-[10rem]">
        <option value="">Sévérité</option>
        <option value="info" {{ ($filters['severity'] ?? '') === 'info' ? 'selected' : '' }}>Info</option>
        <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>Warning</option>
        <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
    </select>
    <button class="sa-btn sa-btn-ghost">Filtrer</button>
</form>

<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead><tr><th>Quand</th><th>Catégorie</th><th>Sévérité</th><th>Événement</th><th>Client</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($events as $e)
                <tr>
                    <td class="whitespace-nowrap text-slate-400">{{ $e->occurred_at->format('d/m/Y H:i') }}</td>
                    <td class="text-slate-300">{{ $e->categoryLabel() }}</td>
                    <td><span class="sa-badge {{ $e->severityColor() }}">{{ $e->severity }}</span></td>
                    <td>
                        <p class="font-semibold text-white">{{ $e->title }}</p>
                        @if($e->body)<p class="text-xs text-slate-500">{{ $e->body }}</p>@endif
                    </td>
                    <td class="text-slate-400">{{ $e->tenant?->name ?? '—' }}</td>
                    <td class="sa-mono text-xs text-slate-500">{{ $e->ip_address ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-16 text-center text-slate-500">Aucun événement</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($events->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $events->links() }}</div>@endif
</div>
@endsection
