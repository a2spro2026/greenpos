@extends('layouts.site')
@section('title', 'Checkout')
@section('content')
<div class="mx-auto max-w-xl px-4 py-10">
<h1 class="text-2xl font-bold">Finaliser la commande</h1>
<form method="post" action="{{ route('shop.checkout.store', $company->shop_slug) }}" class="mt-6 space-y-3">@csrf
<input name="name" required placeholder="Nom" class="w-full rounded-xl border px-3 py-2.5">
<input name="email" type="email" placeholder="Email" class="w-full rounded-xl border px-3 py-2.5">
<input name="phone" placeholder="Téléphone" class="w-full rounded-xl border px-3 py-2.5">
<textarea name="address" placeholder="Adresse de livraison" class="w-full rounded-xl border px-3 py-2.5" rows="3"></textarea>
<select name="payment" class="w-full rounded-xl border px-3 py-2.5"><option value="pay_on_delivery">Paiement à la livraison</option><option value="card_stub">Carte (simulation)</option></select>
<button class="rounded-full bg-teal-700 px-5 py-2.5 font-semibold text-white">Confirmer</button>
</form></div>
@endsection
