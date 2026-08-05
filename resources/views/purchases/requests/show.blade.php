@extends('layouts.app')

@section('title', $request->number)
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', $request->title)
@section('subtitle', $request->number.' · '.$request->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('purchases.requests.index') }}" class="gp-btn-secondary">Retour</a>
        @if($request->status === 'draft')
            <form method="post" action="{{ route('purchases.requests.submit', $request) }}">@csrf<button class="gp-btn-primary">Soumettre</button></form>
        @endif
        @if(in_array($request->status, ['draft', 'submitted'], true))
            @can('purchases.update')
                <form method="post" action="{{ route('purchases.requests.approve', $request) }}">@csrf<button class="gp-btn-secondary">Approuver</button></form>
            @endcan
        @endif
    </div>
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="mb-4 grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2">
            <div class="mb-4 text-sm text-gp-muted">{{ $request->store?->name }} · {{ $request->requester?->name }} · {{ $request->created_at?->format('d/m/Y') }}</div>
            @if($request->notes)<p class="mb-4 text-sm">{{ $request->notes }}</p>@endif
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-gp-muted"><tr><th class="px-2 py-2 text-left">Produit</th><th class="px-2 py-2 text-right">Qté</th><th class="px-2 py-2 text-left">Note</th></tr></thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($request->lines as $line)
                        <tr>
                            <td class="px-2 py-2 font-semibold">{{ $line->product?->name }}</td>
                            <td class="px-2 py-2 text-right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                            <td class="px-2 py-2 text-gp-muted">{{ $line->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </article>
        <aside class="gp-card space-y-3">
            @if($request->convertedOrder)
                <p class="text-sm">Convertie en <a class="font-semibold text-gp-primary hover:underline" href="{{ route('purchases.orders.show', $request->convertedOrder) }}">{{ $request->convertedOrder->number }}</a></p>
            @elseif(in_array($request->status, ['approved', 'submitted'], true))
                @can('purchases.create')
                    <form method="post" action="{{ route('purchases.requests.convert', $request) }}" class="space-y-3">
                        @csrf
                        <label class="block text-sm">
                            <span class="mb-1.5 block font-semibold">Convertir vers fournisseur</span>
                            <select name="supplier_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="gp-btn-primary w-full">Créer le BC</button>
                    </form>
                @endcan
            @else
                <p class="text-sm text-gp-muted">Soumettez ou approuvez pour convertir en commande.</p>
            @endif
        </aside>
    </section>
@endsection
