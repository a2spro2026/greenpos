@extends('layouts.admin')

@section('title', $item->company_name)
@section('breadcrumb', 'Demandes d’inscription')
@section('heading', $item->company_name)
@section('actions')
    <a href="{{ route('admin.registrations.index') }}" class="pa-btn pa-btn-ghost">Retour</a>
@endsection

@section('content')
@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">{{ $errors->first() }}</div>
@endif

<div class="pa-grid-2 mb-4">
    <div class="pa-card space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-white">Demande {{ $item->reference }}</h2>
            @php
                $badge = match($item->status) {
                    'EN_ATTENTE' => 'pa-badge-warn',
                    'ACTIVE' => 'pa-badge-ok',
                    'REFUSEE' => 'pa-badge-danger',
                    'SUSPENDUE' => 'pa-badge-muted',
                    default => 'pa-badge-muted',
                };
            @endphp
            <span class="pa-badge {{ $badge }}">{{ $item->statusLabel() }}</span>
        </div>
        <dl class="grid gap-2 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Date</dt><dd>{{ $item->created_at?->format('d/m/Y H:i') }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Responsable</dt><dd>{{ $item->owner_name }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Email</dt><dd>{{ $item->owner_email }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Téléphone</dt><dd>{{ $item->owner_phone ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Activité</dt><dd>{{ $item->activity ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Plan</dt><dd>{{ $item->plan?->name ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Boutique</dt><dd>{{ $item->store_name }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Localisation</dt><dd>{{ $item->city }}, {{ $item->country }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Adresse</dt><dd class="text-right">{{ $item->address }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-zinc-500">Devise</dt><dd>{{ $item->currency }}</dd></div>
            @if($item->company)
                <div class="flex justify-between gap-3"><dt class="text-zinc-500">Entreprise liée</dt>
                    <dd><a class="text-emerald-400 font-semibold" href="{{ route('admin.companies.show', $item->company) }}">{{ $item->company->name }}</a></dd>
                </div>
            @endif
            @if($item->rejection_reason)
                <div><dt class="text-zinc-500 mb-1">Motif de refus</dt><dd class="text-rose-300">{{ $item->rejection_reason }}</dd></div>
            @endif
            @if($item->suspend_reason)
                <div><dt class="text-zinc-500 mb-1">Motif de suspension</dt><dd class="text-amber-300">{{ $item->suspend_reason }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="pa-card space-y-4">
        <h2 class="text-sm font-bold text-white">Actions</h2>

        @if($item->canApprove() || ($item->status === 'SUSPENDUE' && $item->company_id))
            <form method="POST" action="{{ route('admin.registrations.approve', $item) }}">
                @csrf
                <button class="pa-btn pa-btn-primary w-full" type="submit" onclick="return confirm('Approuver / réactiver cette demande ?')">
                    {{ $item->company_id ? 'Réactiver' : 'Approuver' }}
                </button>
                <p class="mt-2 text-xs text-zinc-500">
                    @if($item->company_id)
                        Réactive l’entreprise et l’abonnement.
                    @else
                        Crée l’entreprise, la boutique, le compte admin, l’abonnement et les modules du plan. Statut → ACTIVE.
                    @endif
                </p>
            </form>
        @endif

        @if($item->canReject())
            <form method="POST" action="{{ route('admin.registrations.reject', $item) }}" id="reject" class="space-y-2">
                @csrf
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Motif du refus *</label>
                <textarea name="rejection_reason" rows="3" required class="pa-input" placeholder="Expliquez le motif au client…">{{ old('rejection_reason') }}</textarea>
                <button class="pa-btn pa-btn-ghost w-full text-rose-400" type="submit">Refuser</button>
            </form>
        @endif

        @if($item->canSuspend())
            <form method="POST" action="{{ route('admin.registrations.suspend', $item) }}" id="suspend" class="space-y-2">
                @csrf
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Motif de suspension</label>
                <textarea name="suspend_reason" rows="2" class="pa-input" placeholder="Optionnel">{{ old('suspend_reason') }}</textarea>
                <button class="pa-btn pa-btn-ghost w-full text-amber-400" type="submit">Suspendre</button>
                <p class="text-xs text-zinc-500">Les utilisateurs de l’entreprise verront un message de suspension.</p>
            </form>
        @endif
    </div>
</div>
@endsection
