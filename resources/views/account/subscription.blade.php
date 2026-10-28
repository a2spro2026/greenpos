@extends('layouts.app')

@section('title', 'Abonnement')
@section('breadcrumb', 'Paramètres / Abonnement')
@section('heading', 'Abonnement & modules')
@section('subtitle', 'Votre formule, ses échéances et les modules activés pour '.$company->name.'.')

@section('content')
    <section class="gp-card mb-6">
        <h2 class="mb-3 text-sm font-bold">Formule</h2>
        @if($subscriptions->isEmpty())
            <p class="text-sm text-gp-muted">Aucun abonnement enregistré pour cette entreprise. Pour toute question de formule, contactez le support {{ config('app.name') }}.</p>
        @else
            <div class="overflow-x-auto"><table class="w-full min-w-[640px] text-sm">
                <thead><tr class="text-left text-xs uppercase text-gp-muted"><th class="py-2">Formule</th><th>Statut</th><th>Cycle</th><th class="text-right">Montant</th><th>Début</th><th>Fin d'essai</th><th>Renouvellement / fin</th></tr></thead>
                <tbody>
                    @foreach($subscriptions as $s)
                        <tr class="border-b border-gp-border/50">
                            <td class="py-2 font-semibold">{{ $s->plan?->name ?? '—' }}</td>
                            <td>{{ $s->status }}</td>
                            <td>{{ $s->billing_cycle === 'yearly' ? 'Annuel' : ($s->billing_cycle === 'monthly' ? 'Mensuel' : $s->billing_cycle) }}</td>
                            <td class="text-right">{{ number_format((float) $s->amount, 2, ',', ' ') }} {{ $s->currency }}</td>
                            <td>{{ optional($s->starts_at)->format('d/m/Y') }}</td>
                            <td>{{ optional($s->trial_ends_at)->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ optional($s->renews_at ?? $s->ends_at)->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <section class="gp-card">
        <h2 class="mb-3 text-sm font-bold">Modules activés ({{ $modules->count() }})</h2>
        <div class="flex flex-wrap gap-2">
            @forelse($modules as $m)
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20">{{ $m['label'] }}</span>
            @empty
                <p class="text-sm text-gp-muted">Aucun module.</p>
            @endforelse
        </div>
        <p class="mt-3 text-xs text-gp-muted">L'ajout d'un module se fait auprès du Super Admin {{ config('app.name') }}.</p>
    </section>
@endsection
