@extends('layouts.app')
@section('title','Plan de salle')
@section('heading','Plan de salle')
@section('actions')<a href="{{ route('pos.tables.index') }}" class="gp-btn-secondary">Gérer</a>@endsection
@section('content')
<div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
@foreach($tables as $t)
<div class="rounded-2xl p-4 text-center ring-1 {{ $t->status==='occupied' ? 'bg-rose-50 ring-rose-200' : 'bg-emerald-50 ring-emerald-200' }}">
<p class="text-lg font-bold">{{ $t->name }}</p>
<p class="text-xs text-gp-muted">{{ $t->group_name }} · {{ $t->seats }} places</p>
<p class="mt-2 text-sm font-semibold">{{ $t->status==='occupied' ? 'Occupée' : 'Libre' }}</p>
@if($t->currentSale)<p class="text-xs">{{ $t->currentSale->number }}</p>@endif
</div>
@endforeach
</div>
@endsection
