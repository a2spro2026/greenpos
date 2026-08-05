@extends('layouts.superadmin')
@section('title', 'Licences')
@section('breadcrumb', 'Platform / Licences')
@section('heading', 'Gestion des licences')
@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    @foreach([
        'active' => 'Actives ('.$counts['active'].')',
        'expired' => 'Expirées ('.$counts['expired'].')',
        'renewals' => 'Renouvellements ('.$counts['renewals'].')',
        'revoked' => 'Révoquées ('.$counts['revoked'].')',
        'history' => 'Historique ('.$counts['all'].')',
    ] as $key => $label)
        <a href="{{ route('superadmin.licenses.index', ['tab' => $key]) }}"
           class="sa-btn {{ $tab === $key ? 'sa-btn-primary' : 'sa-btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead><tr><th>Clé</th><th>Client</th><th>Plan</th><th>Statut</th><th>Émise</th><th>Expire</th><th>Renouvellements</th><th></th></tr></thead>
            <tbody>
            @forelse($licenses as $lic)
                <tr>
                    <td class="sa-mono text-xs text-sky-200">{{ $lic->license_key }}</td>
                    <td>{{ $lic->tenant?->name }}</td>
                    <td class="text-slate-400">{{ $lic->subscription?->plan?->name }}</td>
                    <td><span class="sa-badge {{ $lic->status === 'active' ? 'bg-emerald-500/15 text-emerald-300' : ($lic->status === 'expired' ? 'bg-amber-500/15 text-amber-300' : 'bg-rose-500/15 text-rose-300') }}">{{ $lic->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ optional($lic->issued_at)->format('d/m/Y') }}</td>
                    <td class="text-slate-400">{{ optional($lic->expires_at)->format('d/m/Y') ?: '—' }}</td>
                    <td class="text-slate-400">{{ $lic->subscription?->renewal_count ?? 0 }}</td>
                    <td class="text-right space-x-2 whitespace-nowrap">
                        @if(in_array($lic->status, ['active', 'expired'], true))
                            <form method="POST" action="{{ route('superadmin.licenses.renew', $lic) }}" class="inline">@csrf
                                <button class="text-xs font-semibold text-emerald-400 hover:underline">Renouveler</button>
                            </form>
                        @endif
                        @if($lic->status === 'active')
                            <form method="POST" action="{{ route('superadmin.licenses.revoke', $lic) }}" class="inline" onsubmit="return confirm('Révoquer ?')">@csrf
                                <button class="text-xs font-semibold text-rose-400 hover:underline">Révoquer</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-16 text-center text-slate-500">Aucune licence</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($licenses->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $licenses->links() }}</div>@endif
</div>
@endsection
