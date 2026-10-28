@extends('layouts.app')
@section('title','Plan comptable')
@section('heading','Plan comptable')
@section('content')
@include('accounting._nav')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
<section class="gp-card mb-4"><form method="post" action="{{ route('accounting.accounts.store') }}" class="grid gap-3 sm:grid-cols-4">@csrf
<input name="code" placeholder="Code" required class="rounded-xl border px-3 py-2.5 text-sm">
<input name="name" placeholder="Nom" required class="rounded-xl border px-3 py-2.5 text-sm">
<select name="type" class="rounded-xl border px-3 py-2.5 text-sm">@foreach($types as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
<button class="gp-btn-primary">Ajouter</button></form></section>
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Type</th></tr></thead>
<tbody>@foreach($accounts as $a)<tr class="border-t"><td class="px-4 py-3 font-mono">{{ $a->code }}</td><td class="px-4 py-3">{{ $a->name }}</td><td class="px-4 py-3">{{ $types[$a->type] ?? $a->type }}</td></tr>@endforeach</tbody></table></section>
@endsection
