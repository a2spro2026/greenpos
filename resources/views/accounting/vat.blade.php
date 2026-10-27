@extends('layouts.app')
@section('title','TVA')
@section('heading','Export TVA')
@section('content')
@include('accounting._nav')
<form class="mb-4 flex flex-wrap gap-2"><input type="date" name="from" value="{{ $from }}" class="rounded-xl border px-3 py-2"><input type="date" name="to" value="{{ $to }}" class="rounded-xl border px-3 py-2"><button class="gp-btn-secondary">Filtrer</button><a class="gp-btn-primary" href="{{ route('accounting.vat', ['from'=>$from,'to'=>$to,'export'=>1]) }}">Export CSV</a></form>
<section class="grid gap-3 sm:grid-cols-3">
<div class="gp-card"><p class="text-xs text-gp-muted">TVA ventes</p><p class="text-xl font-bold">{{ number_format($summary['sales_vat'],2,',',' ') }}</p></div>
<div class="gp-card"><p class="text-xs text-gp-muted">TVA factures</p><p class="text-xl font-bold">{{ number_format($summary['invoice_vat'],2,',',' ') }}</p></div>
<div class="gp-card"><p class="text-xs text-gp-muted">Collectée</p><p class="text-xl font-bold">{{ number_format($summary['collected'],2,',',' ') }}</p></div>
</section>
@endsection
