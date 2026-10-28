@extends('layouts.site')
@section('title', $company->name.' — Boutique')
@section('content')
<div class="mx-auto max-w-6xl px-4 py-10">
<h1 class="text-3xl font-bold">{{ $company->name }}</h1>
<p class="mt-2 text-slate-600">Catalogue en ligne</p>
<a href="{{ route('shop.cart', $company->shop_slug) }}" class="mt-4 inline-flex rounded-full bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Voir le panier</a>
@if(session('success'))<p class="mt-4 text-emerald-700">{{ session('success') }}</p>@endif
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach($products as $p)
<div class="rounded-2xl border border-slate-200 p-4">
<h2 class="font-semibold">{{ $p->name }}</h2>
<p class="mt-1 text-teal-700 font-bold">{{ number_format($p->sale_price, 2, ',', ' ') }} {{ $company->currency ?? 'MAD' }}</p>
<form method="post" action="{{ route('shop.cart.add', $company->shop_slug) }}" class="mt-3 flex gap-2">@csrf
<input type="hidden" name="product_id" value="{{ $p->id }}">
<input type="number" name="quantity" value="1" min="1" step="1" class="w-20 rounded-lg border px-2 py-1">
<button class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm text-white">Ajouter</button>
</form></div>
@endforeach
</div>
{{ $products->links() }}
</div>
@endsection
