@extends('layouts.superadmin')
@section('title', 'Alertes abonnements')
@section('breadcrumb', 'Billing / Alertes')
@section('heading', 'Alertes abonnements')
@section('actions')
    <a href="{{ route('superadmin.subscriptions.dashboard') }}" class="sa-btn sa-btn-ghost">Dashboard</a>
@endsection
@section('content')
<div class="sa-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="sa-table">
            <thead><tr><th>Date</th><th>Type</th><th>Client</th><th>Message</th><th>Sévérité</th><th></th></tr></thead>
            <tbody>
            @forelse($alerts as $a)
                <tr class="{{ $a->is_read ? 'opacity-50' : '' }}">
                    <td class="text-slate-400 whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $a->typeLabel() }}</td>
                    <td>{{ $a->tenant?->name ?? '—' }}</td>
                    <td>
                        <p class="font-semibold text-white">{{ $a->title }}</p>
                        <p class="text-xs text-slate-500">{{ $a->body }}</p>
                    </td>
                    <td><span class="sa-badge {{ $a->severityColor() }}">{{ $a->severity }}</span></td>
                    <td class="text-right">
                        @if(! $a->is_read)
                            <form method="POST" action="{{ route('superadmin.subscriptions.alerts.read', $a) }}">@csrf
                                <button class="text-xs font-semibold text-sky-400">Marquer lu</button>
                            </form>
                        @endif
                        @if($a->subscription)
                            <a href="{{ route('superadmin.subscriptions.show', $a->subscription) }}" class="ml-2 text-xs text-slate-400 hover:text-white">Voir</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-16 text-center text-slate-500">Aucune alerte</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($alerts->hasPages())<div class="border-t border-white/5 px-4 py-3">{{ $alerts->links() }}</div>@endif
</div>
@endsection
