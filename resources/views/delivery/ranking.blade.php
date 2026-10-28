@extends('layouts.app')

@section('title', 'Performances livreurs')
@section('breadcrumb', 'Livraison')
@section('heading', 'Performances')
@section('subtitle', 'Classement par CA livré et commissions.')

@section('actions')
    <a href="{{ route('delivery.index') }}" class="gp-btn-secondary">Livreurs</a>
@endsection

@section('content')
    @include('delivery._nav')

    <section class="gp-card mb-4">
        <form method="get" class="flex flex-wrap gap-3 items-end">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Du</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Au</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if(empty($ranking))
            <div class="px-6 py-16 text-center text-gp-muted">Aucun livreur.</div>
        @else
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase text-gp-muted dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Livreur</th>
                        <th class="px-4 py-3">Livraisons</th>
                        <th class="px-4 py-3">CA</th>
                        <th class="px-4 py-3">Commissions</th>
                        <th class="px-4 py-3">Ticket moy.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gp-border dark:divide-white/10">
                    @foreach($ranking as $i => $row)
                        <tr>
                            <td class="px-4 py-3 text-gp-muted">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('delivery.show', $row['agent']) }}" class="hover:text-gp-primary">{{ $row['agent']->name }}</a></td>
                            <td class="px-4 py-3">{{ $row['stats']['deliveries'] }}</td>
                            <td class="px-4 py-3 font-semibold">{{ number_format($row['stats']['revenue'], 2, ',', ' ') }} {{ $currency }}</td>
                            <td class="px-4 py-3">{{ number_format($row['stats']['commission'], 2, ',', ' ') }}</td>
                            <td class="px-4 py-3">{{ number_format($row['stats']['avg_ticket'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
