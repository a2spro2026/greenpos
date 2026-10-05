@extends('layouts.app')
@section('title', 'Listes personnalisées')
@section('breadcrumb', 'Paramètres / Listes personnalisées')
@section('heading', 'Listes personnalisées')
@section('subtitle', 'Tickets, modes de service, paiements, taxes, remises et dépenses.')
@section('actions')
    <a href="{{ route('settings.lists.create', ['type' => $type]) }}" class="gp-btn-primary">Nouvelle entrée</a>
@endsection
@section('content')
<div class="flex flex-col gap-6 lg:flex-row">
    @include('settings._nav', ['section' => 'lists'])
    <div class="min-w-0 flex-1 space-y-4">
        @if(session('success'))<div class="gp-flash gp-flash-success">{{ session('success') }}</div>@endif
        <div class="flex flex-wrap gap-2">
            @foreach($types as $key => $label)
                <a href="{{ route('settings.lists.index', ['type' => $key]) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $type === $key ? 'bg-gp-primary text-white' : 'bg-white text-gp-muted ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10' }}">{{ $label }}</a>
            @endforeach
        </div>
        <section class="gp-card overflow-hidden p-0">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Détail</th><th class="px-4 py-3">État</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-gp-muted">{{ $item->code }}</td>
                            <td class="px-4 py-3 text-xs text-gp-muted">
                                @if($item->type === 'mode_de_paiement')
                                    {{ $item->meta('method') }} · {{ $item->meta('timing') === 'deferred' ? 'Différé' : 'Immédiat' }}
                                    @if($item->meta('required_fields')) · {{ implode(', ', $item->meta('required_fields')) }} @endif
                                @elseif($item->type === 'mode_de_service')
                                    {{ \App\Models\CustomList::OPERATIONAL_MODES[$item->meta('operational_mode')] ?? $item->meta('operational_mode') }}
                                    @if($item->meta('requires_delivery_agent')) · livreur @endif
                                    @if($item->meta('synced_from_platform')) · plateforme @endif
                                @elseif($item->type === 'taxes')
                                    {{ $item->meta('rate') }} %
                                @elseif($item->type === 'remises')
                                    {{ $item->meta('value') }} {{ $item->meta('discount_type') === 'amount' ? 'MAD' : '%' }}
                                @else
                                    {{ $item->meta('group') ?: $item->meta('expense_kind') }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3 text-right"><a class="text-gp-primary" href="{{ route('settings.lists.edit', $item) }}">Modifier</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gp-muted">Aucune entrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
@endsection
