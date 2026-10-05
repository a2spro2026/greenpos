@extends('layouts.app')
@section('title', 'Remboursement '.$sale->number)
@section('heading', 'Remboursement ticket '.$sale->number)
@section('content')
@include('pos._nav')
<form method="POST" action="{{ route('pos.tickets.refund.store', $sale) }}" class="gp-card max-w-2xl space-y-4">
    @csrf
    <p class="text-sm text-gp-muted">Déjà remboursé : {{ number_format($sale->amount_refunded, 2, ',', ' ') }} / {{ number_format($sale->total_ttc, 2, ',', ' ') }}</p>
    @if($errors->any())<div class="gp-flash gp-flash-warning">{{ $errors->first() }}</div>@endif
    <div class="grid gap-3 sm:grid-cols-2">
        <input type="number" step="0.01" name="amount" value="{{ max(0, $sale->total_ttc - $sale->amount_refunded) }}" class="gp-input" required>
        <select name="method" class="gp-select">@foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
    </div>
    <input name="reason" placeholder="Motif" class="gp-input w-full" required>
    <label class="text-sm"><input type="checkbox" name="restock" value="1"> Réintégrer le stock</label>
    @foreach($sale->lines as $i => $line)
        <div class="grid grid-cols-3 items-center gap-2 text-sm">
            <span class="col-span-2">{{ $line->product_name }}</span>
            <input type="hidden" name="lines[{{ $i }}][line_id]" value="{{ $line->id }}">
            <input type="number" step="0.001" min="0" name="lines[{{ $i }}][quantity]" value="0" class="gp-input">
        </div>
    @endforeach
    <button class="gp-btn-primary">Rembourser</button>
</form>
@endsection
