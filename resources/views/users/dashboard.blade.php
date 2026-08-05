@extends('layouts.app')

@section('title', 'Utilisateurs')
@section('breadcrumb', 'Administration / Utilisateurs')
@section('heading', 'Dashboard Utilisateurs')
@section('subtitle', 'Pilotage des comptes, accès et activité des collaborateurs.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('users.export')
            <a href="{{ route('users.export') }}" class="gp-btn-secondary">Exporter</a>
        @endcan
        @can('users.create')
            <a href="{{ route('users.create') }}" class="gp-btn-primary">Nouvel utilisateur</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('users._nav')

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Total</p><p class="mt-2 text-3xl font-bold">{{ $stats['total'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Actifs</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['active'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Inactifs</p><p class="mt-2 text-3xl font-bold text-slate-500">{{ $stats['inactive'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Nouveaux (30j)</p><p class="mt-2 text-3xl font-bold text-sky-600">{{ $stats['new'] }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Invitations</p><p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['invited'] }}</p></article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Connexions (7 jours)</h2>
            <canvas id="logins-chart" height="140"></canvas>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Dernières connexions</h2></div>
            @if($recentLogins->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune connexion enregistrée.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($recentLogins as $login)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $login->user?->initials() ?? '?' }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $login->user?->displayName() ?? '—' }}</p>
                                    <p class="text-xs text-gp-muted">{{ $login->device ?? '—' }} · {{ $login->ip_address ?? '—' }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-gp-muted">{{ optional($login->logged_in_at)->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <section class="mb-6 grid gap-4 xl:grid-cols-2">
        <article class="gp-card overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-gp-border px-5 py-4 dark:border-white/10">
                <h2 class="text-sm font-bold">Utilisateurs récents</h2>
                <a href="{{ route('users.index') }}" class="text-sm font-semibold text-gp-primary hover:underline">Tout voir</a>
            </div>
            <ul class="divide-y divide-gp-border dark:divide-white/10">
                @forelse($recentUsers as $u)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <div class="flex items-center gap-3">
                            @if($u->photoUrl())
                                <img src="{{ $u->photoUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $u->initials() }}</span>
                            @endif
                            <div>
                                <a href="{{ route('users.show', $u) }}" class="font-semibold text-gp-primary hover:underline">{{ $u->displayName() }}</a>
                                <p class="text-xs text-gp-muted">{{ $u->email }}</p>
                            </div>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->statusColor() }}">{{ $u->statusLabel() }}</span>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-gp-muted">Aucun utilisateur.</li>
                @endforelse
            </ul>
        </article>
        <article class="gp-card overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Activité récente</h2></div>
            @if($recentActivity->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gp-muted">Aucune activité.</div>
            @else
                <ul class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($recentActivity as $log)
                        <li class="px-5 py-3 text-sm">
                            <p>{{ $log->message }}</p>
                            <p class="text-xs text-gp-muted">{{ $log->actor?->displayName() ?? 'Système' }} · {{ $log->created_at->diffForHumans() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    @can('users.invite')
        <section class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Inviter un collaborateur</h2>
            <form method="POST" action="{{ route('users.invite') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[220px] flex-1">
                    <label class="gp-label">Email</label>
                    <input type="email" name="email" required class="gp-input w-full" placeholder="collaborateur@entreprise.com">
                </div>
                <div class="w-48">
                    <label class="gp-label">Rôle</label>
                    <select name="role" class="gp-select w-full">
                        @foreach(\App\Models\User::ROLES as $k => $v)
                            @if($k !== 'owner')
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button class="gp-btn-primary">Préparer l'invitation</button>
            </form>
            <p class="mt-2 text-xs text-gp-muted">L'envoi email sera branché ultérieurement. L'invitation est enregistrée et traçable.</p>
        </section>
    @endcan

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const isDark = document.documentElement.classList.contains('dark');
        const tick = isDark ? '#94a3b8' : '#64748b';
        const logins = @json($logins);
        new Chart(document.getElementById('logins-chart'), {
            type: 'bar',
            data: { labels: logins.map(d => d.label), datasets: [{ data: logins.map(d => d.count), backgroundColor: 'rgba(22,163,74,.65)', borderRadius: 4 }] },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick } }, y: { ticks: { color: tick }, beginAtZero: true } } }
        });
    </script>
@endsection
