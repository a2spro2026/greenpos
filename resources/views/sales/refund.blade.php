@extends('layouts.app')
@section('title', 'Remboursement '.$sale->number)
@section('heading', 'Remboursement '.$sale->number)
@section('subtitle', 'Remboursement monétaire. Le retour marchandise reste disponible à part.')
@section('content')
@include('sales._nav')
<form method="POST" action="{{ route('sales.refund.store', $sale) }}" class="gp-card max-w-2xl space-y-4">
    @csrf
    <p class="text-sm text-gp-muted">Solde remboursable : {{ number_format(max(0, $sale->total_ttc - $sale->amount_returned), 2, ',', ' ') }} {{ $sale->currency }}</p>
    @if($errors->any())<div class="gp-flash gp-flash-warning">{{ $errors->first() }}</div>@endif
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="gp-label">Montant</label><input type="number" step="0.01" name="amount" value="{{ old('amount', max(0, $sale->total_ttc - $sale->amount_returned)) }}" class="gp-input w-full" required></div>
        <div><label class="gp-label">Mode</label><select name="method" class="gp-select w-full">@foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    </div>
    <div><label class="gp-label">Motif</label><input name="reason" value="{{ old('reason') }}" class="gp-input w-full" required></div>
    <div><label class="gp-label">Notes</label><textarea name="notes" class="gp-input w-full" rows="2">{{ old('notes') }}</textarea></div>
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="restock" value="1" @checked(old('restock'))> Réintégrer le stock (statut « remboursé et réintégré »)</label>
    <div class="space-y-2">
        <p class="text-xs font-bold uppercase text-gp-muted">Quantités à réintégrer (optionnel si remboursement total)</p>
        @foreach($sale->lines as $i => $line)
            <div class="grid grid-cols-3 items-center gap-2 text-sm">
                <span class="col-span-2">{{ $line->product_name }} <span class="text-gp-muted">(restant {{ $line->returnableQuantity() }})</span></span>
                <input type="hidden" name="lines[{{ $i }}][line_id]" value="{{ $line->id }}">
                <input type="number" step="0.001" min="0" name="lines[{{ $i }}][quantity]" value="0" class="gp-input">
            </div>
        @endforeach
    </div>
    <button class="gp-btn-primary">Enregistrer le remboursement</button>
</form>
@endsection
