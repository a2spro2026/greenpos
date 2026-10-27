@extends('layouts.app')
@section('title','API Tokens')
@section('heading','Jetons API')
@section('content')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm break-all">{{ session('success') }}</div>@endif
<section class="gp-card mb-4"><form method="post" action="{{ route('api.tokens.store') }}" class="flex gap-2">@csrf
<input name="name" required placeholder="Nom du jeton" class="flex-1 rounded-xl border px-3 py-2.5 text-sm">
<button class="gp-btn-primary">Créer</button></form>
<p class="mt-2 text-xs text-gp-muted">Endpoints: GET /api/v1/products — GET /api/v1/stock (Bearer token)</p></section>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Préfixe</th><th class="px-4 py-3">Dernier usage</th><th></th></tr></thead>
<tbody>@forelse($tokens as $t)<tr class="border-t"><td class="px-4 py-3">{{ $t->name }}</td><td class="px-4 py-3 font-mono">{{ $t->token_prefix }}…</td><td class="px-4 py-3">{{ optional($t->last_used_at)->diffForHumans() ?: '—' }}</td>
<td class="px-4 py-3 text-right"><form method="post" action="{{ route('api.tokens.destroy', $t) }}">@csrf @method('DELETE')<button class="text-rose-700">Révoquer</button></form></td></tr>
@empty<tr><td colspan="4" class="px-4 py-10 text-center text-gp-muted">Aucun jeton</td></tr>@endforelse</tbody></table></section>
@endsection
