@extends('layouts.superadmin')
@section('title', 'Utilisateurs globaux')
@section('breadcrumb', 'Platform / Users')
@section('heading', 'Utilisateurs globaux')
@section('content')
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…" class="sa-input max-w-xs">
    <label class="flex items-center gap-2 text-sm text-slate-400"><input type="checkbox" name="admins_only" value="1" {{ !empty($filters['admins_only']) ? 'checked' : '' }}> Super Admins uniquement</label>
    <button class="sa-btn sa-btn-ghost">Filtrer</button>
</form>
<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead><tr><th>Utilisateur</th><th>Email</th><th>Statut</th><th>Platform Admin</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td class="font-semibold">{{ method_exists($u, 'displayName') ? $u->displayName() : $u->name }}</td>
                    <td class="text-slate-400">{{ $u->email }}</td>
                    <td>{{ $u->status ?? '—' }}</td>
                    <td>
                        @if($u->is_platform_admin)
                            <span class="sa-badge bg-sky-500/15 text-sky-300">Super Admin</span>
                        @else
                            <span class="text-slate-500">—</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('superadmin.users.toggle-admin', $u) }}">@csrf
                            <button class="text-xs font-semibold text-sky-400 hover:underline">
                                {{ $u->is_platform_admin ? 'Retirer' : 'Promouvoir' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-16 text-center text-slate-500">Aucun utilisateur</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $users->links() }}</div>@endif
</div>
@endsection
