@extends('layouts.app')

@section('title', 'Retours fournisseur')
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Retours fournisseur')
@section('subtitle', 'Marchandise renvoyée au fournisseur, sortie de stock et suivi des avoirs.')

@section('actions')
    @can('purchases.receive')
        <a href="{{ route('purchases.returns.create') }}" class="gp-btn-primary">Nouveau retour</a>
    @endcan
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach(\App\Models\SupplierReturn::STATUSES as $key => $label)
            <a href="{{ route('purchases.returns.index', ['status' => $status === $key ? null : $key]) }}" class="gp-card block p-4 {{ $status === $key ? 'ring-2 ring-gp-primary' : '' }}">
                <p class="text-xs font-semibold uppercase text-gp-muted">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold">{{ (int) ($counts[$key]->c ?? 0) }}</p>
                <p class="text-xs text-gp-muted">{{ number_format((float) ($counts[$key]->t ?? 0), 2, ',', ' ') }} TTC</p>
            </a>
        @endforeach
    </section>

    <form method="get" class="mb-4 flex flex-wrap gap-2">
        <select name="supplier_id" class="gp-input w-auto min-w-0">
            <option value="">Tous les fournisseurs</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected((string) $supplierId === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="status" class="gp-input w-auto min-w-0">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\SupplierReturn::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="gp-btn-secondary !w-auto">Filtrer</button>
    </form>

    <section class="gp-card overflow-hidden p-0">
        @if($returns->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun retour fournisseur</p>
                <p class="mx-auto mt-2 max-w-md text-sm text-gp-muted">Un produit reçu est défectueux, en surplus ou mal livré ? Créez un retour à partir d’une réception validée : le stock sera sorti à la validation, puis vous enregistrez l’avoir du fournisseur.</p>
                @can('purchases.receive')
                    <a href="{{ route('purchases.returns.create') }}" class="gp-btn-primary mt-5">Créer un retour</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Fournisseur</th>
                            <th class="px-4 py-3">Réception</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Motif</th>
                            <th class="px-4 py-3 text-right">Total TTC</th>
                            <th class="px-4 py-3">Avoir</th>
                            <th class="px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($returns as $r)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('purchases.returns.show', $r) }}" class="hover:text-gp-primary">{{ $r->number }}</a></td>
                                <td class="px-4 py-3">{{ $r->supplier?->name ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $r->receipt?->number ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gp-muted">{{ optional($r->returned_at)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gp-muted">{{ $r->reason ?: '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $r->total_ttc, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-xs">{{ $r->credit_note_number ?: '—' }}</td>
                                <td class="px-4 py-3"><span class="gp-badge">{{ $r->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $returns->links() }}</div>
        @endif
    </section>
@endsection
