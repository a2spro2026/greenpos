@extends('layouts.app')

@section('title', 'Liste clients')
@section('breadcrumb', 'Relation Client')
@section('heading', 'Clients')
@section('subtitle', 'Particuliers et sociétés — recherche, filtres et exports.')

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('customers.export')
            <a href="{{ route('customers.export', request()->query()) }}" class="gp-btn-secondary">Exporter Excel/CSV</a>
        @endcan
        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="gp-btn-primary">Nouveau client</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('customers._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10">{{ session('success') }}</div>
    @endif

    <section class="gp-card mb-4">
        <form method="get" class="grid gap-3 lg:grid-cols-6">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, code, email, ville…" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm lg:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
            <select name="status" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous statuts</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Tous types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category" class="rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Catégories</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="gp-btn-primary">Filtrer</button>
        </form>
        <form method="get" class="mt-3 flex flex-wrap gap-3 text-xs text-gp-muted">
            @foreach(request()->except('columns') as $key => $value)
                @if(is_array($value)) @continue @endif
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <span class="font-semibold">Colonnes :</span>
            @foreach(['code' => 'Code', 'name' => 'Nom', 'company' => 'Société', 'phone' => 'Tél.', 'email' => 'Email', 'city' => 'Ville', 'category' => 'Catégorie', 'last_purchase' => 'Dernier achat', 'balance' => 'Solde', 'status' => 'Statut'] as $key => $label)
                <label class="inline-flex items-center gap-1">
                    <input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $columns, true)) onchange="this.form.submit()">
                    {{ $label }}
                </label>
            @endforeach
        </form>
    </section>

    <section class="gp-card overflow-hidden p-0">
        @if($customers->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-lg font-bold">Aucun client</p>
                <p class="mt-2 text-sm text-gp-muted">Créez un client pour préparer ventes, POS et facturation.</p>
                @can('customers.create')
                    <a href="{{ route('customers.create') }}" class="gp-btn-primary mt-5">Ajouter le premier</a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gp-border bg-slate-50 text-xs uppercase tracking-wide text-gp-muted dark:border-white/10 dark:bg-white/5">
                        <tr>
                            @if(in_array('code', $columns, true))<th class="px-4 py-3">Code</th>@endif
                            @if(in_array('name', $columns, true))<th class="px-4 py-3">Nom</th>@endif
                            @if(in_array('company', $columns, true))<th class="px-4 py-3">Société</th>@endif
                            @if(in_array('phone', $columns, true))<th class="px-4 py-3">Téléphone</th>@endif
                            @if(in_array('email', $columns, true))<th class="px-4 py-3">Email</th>@endif
                            @if(in_array('city', $columns, true))<th class="px-4 py-3">Ville</th>@endif
                            @if(in_array('category', $columns, true))<th class="px-4 py-3">Catégorie</th>@endif
                            @if(in_array('last_purchase', $columns, true))<th class="px-4 py-3">Dernier achat</th>@endif
                            @if(in_array('balance', $columns, true))<th class="px-4 py-3">Solde</th>@endif
                            @if(in_array('status', $columns, true))<th class="px-4 py-3">Statut</th>@endif
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($customers as $customer)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-white/5">
                                @if(in_array('code', $columns, true))<td class="px-4 py-3 font-mono text-xs">{{ $customer->code ?: '—' }}</td>@endif
                                @if(in_array('name', $columns, true))
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gp-primary-soft text-xs font-bold text-gp-primary">{{ $customer->initials() }}</span>
                                            <div>
                                                <a href="{{ route('customers.show', $customer) }}" class="font-semibold hover:text-gp-primary">{{ $customer->name }}</a>
                                                <p class="text-xs text-gp-muted">{{ $customer->typeLabel() }}</p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                @if(in_array('company', $columns, true))<td class="px-4 py-3">{{ $customer->company_name ?: '—' }}</td>@endif
                                @if(in_array('phone', $columns, true))<td class="px-4 py-3">{{ $customer->phone ?: '—' }}</td>@endif
                                @if(in_array('email', $columns, true))<td class="px-4 py-3">{{ $customer->email ?: '—' }}</td>@endif
                                @if(in_array('city', $columns, true))<td class="px-4 py-3">{{ $customer->city ?: '—' }}</td>@endif
                                @if(in_array('category', $columns, true))<td class="px-4 py-3">{{ $customer->categoryLabel() }}</td>@endif
                                @if(in_array('last_purchase', $columns, true))<td class="px-4 py-3 text-xs text-gp-muted">{{ $customer->last_purchase_at?->format('d/m/Y') ?: '—' }}</td>@endif
                                @if(in_array('balance', $columns, true))<td class="px-4 py-3 font-semibold">{{ number_format($customer->balance, 2, ',', ' ') }}</td>@endif
                                @if(in_array('status', $columns, true))<td class="px-4 py-3"><span class="gp-badge {{ $customer->statusColor() }}">{{ $customer->statusLabel() }}</span></td>@endif
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                        <a href="{{ route('customers.show', $customer) }}" class="text-gp-primary hover:underline">Voir</a>
                                        @can('customers.update')
                                            <a href="{{ route('customers.edit', $customer) }}" class="text-gp-muted hover:underline">Modifier</a>
                                        @endcan
                                        @can('customers.print')
                                            <a href="{{ route('customers.print', $customer) }}" target="_blank" class="text-gp-muted hover:underline">Imprimer</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gp-border px-4 py-3 dark:border-white/10">{{ $customers->links() }}</div>
        @endif
    </section>
@endsection
