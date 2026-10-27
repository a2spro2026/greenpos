@extends('layouts.app')

@section('title', 'Paie '.$payroll->periodLabel())
@section('breadcrumb', 'Ressources Humaines')
@section('heading', 'Bulletin — '.$payroll->employee?->full_name)
@section('subtitle', $payroll->periodLabel().' · '.$payroll->payTypeLabel().' · '.$payroll->statusLabel())

@section('actions')
    <a href="{{ route('hr.payroll.index', ['year' => $payroll->year, 'month' => $payroll->month]) }}" class="gp-btn-secondary">Retour</a>
@endsection

@section('content')
    @include('hr._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Brut</p><p class="mt-2 text-2xl font-bold">{{ number_format($payroll->gross, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Heures supp.</p><p class="mt-2 text-2xl font-bold">{{ number_format($payroll->overtime_amount, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Déductions</p><p class="mt-2 text-2xl font-bold text-rose-600">{{ number_format($payroll->absence_deduction + $payroll->advance + $payroll->retention, 2, ',', ' ') }}</p></article>
        <article class="gp-kpi"><p class="text-xs font-semibold uppercase text-gp-muted">Net</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($payroll->net, 2, ',', ' ') }}</p></article>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="gp-card lg:col-span-2 grid gap-4 sm:grid-cols-2">
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Taux de base</p><p class="mt-1 font-semibold">{{ number_format($payroll->base_rate, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Type</p><p class="mt-1 font-semibold">{{ $payroll->payTypeLabel() }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Jours travaillés</p><p class="mt-1">{{ $payroll->worked_days }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Jours absents</p><p class="mt-1">{{ $payroll->absent_days }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Heures normales</p><p class="mt-1">{{ number_format($payroll->normal_hours, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Heures supp.</p><p class="mt-1">{{ number_format($payroll->overtime_hours, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Prime</p><p class="mt-1">{{ number_format($payroll->prime, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Bonus</p><p class="mt-1">{{ number_format($payroll->bonus, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Avance</p><p class="mt-1">{{ number_format($payroll->advance, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Retenue</p><p class="mt-1">{{ number_format($payroll->retention, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Pénalité absences</p><p class="mt-1">{{ number_format($payroll->absence_deduction, 2, ',', ' ') }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-gp-muted">Calculé le</p><p class="mt-1">{{ $payroll->calculated_at?->format('d/m/Y H:i') ?: '—' }}</p></div>
            @if($payroll->notes)
                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-gp-muted">Notes</p><p class="mt-1 text-sm">{{ $payroll->notes }}</p></div>
            @endif
        </article>

        <aside class="space-y-4">
            <article class="gp-card">
                <h2 class="mb-3 text-sm font-bold">Paiement</h2>
                <div class="mb-3 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gp-muted">Statut</span><span class="gp-badge {{ $payroll->statusColor() }}">{{ $payroll->statusLabel() }}</span></div>
                    <div class="flex justify-between"><span class="text-gp-muted">Payé</span><span class="font-semibold">{{ number_format($payroll->paid_amount, 2, ',', ' ') }}</span></div>
                    <div class="flex justify-between"><span class="text-gp-muted">Méthode</span><span>{{ $payroll->paymentMethodLabel() }}</span></div>
                    <div class="flex justify-between"><span class="text-gp-muted">Payé le</span><span>{{ $payroll->paid_at?->format('d/m/Y') ?: '—' }}</span></div>
                </div>
                @can('hr.update')
                    @if($payroll->status !== 'paid')
                        <form method="post" action="{{ route('hr.payroll.pay', $payroll) }}" class="space-y-2 border-t border-gp-border pt-3 dark:border-white/10">
                            @csrf
                            <label class="block text-sm"><span class="mb-1 block font-semibold">Montant</span>
                                <input type="number" step="0.01" name="paid_amount" value="{{ old('paid_amount', $payroll->net) }}" class="w-full rounded-xl border border-gp-border px-3 py-2 dark:border-white/10 dark:bg-[#0f1614]">
                            </label>
                            <label class="block text-sm"><span class="mb-1 block font-semibold">Méthode</span>
                                <select name="payment_method" class="w-full rounded-xl border border-gp-border px-3 py-2 dark:border-white/10 dark:bg-[#0f1614]">
                                    @foreach($paymentMethods as $key => $label)
                                        <option value="{{ $key }}" @selected(old('payment_method', $payroll->payment_method ?? 'transfer') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="gp-btn-primary w-full">Marquer comme payé</button>
                        </form>
                    @endif
                @endcan
            </article>
            <article class="gp-card text-sm">
                <a href="{{ route('hr.employees.show', $payroll->employee) }}" class="font-semibold text-gp-primary hover:underline">Voir la fiche employé</a>
            </article>
        </aside>
    </section>
@endsection
