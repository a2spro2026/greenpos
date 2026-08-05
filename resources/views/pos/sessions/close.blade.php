@extends('layouts.app')

@section('title', 'Clôturer caisse')
@section('breadcrumb', 'Ventes / POS / Sessions')
@section('heading', 'Clôture — '.$session->number)
@section('subtitle', 'Comptez les espèces et validez l’écart de caisse.')

@section('content')
    @include('pos._nav')

    <div class="mx-auto grid max-w-3xl gap-4 lg:grid-cols-2">
        <article class="gp-card space-y-3">
            <h2 class="text-sm font-bold">Récapitulatif théorique</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gp-muted">Fond d’ouverture</dt><dd class="font-semibold">{{ number_format($session->opening_float, 2, ',', ' ') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gp-muted">Encaissements espèces</dt><dd class="font-semibold">{{ number_format($cashSales, 2, ',', ' ') }}</dd></div>
                <div class="flex justify-between border-t border-gp-border pt-2 text-base font-bold dark:border-white/10">
                    <dt>Espèces attendues</dt>
                    <dd class="text-gp-primary">{{ number_format($expected, 2, ',', ' ') }}</dd>
                </div>
                <div class="flex justify-between"><dt class="text-gp-muted">Tickets</dt><dd>{{ $session->tickets_count }}</dd></div>
                <div class="flex justify-between"><dt class="text-gp-muted">Total ventes</dt><dd>{{ number_format($session->total_sales, 2, ',', ' ') }}</dd></div>
            </dl>
        </article>

        <form method="post" action="{{ route('pos.sessions.close', $session) }}" class="gp-card space-y-4">
            @csrf
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">Espèces comptées</span>
                <input type="number" name="closing_counted" value="{{ old('closing_counted', number_format($expected, 2, '.', '')) }}" min="0" step="0.01" required class="gp-input text-xl font-bold">
                @error('closing_counted')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">Notes de clôture</span>
                <textarea name="closing_notes" rows="3" class="gp-input" placeholder="Écart, incidents…">{{ old('closing_notes') }}</textarea>
            </label>
            <div class="flex gap-2">
                <a href="{{ route('pos.sessions.show', $session) }}" class="gp-btn-secondary">Annuler</a>
                <button type="submit" class="gp-btn-primary">Valider la clôture</button>
            </div>
        </form>
    </div>
@endsection
