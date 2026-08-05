@extends('layouts.app')

@section('title', 'Retour — ' . $sale->number)
@section('breadcrumb', 'Ventes / ' . $sale->number . ' / Retour')
@section('heading', 'Retour de vente')
@section('subtitle', $sale->number . ' — sélectionnez les produits à retourner.')

@section('content')
    @include('sales._nav')

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales.return.store', $sale) }}">
        @csrf

        <section class="gp-card mb-6">
            <h2 class="mb-4 text-sm font-bold">Motif du retour</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="gp-label">Motif *</label>
                    <select name="reason" class="gp-select w-full" required>
                        <option value="Défectueux">Défectueux</option>
                        <option value="Erreur de commande">Erreur de commande</option>
                        <option value="Non conforme">Non conforme</option>
                        <option value="Client insatisfait">Client insatisfait</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="gp-label">Remettre en stock ?</label>
                    <select name="restock" class="gp-select w-full">
                        <option value="1">Oui — réintégrer le stock</option>
                        <option value="0">Non</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="gp-label">Notes</label>
                    <textarea name="notes" class="gp-input w-full" rows="2" placeholder="Détails optionnels…"></textarea>
                </div>
            </div>
        </section>

        <section class="gp-card mb-6 overflow-hidden p-0">
            <div class="border-b border-gp-border px-5 py-4 dark:border-white/10"><h2 class="text-sm font-bold">Produits à retourner</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Produit</th>
                            <th class="px-4 py-3 text-right">Qté vendue</th>
                            <th class="px-4 py-3 text-right">Déjà retourné</th>
                            <th class="px-4 py-3 text-right">Max retournable</th>
                            <th class="px-4 py-3 text-right w-32">Qté à retourner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($sale->lines as $i => $line)
                            @if($line->returnableQuantity() > 0)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">{{ $line->product_name }}</td>
                                    <td class="px-4 py-3 text-right">{{ $line->quantity }}</td>
                                    <td class="px-4 py-3 text-right text-orange-600">{{ $line->returned_quantity > 0 ? $line->returned_quantity : '—' }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ $line->returnableQuantity() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="hidden" name="lines[{{ $i }}][sale_line_id]" value="{{ $line->id }}">
                                        <input type="number" name="lines[{{ $i }}][quantity]" value="0" min="0" max="{{ $line->returnableQuantity() }}" step="any" class="gp-input w-full text-right">
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('sales.show', ['sale' => $sale, 'tab' => 'returns']) }}" class="gp-btn-secondary">Annuler</a>
            <button class="gp-btn-primary !bg-orange-600 !hover:bg-orange-700">Confirmer le retour</button>
        </div>
    </form>
@endsection
