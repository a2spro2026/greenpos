@extends('layouts.site')
@section('title', 'Merci')
@section('content')
<div class="mx-auto max-w-xl px-4 py-16 text-center">
<h1 class="text-3xl font-bold">Merci !</h1>
<p class="mt-3 text-slate-600">Commande {{ $sale->number }} enregistrée.</p>
<a href="{{ route('shop.home', $company->shop_slug) }}" class="mt-6 inline-flex text-teal-700 font-semibold">Retour boutique</a>
</div>
@endsection
