@extends('layouts.app')

@section('title', 'Livreurs')
@section('breadcrumb', 'Livraison')
@section('heading', 'Livreurs & plateformes')
@section('subtitle', 'Agents internes et externes — commissions.')

@section('actions')
    <a href="{{ route('delivery.create') }}" class="gp-btn-primary">Nouveau livreur</a>
@endsection

@section('content')
    @include('delivery._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-4">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, téléphone…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            <select name="type" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Type</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="active" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>Actifs</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>Inactifs</option>
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($agents->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun livreur</p>
                <a href="{{ route('delivery.create') }}" class="gp-btn-primary mt-5">Ajouter le premier</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Commission</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($agents as $agent)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $agent->initials() }}</span>
                                        <div>
                                            <a href="{{ route('delivery.show', $agent) }}" class="font-semibold hover:text-gp-primary">{{ $agent->name }}</a>
                                            @if($agent->platform_name)<p class="text-xs text-gp-muted">{{ $agent->platform_name }}</p>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $agent->typeLabel() }}</td>
                                <td class="px-4 py-3 text-xs">{{ $agent->phone ?: $agent->email ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ number_format($agent->commission_percent, 1) }}% + {{ number_format($agent->commission_fixed, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3"><span class="gp-badge {{ $agent->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $agent->is_active ? 'Actif' : 'Inactif' }}</span></td>
                                <td class="px-4 py-3 text-xs font-semibold">
                                    <a href="{{ route('delivery.show', $agent) }}" class="text-gp-primary hover:underline">Voir</a>
                                    <a href="{{ route('delivery.edit', $agent) }}" class="ml-2 text-gp-muted hover:underline">Modifier</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3">{{ $agents->links() }}</div>
        @endif
    </section>
@endsection
