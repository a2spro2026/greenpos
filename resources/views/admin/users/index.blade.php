@extends('layouts.admin')
@section('title', 'Utilisateurs')
@section('breadcrumb', 'Plateforme')
@section('heading', 'Utilisateurs')
@section('content')
<form method="GET" class="mb-4"><input type="search" name="q" value="{{ request('q') }}" class="pa-input max-w-sm" placeholder="Nom ou e-mail…"></form>
<div class="pa-card overflow-x-auto !p-0">
<table class="pa-table">
<thead><tr><th>Utilisateur</th><th>E-mail</th><th>Entreprises</th><th>Rôle plateforme</th><th>Statut</th></tr></thead>
<tbody>
@forelse($users as $user)
<tr>
<td class="font-semibold">{{ $user->name }}</td>
<td class="pa-mono text-xs">{{ $user->email }}</td>
<td>{{ $user->companies_count }}</td>
<td>{{ $user->is_platform_admin ? 'Super Admin' : 'Client' }}</td>
<td><span class="pa-badge {{ $user->status === 'active' ? 'pa-badge-ok' : 'pa-badge-muted' }}">{{ $user->status }}</span></td>
</tr>
@empty
<tr><td colspan="5" class="text-zinc-500">Aucun utilisateur.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($users->hasPages())<div class="mt-4">{{ $users->links() }}</div>@endif
@endsection
