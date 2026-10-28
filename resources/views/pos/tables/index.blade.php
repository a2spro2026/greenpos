@extends('layouts.app')
@section('title','Tables')
@section('heading','Tables / salles')
@section('actions')<a href="{{ route('pos.tables.floor') }}" class="gp-btn-secondary">Plan de salle</a>@endsection
@section('content')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
<section class="gp-card mb-4"><form method="post" action="{{ route('pos.tables.store') }}" class="flex flex-wrap gap-2">@csrf
<input name="name" required placeholder="Nom table" class="rounded-xl border px-3 py-2.5 text-sm">
<input name="group_name" placeholder="Groupe" class="rounded-xl border px-3 py-2.5 text-sm">
<input name="seats" type="number" min="1" value="4" class="w-24 rounded-xl border px-3 py-2.5 text-sm">
<button class="gp-btn-primary">Ajouter</button></form></section>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Groupe</th><th class="px-4 py-3">Places</th><th class="px-4 py-3">Statut</th><th></th></tr></thead>
<tbody>@forelse($tables as $t)<tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $t->name }}</td><td class="px-4 py-3">{{ $t->group_name ?: '—' }}</td><td class="px-4 py-3">{{ $t->seats }}</td><td class="px-4 py-3">{{ $t->status }}</td>
<td class="px-4 py-3 text-right"><form method="post" action="{{ route('pos.tables.destroy', $t) }}">@csrf @method('DELETE')<button class="text-rose-700">Suppr.</button></form></td></tr>
@empty<tr><td colspan="5" class="px-4 py-10 text-center text-gp-muted">Aucune table</td></tr>@endforelse</tbody></table></section>
@endsection
