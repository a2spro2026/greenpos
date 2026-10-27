@extends('layouts.app')
@section('title','KDS')
@section('heading','Cuisine / KDS')
@section('content')
<meta http-equiv="refresh" content="10">
<div class="grid gap-4 lg:grid-cols-2">
<section class="gp-card"><h2 class="mb-3 font-bold">Tickets POS</h2>
@forelse($tickets as $t)
<div class="mb-3 rounded-xl border border-gp-border p-3"><div class="flex justify-between"><strong>{{ $t->number }}</strong><span class="text-xs">{{ $t->service_mode }}</span></div>
<ul class="mt-2 text-sm">@foreach($t->lines as $l)<li>{{ $l->quantity }} × {{ $l->product_name }}</li>@endforeach</ul>
@if($t->notes)<p class="mt-1 text-xs text-gp-muted">{{ $t->notes }}</p>@endif
</div>
@empty<p class="text-sm text-gp-muted">Aucun ticket récent</p>@endforelse
</section>
<section class="gp-card"><h2 class="mb-3 font-bold">Commandes</h2>
@forelse($orders as $o)
<div class="mb-3 rounded-xl border border-gp-border p-3"><div class="flex justify-between items-center"><strong>{{ $o->number }}</strong>
<form method="post" action="{{ route('pos.kds.ready', $o) }}">@csrf<button class="gp-btn-primary text-xs">Prêt</button></form></div>
<ul class="mt-2 text-sm">@foreach($o->lines as $l)<li>{{ $l->quantity }} × {{ $l->product_name ?? $l->description }}</li>@endforeach</ul>
</div>
@empty<p class="text-sm text-gp-muted">Aucune commande en préparation</p>@endforelse
</section>
</div>
@endsection
