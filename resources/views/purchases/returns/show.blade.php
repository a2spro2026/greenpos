@extends('layouts.app')

@section('title', $return->number)
@section('breadcrumb', 'Approvisionnement / Retours fournisseur')
@section('heading', 'Retour '.$return->number)
@section('subtitle', ($return->supplier?->name ?: 'Fournisseur').' · '.$return->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('purchases.returns.print', $return) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @can('purchases.receive')
            @if($return->status === 'draft')
                <form method="post" action="{{ route('purchases.returns.cancel', $return) }}" onsubmit="return confirm('Annuler ce brouillon ?')">@csrf<button class="gp-btn-secondary">Annuler</button></form>
                <form method="post" action="{{ route('purchases.returns.validate', $return) }}" onsubmit="return confirm('Valider et sortir les quantités du stock ?')">@csrf<button class="gp-btn-primary">Valider (sortie de stock)</button></form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    @include('purchases._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <ol class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
        @foreach(['draft' => '1. Brouillon', 'validated' => '2. Stock sorti', 'credited' => '3. Avoir reçu'] as $k => $l)
            @php($done = array_search($return->status, array_keys(\App\Models\SupplierReturn::STATUSES)) >= array_search($k, array_keys(\App\Models\SupplierReturn::STATUSES)) && $return->status !== 'cancelled')
            <li class="rounded-full px-3 py-1 {{ $done ? 'bg-gp-primary text-white' : 'bg-slate-100 text-gp-muted dark:bg-white/10' }}">{{ $l }}</li>
        @endforeach
        @if($return->status === 'cancelled')<li class="rounded-full bg-red-100 px-3 py-1 text-red-700">Annulé</li>@endif
    </ol>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="gp-card overflow-hidden p-0 lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-gp-muted">
                        <tr>
                            <th class="px-4 py-3 text-left">Produit</th>
                            <th class="px-4 py-3 text-right">Qté</th>
                            <th class="px-4 py-3 text-right">Prix HT</th>
                            <th class="px-4 py-3 text-right">TVA</th>
                            <th class="px-4 py-3 text-right">Total HT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($return->lines as $line)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $line->product?->name }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $line->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $line->unit_price, 2, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format((float) $line->tax_rate, 2, '.', ''), '0'), '.') }} %</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $line->line_total, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ml-auto w-full max-w-xs space-y-1 border-t border-gp-border p-4 text-sm dark:border-white/10">
                <div class="flex justify-between"><span class="text-gp-muted">Total HT</span><span>{{ number_format((float) $return->total_ht, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between"><span class="text-gp-muted">TVA</span><span>{{ number_format((float) $return->total_tax, 2, ',', ' ') }}</span></div>
                <div class="flex justify-between text-base font-bold"><span>Total TTC</span><span>{{ number_format((float) $return->total_ttc, 2, ',', ' ') }}</span></div>
            </div>
        </article>
        <aside class="space-y-4">
            <div class="gp-card space-y-1 text-sm">
                <p><span class="text-gp-muted">Réception :</span> @if($return->receipt)<a class="font-semibold hover:text-gp-primary" href="{{ route('purchases.receipts.show', $return->receipt) }}">{{ $return->receipt->number }}</a>@else — @endif</p>
                <p><span class="text-gp-muted">Commande :</span> @if($return->order)<a class="hover:text-gp-primary" href="{{ route('purchases.orders.show', $return->order) }}">{{ $return->order->number }}</a>@else — @endif</p>
                <p><span class="text-gp-muted">Boutique :</span> {{ $return->store?->name }}</p>
                <p><span class="text-gp-muted">Date :</span> {{ optional($return->returned_at)->format('d/m/Y') }}</p>
                <p><span class="text-gp-muted">Motif :</span> {{ $return->reason ?: '—' }}</p>
                @if($return->notes)<p class="text-gp-muted">{{ $return->notes }}</p>@endif
                <p><span class="text-gp-muted">Créé par :</span> {{ $return->creator?->name ?: '—' }}</p>
            </div>
            <div class="gp-card text-sm">
                <p class="mb-2 font-bold">Avoir fournisseur</p>
                @if($return->status === 'credited')
                    <p>N° <strong>{{ $return->credit_note_number }}</strong> du {{ optional($return->credited_at)->format('d/m/Y') }}</p>
                    <p>Montant : <strong>{{ number_format((float) $return->credit_note_amount, 2, ',', ' ') }}</strong></p>
                    @if(abs((float) $return->credit_note_amount - (float) $return->total_ttc) > 0.01)
                        <p class="mt-1 text-xs text-amber-700">Écart de {{ number_format((float) $return->credit_note_amount - (float) $return->total_ttc, 2, ',', ' ') }} avec le total du retour.</p>
                    @endif
                @elseif($return->status === 'validated')
                    @can('purchases.receive')
                        <form method="post" action="{{ route('purchases.returns.credit', $return) }}" class="space-y-2">
                            @csrf
                            <input name="credit_note_number" class="gp-input w-full" placeholder="N° d’avoir fournisseur" required>
                            <input name="credit_note_amount" type="number" step="0.01" min="0" value="{{ $return->total_ttc }}" class="gp-input w-full" required>
                            <input name="credited_at" type="date" value="{{ now()->toDateString() }}" class="gp-input w-full">
                            <button class="gp-btn-primary w-full">Enregistrer l’avoir</button>
                        </form>
                    @endcan
                @else
                    <p class="text-gp-muted">L’avoir s’enregistre après validation du retour.</p>
                @endif
            </div>
        </aside>
    </section>
@endsection
