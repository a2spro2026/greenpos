@extends('layouts.app')
@section('title', 'Imprimantes')
@section('heading', 'Profils d\'impression')
@section('subtitle', 'Tickets client, cuisine, ou les deux.')
@section('actions')
    <a href="{{ route('pos.printers.create') }}" class="gp-btn-primary">Nouveau profil</a>
@endsection
@section('content')
@include('pos._nav')
@if(session('success'))<div class="mb-4 gp-flash gp-flash-success">{{ session('success') }}</div>@endif
<div class="grid gap-3">
    @forelse($printers as $printer)
        <article class="gp-card flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold">{{ $printer->name }} @if($printer->is_default)<span class="text-xs text-gp-primary">défaut</span>@endif</h2>
                <p class="text-xs text-gp-muted">{{ $printer->roleLabel() }} · {{ $printer->store?->name ?? 'Toutes les boutiques' }} · {{ $printer->is_active ? 'actif' : 'inactif' }}</p>
            </div>
            <a href="{{ route('pos.printers.edit', $printer) }}" class="gp-btn-secondary text-xs">Modifier</a>
        </article>
    @empty
        <p class="gp-card text-sm text-gp-muted">Aucun profil. L'impression ticket actuelle reste inchangée.</p>
    @endforelse
</div>
@endsection
