@extends('layouts.app')
@section('title','Écritures')
@section('heading','Journal général')
@section('content')
@include('accounting._nav')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
<section class="gp-card mb-4">
<form method="post" action="{{ route('accounting.ledger.store') }}" class="space-y-3">@csrf
<input name="label" required placeholder="Libellé" class="w-full rounded-xl border px-3 py-2.5 text-sm">
<input type="date" name="entry_date" value="{{ now()->toDateString() }}" class="rounded-xl border px-3 py-2.5 text-sm">
<div class="grid gap-2 sm:grid-cols-2">
@foreach([0,1] as $i)
<div class="rounded-xl border p-3 space-y-2">
<select name="lines[{{ $i }}][account_id]" required class="w-full rounded-lg border px-2 py-2 text-sm">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach</select>
<input name="lines[{{ $i }}][debit]" type="number" step="0.01" min="0" placeholder="Débit" class="w-full rounded-lg border px-2 py-2 text-sm">
<input name="lines[{{ $i }}][credit]" type="number" step="0.01" min="0" placeholder="Crédit" class="w-full rounded-lg border px-2 py-2 text-sm">
</div>
@endforeach
</div>
<button class="gp-btn-primary">Enregistrer écriture</button>
</form></section>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">N°</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Libellé</th><th class="px-4 py-3">Lignes</th></tr></thead>
<tbody>@forelse($entries as $e)<tr class="border-t"><td class="px-4 py-3">{{ $e->number }}</td><td class="px-4 py-3">{{ $e->entry_date?->format('d/m/Y') }}</td><td class="px-4 py-3">{{ $e->label }}</td>
<td class="px-4 py-3 text-xs">@foreach($e->lines as $l){{ $l->account?->code }} D{{ $l->debit }} C{{ $l->credit }}<br>@endforeach</td></tr>
@empty<tr><td colspan="4" class="px-4 py-10 text-center text-gp-muted">Aucune écriture</td></tr>@endforelse</tbody></table></section>{{ $entries->links() }}
@endsection
