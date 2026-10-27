@extends('layouts.app')

@section('title', 'Conflits de synchronisation')
@section('breadcrumb', 'Ventes / POS / Conflits de synchro')
@section('heading', 'Conflits de synchronisation hors ligne')
@section('subtitle', 'Tickets saisis hors connexion et refusés à la synchronisation (stock, crédit, paiement…). Rien n’est supprimé.')

@section('content')
    @include('pos._nav')
    @if(session('success'))<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

    <nav class="mb-4 flex gap-2 text-sm">
        @foreach(['open' => 'À traiter ('.$openCount.')', 'synced' => 'Synchronisés', 'resolved' => 'Classés', 'all' => 'Tous'] as $k => $l)
            <a href="{{ route('pos.sync-conflicts.index', ['status' => $k]) }}" class="rounded-full px-3 py-1 {{ $status === $k ? 'bg-gp-primary text-white' : 'bg-white ring-1 ring-gp-border' }}">{{ $l }}</a>
        @endforeach
    </nav>

    <article class="gp-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs uppercase text-gp-muted"><th class="px-2 py-2">Saisi le</th><th>Caissier</th><th>Boutique</th><th>Articles</th><th class="text-right">Montant</th><th>Motif</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($conflicts as $c)
                    <tr class="border-b border-gp-border/50 align-top">
                        <td class="px-2 py-2 whitespace-nowrap">{{ optional($c->offline_at ?? $c->created_at)->format('d/m/Y H:i') }}<br><span class="text-[10px] text-gp-muted">{{ $c->client_uuid }}</span></td>
                        <td>{{ $c->user?->name ?? '—' }}</td>
                        <td>{{ $c->store?->name ?? '—' }}</td>
                        <td class="text-xs">
                            @foreach($c->payload['items'] ?? [] as $it)
                                {{ rtrim(rtrim((string) $it['quantity'], '0'), '.') }} × {{ \App\Models\Product::query()->whereKey($it['product_id'])->value('name') ?? ('#'.$it['product_id']) }}<br>
                            @endforeach
                        </td>
                        <td class="text-right whitespace-nowrap">{{ number_format($c->total(), 2, ',', ' ') }}</td>
                        <td class="text-xs text-rose-700">{{ $c->message }} @if($c->attempts > 1)<span class="text-gp-muted">({{ $c->attempts }} essais)</span>@endif</td>
                        <td>{{ \App\Models\PosSyncConflict::STATUSES[$c->status] ?? $c->status }} @if($c->sale)<br><a class="text-gp-primary hover:underline" href="{{ route('pos.tickets.show', $c->sale) }}">{{ $c->sale->number }}</a>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            @if($c->status === 'open')
                                <form method="post" action="{{ route('pos.sync-conflicts.retry', $c) }}" class="inline">@csrf<button class="gp-btn-secondary text-xs">Réessayer</button></form>
                                <form method="post" action="{{ route('pos.sync-conflicts.resolve', $c) }}" class="mt-1 flex gap-1">@csrf<input name="note" placeholder="Note" class="gp-input w-28 text-xs"><button class="gp-btn-secondary text-xs">Classer</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-gp-muted">Aucun conflit.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $conflicts->links() }}</div>
    </article>
@endsection
