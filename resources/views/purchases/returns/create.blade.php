@extends('layouts.app')

@section('title', 'Nouveau retour fournisseur')
@section('breadcrumb', 'Approvisionnement / Retours fournisseur')
@section('heading', 'Nouveau retour fournisseur')
@section('subtitle', '1. Choisissez la réception · 2. Indiquez les quantités à renvoyer · 3. Validez pour sortir le stock.')

@section('content')
    @include('purchases._nav')

    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="get" class="gp-card mb-4 flex flex-wrap items-end gap-3">
        <label class="min-w-0 flex-1 text-sm font-semibold">Réception validée
            <select name="receipt" class="gp-input mt-1 w-full" onchange="this.form.submit()">
                <option value="">— Choisir une réception —</option>
                @foreach($receipts as $rc)
                    <option value="{{ $rc->id }}" @selected($receipt?->id === $rc->id)>{{ $rc->number }} · {{ $rc->order?->supplier?->name }} · {{ optional($rc->received_at)->format('d/m/Y') }}</option>
                @endforeach
            </select>
        </label>
        <button class="gp-btn-secondary !w-auto">Charger</button>
    </form>

    @if(! $receipt)
        <div class="gp-card px-6 py-12 text-center text-sm text-gp-muted">
            @if($receipts->isEmpty())
                Aucune réception validée pour l’instant. Un retour se fait à partir d’une marchandise réellement reçue.
                <div><a href="{{ route('purchases.receipts.index') }}" class="gp-btn-primary mt-4">Voir les réceptions</a></div>
            @else
                Sélectionnez la réception concernée pour afficher les produits retournables.
            @endif
        </div>
    @else
        <form method="post" action="{{ route('purchases.returns.store') }}" class="grid gap-4 lg:grid-cols-3">
            @csrf
            <input type="hidden" name="receipt_id" value="{{ $receipt->id }}">
            <section class="gp-card overflow-hidden p-0 lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase text-gp-muted">
                            <tr>
                                <th class="px-4 py-3 text-left">Produit</th>
                                <th class="px-4 py-3 text-right">Reçu</th>
                                <th class="px-4 py-3 text-right">Déjà retourné</th>
                                <th class="px-4 py-3 text-right">Prix HT</th>
                                <th class="px-4 py-3 text-right">À retourner</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gp-border dark:divide-white/10">
                            @foreach($lines as $line)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">{{ $line->product?->name }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $line->quantity, 3, ',', ' ') }}</td>
                                    <td class="px-4 py-3 text-right text-gp-muted">{{ number_format($line->already_returned, 3, ',', ' ') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) ($line->orderLine?->unit_price ?? 0), 2, ',', ' ') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" step="0.001" min="0" max="{{ $line->returnable }}" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id) }}" class="gp-input ml-auto w-28 text-right" placeholder="max {{ rtrim(rtrim(number_format($line->returnable, 3, '.', ''), '0'), '.') }}" @disabled($line->returnable <= 0)>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
            <aside class="gp-card space-y-3 text-sm">
                <p><span class="text-gp-muted">Fournisseur :</span> <strong>{{ $receipt->order?->supplier?->name }}</strong></p>
                <p><span class="text-gp-muted">Boutique :</span> {{ $receipt->store?->name }}</p>
                <label class="block font-semibold">Motif
                    <select name="reason" class="gp-input mt-1 w-full">
                        @foreach($reasons as $r)<option @selected(old('reason') === $r)>{{ $r }}</option>@endforeach
                    </select>
                </label>
                <label class="block font-semibold">Date du retour
                    <input type="date" name="returned_at" value="{{ old('returned_at', now()->toDateString()) }}" class="gp-input mt-1 w-full">
                </label>
                <label class="block font-semibold">Notes
                    <textarea name="notes" rows="3" class="gp-input mt-1 w-full">{{ old('notes') }}</textarea>
                </label>
                <button class="gp-btn-primary w-full">Créer le retour (brouillon)</button>
                <p class="text-xs text-gp-muted">Le stock ne bouge qu’à la validation du retour.</p>
            </aside>
        </form>
    @endif
@endsection
