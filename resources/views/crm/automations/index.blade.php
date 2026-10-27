@extends('layouts.app')
@section('title','Automatisations')
@section('heading','Automatisations CRM')
@section('actions')<a href="{{ route('crm.automations.create') }}" class="gp-btn-primary">Nouvelle règle</a>@endsection
@section('content')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
<section class="gp-card overflow-hidden p-0"><table class="min-w-full text-sm">
<thead class="bg-slate-50 text-xs uppercase text-gp-muted"><tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Déclencheur</th><th class="px-4 py-3">Actif</th><th class="px-4 py-3">Dernière exéc.</th><th></th></tr></thead>
<tbody>
@forelse($items as $a)
<tr class="border-t"><td class="px-4 py-3 font-semibold">{{ $a->name }}</td>
<td class="px-4 py-3">{{ $triggers[$a->trigger_type] ?? $a->trigger_type }}</td>
<td class="px-4 py-3">{{ $a->is_active ? 'Oui' : 'Non' }}</td>
<td class="px-4 py-3">{{ optional($a->last_run_at)->format('d/m H:i') ?: '—' }}</td>
<td class="px-4 py-3 text-right space-x-2">
<form class="inline" method="post" action="{{ route('crm.automations.run', $a) }}">@csrf<button class="text-emerald-700 font-semibold">Exécuter</button></form>
<form class="inline" method="post" action="{{ route('crm.automations.destroy', $a) }}">@csrf @method('DELETE')<button class="text-rose-700">Suppr.</button></form>
</td></tr>
@empty<tr><td colspan="5" class="px-4 py-10 text-center text-gp-muted">Aucune automatisation</td></tr>@endforelse
</tbody></table></section>{{ $items->links() }}
@endsection
