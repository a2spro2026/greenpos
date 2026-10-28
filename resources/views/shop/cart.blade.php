@extends('layouts.site')
@section('title', 'Panier')
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
<h1 class="text-2xl font-bold">Panier</h1>
<a href="{{ route('shop.home', $company->shop_slug) }}" class="text-sm text-teal-700">← Catalogue</a>
<ul class="mt-6 space-y-3">
@forelse($cart as $line)
<li class="flex justify-between rounded-xl border p-3"><span>{{ $line['name'] }} × {{ $line['quantity'] }}</span><span class="font-semibold">{{ number_format($line['price']*$line['quantity'],2,',',' ') }}</span></li>
@empty<li class="text-slate-500">Panier vide</li>@endforelse
</ul>
@if(!empty($cart))<a href="{{ route('shop.checkout', $company->shop_slug) }}" class="mt-6 inline-flex rounded-full bg-teal-700 px-5 py-2.5 font-semibold text-white">Commander</a>@endif
</div>
@endsection
