@extends('layouts.app')

@section('title', 'Réceptionner '.$order->number)
@section('breadcrumb', 'Approvisionnement / Achats')
@section('heading', 'Réception — '.$order->number)
@section('subtitle', 'Réception totale ou partielle. La validation met à jour le stock.')

@section('actions')
    <a href="{{ route('purchases.orders.show', $order) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('purchases._nav')

    @if($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('purchases.receipts.store', $order) }}" class="space-y-4">
        @csrf
        <section class="gp-card grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Date de réception</span>
                <input type="date" name="received_at" value="{{ old('received_at', now()->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Notes</span>
                <input type="text" name="notes" value="{{ old('notes') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
        </section>

        <section class="gp-card overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-gp-border px-5 py-3 dark:border-white/10">
                <h2 class="text-sm font-bold">Quantités reçues</h2>
                <button type="button" id="fill-remaining" class="text-xs font-semibold text-gp-primary hover:underline">Tout réceptionner</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-gp-muted dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left">Produit</th>
                            <th class="px-4 py-3 text-right">Commandé</th>
                            <th class="px-4 py-3 text-right">Déjà reçu</th>
                            <th class="px-4 py-3 text-right">Reste</th>
                            <th class="px-4 py-3 text-right">À réceptionner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($order->lines as $line)
                            @php $remain = $line->remainingQuantity(); @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $line->product?->name }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($line->received_quantity, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($remain, 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" step="0.001" min="0" max="{{ $remain }}" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id, $remain) }}" data-remain="{{ $remain }}" class="recv-qty w-28 rounded-lg border border-gp-border px-2 py-1.5 text-right dark:border-white/10 dark:bg-[#0f1614]" {{ $remain <= 0 ? 'disabled' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-wrap gap-2">
            <button name="validate_now" value="1" class="gp-btn-primary">Valider et mettre à jour le stock</button>
            <button class="gp-btn-secondary">Enregistrer en brouillon</button>
        </div>
    </form>

    <script>
        document.getElementById('fill-remaining')?.addEventListener('click', () => {
            document.querySelectorAll('.recv-qty').forEach(i => { if (!i.disabled) i.value = i.dataset.remain; });
        });
    </script>
@endsection
