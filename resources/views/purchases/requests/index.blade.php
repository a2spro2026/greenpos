@extends('layouts.app')

@section('title', 'Demandes d’achat')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Demandes d’achat')
@section('subtitle', 'Besoins internes avant conversion en bon de commande.')

@section('actions')
    @can('purchases.create')
        <a href="{{ route('purchases.requests.create') }}" class="gp-btn-primary">Nouvelle demande</a>
    @endcan
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="gp-card overflow-hidden p-0">
        @if($requests->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucune demande</p>
                <p class="mt-2 text-sm text-gp-muted">Créez une demande pour formaliser un besoin d’approvisionnement.</p>
                <a href="{{ route('purchases.requests.create') }}" class="gp-btn-primary mt-5">Créer une demande</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Titre</th>
                            <th class="px-4 py-3">Boutique</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Demandeur</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($requests as $req)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('purchases.requests.show', $req) }}" class="hover:text-gp-primary">{{ $req->number }}</a></td>
                                <td class="px-4 py-3">{{ $req->title }}</td>
                                <td class="px-4 py-3">{{ $req->store?->name }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $req->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-gp-muted">{{ $req->requester?->name }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ $req->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $requests->links() }}</div>
        @endif
    </section>
@endsection
