@extends('layouts.app')

@section('title', $production->number)
@section('breadcrumb', 'Opérations / Production')
@section('heading', $production->number)
@section('subtitle', $production->product?->name)

@section('actions')
    <div class="flex flex-wrap gap-2">
        @if($production->isEditable())
            <a href="{{ route('ops.production.edit', $production) }}" class="gp-btn-secondary">Modifier</a>
            <form method="post" action="{{ route('ops.production.validate', $production) }}" onsubmit="return confirm('Valider et consommer les composants ?')">
                @csrf
                <button class="gp-btn-primary">Valider</button>
            </form>
        @endif
        <a href="{{ route('ops.production.index') }}" class="gp-btn-secondary">Liste</a>
    </div>
@endsection

@section('content')
    @include('production._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Statut</p><p class="mt-1"><span class="gp-badge {{ $production->statusColor() }}">{{ $production->statusLabel() }}</span></p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Quantité</p><p class="mt-1 text-xl font-bold">{{ number_format($production->quantity, 3, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Coût unitaire</p><p class="mt-1 text-xl font-bold">{{ number_format($production->unit_cost, 2, ',', ' ') }}</p></div>
        <div class="gp-card"><p class="text-xs font-semibold uppercase text-gp-muted">Coût total</p><p class="mt-1 text-xl font-bold">{{ number_format($production->total_cost, 2, ',', ' ') }}</p></div>
    </div>

    <section class="gp-card mb-4">
        <h2 class="mb-3 text-sm font-bold">Nomenclature prévue (BOM)</h2>
        @if($bom->isEmpty())
            <p class="text-sm text-gp-muted">Aucune nomenclature. <a href="{{ route('ops.production.bom', ['product_id' => $production->product_id]) }}" class="text-gp-primary font-semibold hover:underline">Définir la BOM</a></p>
        @else
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-gp-muted"><tr><th class="py-2 text-left">Composant</th><th class="py-2 text-left">Qté / unité</th><th class="py-2 text-left">Besoin</th></tr></thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($bom as $row)
                        <tr>
                            <td class="py-2">{{ $row->component?->name }}</td>
                            <td class="py-2">{{ number_format($row->quantity, 3, ',', ' ') }}</td>
                            <td class="py-2 font-semibold">{{ number_format((float)$row->quantity * (float)$production->quantity, 3, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if($production->consumptions->isNotEmpty())
        <section class="gp-card">
            <h2 class="mb-3 text-sm font-bold">Consommations enregistrées</h2>
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-gp-muted"><tr><th class="py-2 text-left">Composant</th><th class="py-2 text-left">Qté</th><th class="py-2 text-left">Coût</th></tr></thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($production->consumptions as $c)
                        <tr>
                            <td class="py-2">{{ $c->product?->name }}</td>
                            <td class="py-2">{{ number_format($c->quantity, 3, ',', ' ') }}</td>
                            <td class="py-2">{{ number_format($c->total_cost, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
