@extends('layouts.superadmin')
@section('title', 'Domaines')
@section('breadcrumb', 'Platform / Domaines')
@section('heading', 'Domaines personnalisés')
@section('content')
<div class="mb-6 grid gap-4 xl:grid-cols-3">
    <form method="POST" action="{{ route('superadmin.domains.store') }}" class="sa-card space-y-3 xl:col-span-1">
        @csrf
        <h2 class="text-sm font-bold text-white">Ajouter un domaine</h2>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Client</label>
            <select name="saas_tenant_id" class="sa-select" required>
                @foreach($tenants as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="mb-1 block text-xs text-slate-500">Domaine</label><input name="domain" placeholder="pos.client.com" class="sa-input" required></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_primary" value="1"> Domaine principal</label>
        <button class="sa-btn sa-btn-primary w-full">Ajouter</button>
    </form>
    <div class="sa-card overflow-hidden p-0 xl:col-span-2">
        <div class="overflow-x-auto">
            <table class="sa-table">
                <thead><tr><th>Domaine</th><th>Client</th><th>SSL</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($domains as $d)
                    <tr>
                        <td class="font-semibold">{{ $d->domain }} @if($d->is_primary)<span class="sa-badge bg-sky-500/15 text-sky-300">Primary</span>@endif</td>
                        <td>{{ $d->tenant?->name }}</td>
                        <td>{{ $d->ssl_enabled ? 'Oui' : 'Non' }}</td>
                        <td>{{ $d->statusLabel() }}</td>
                        <td class="space-x-2 text-right">
                            @if($d->status !== 'active')
                            <form class="inline" method="POST" action="{{ route('superadmin.domains.verify', $d) }}">@csrf<button class="text-xs font-semibold text-emerald-400">Vérifier</button></form>
                            @endif
                            <form class="inline" method="POST" action="{{ route('superadmin.domains.destroy', $d) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                                <button class="text-xs font-semibold text-rose-400">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-16 text-center text-slate-500">Aucun domaine</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($domains->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $domains->links() }}</div>@endif
    </div>
</div>
@endsection
