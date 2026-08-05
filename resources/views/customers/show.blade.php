@extends('layouts.app')

@section('title', $customer->displayName())
@section('breadcrumb', 'Relation Client')
@section('heading', $customer->displayName())
@section('subtitle', ($customer->code ?: 'Sans code').' · '.$customer->typeLabel().' · '.$customer->statusLabel())

@section('actions')
    <div class="flex flex-wrap gap-2">
        @can('customers.print')
            <a href="{{ route('customers.print', $customer) }}" target="_blank" class="gp-btn-secondary">Imprimer</a>
        @endcan
        @can('customers.update')
            <a href="{{ route('customers.edit', $customer) }}" class="gp-btn-primary">Modifier</a>
        @endcan
        @can('customers.delete')
            <form method="post" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Archiver ce client ?')">
                @csrf @method('DELETE')
                <button class="gp-btn-secondary text-rose-600">Archiver</button>
            </form>
        @endcan
    </div>
@endsection

@section('content')
    @include('customers._nav')

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gp-primary-soft text-lg font-bold text-gp-primary">{{ $customer->initials() }}</span>
        <div>
            <p class="font-bold text-gp-text dark:text-white">{{ $customer->name }}</p>
            <p class="text-sm text-gp-muted">{{ $customer->email ?: '—' }} · {{ $customer->phone ?: '—' }}</p>
        </div>
        <span class="gp-badge {{ $customer->statusColor() }}">{{ $customer->statusLabel() }}</span>
        <span class="gp-badge">{{ $customer->categoryLabel() }}</span>
    </div>

    @php
        $tabs = [
            'overview' => 'Présentation',
            'purchases' => 'Achats',
            'invoices' => 'Factures',
            'payments' => 'Paiements',
            'history' => 'Historique',
            'documents' => 'Documents',
            'stats' => 'Statistiques',
        ];
    @endphp
    <nav class="mb-5 flex gap-2 overflow-x-auto pb-1">
        @foreach($tabs as $key => $label)
            <a href="{{ route('customers.show', ['customer' => $customer, 'tab' => $key]) }}"
               class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'bg-gp-primary text-white' : 'bg-white text-gp-muted ring-1 ring-gp-border dark:bg-white/5 dark:ring-white/10' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($tab === 'overview')
        <section class="grid gap-4 lg:grid-cols-3">
            <article class="gp-card lg:col-span-2 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Type</p><p class="mt-1 font-semibold">{{ $customer->typeLabel() }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Société</p><p class="mt-1 font-semibold">{{ $customer->company_name ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Adresse</p><p class="mt-1">{{ $customer->address ?: '—' }}</p><p class="text-sm text-gp-muted">{{ collect([$customer->postal_code, $customer->city, $customer->region, $customer->country])->filter()->implode(', ') }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Boutique</p><p class="mt-1">{{ $customer->store?->name ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Limite crédit</p><p class="mt-1 font-semibold">{{ number_format($customer->credit_limit, 2, ',', ' ') }} {{ $customer->currency }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Conditions</p><p class="mt-1">{{ $customer->payment_terms ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">N° fiscal</p><p class="mt-1">{{ $customer->tax_id ?: '—' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gp-muted">Site web</p><p class="mt-1">{{ $customer->website ?: '—' }}</p></div>
                @if($customer->notes)
                    <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-gp-muted">Notes</p><p class="mt-1 text-sm">{{ $customer->notes }}</p></div>
                @endif
            </article>
            <aside class="space-y-4">
                <article class="gp-card space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gp-muted">Solde</span><span class="font-bold">{{ number_format($customer->balance, 2, ',', ' ') }}</span></div>
                    <div class="flex justify-between"><span class="text-gp-muted">CA cumulé</span><span class="font-bold text-gp-primary">{{ number_format($customer->lifetime_revenue, 2, ',', ' ') }}</span></div>
                    <div class="flex justify-between"><span class="text-gp-muted">Dernier achat</span><span>{{ $customer->last_purchase_at?->format('d/m/Y') ?: '—' }}</span></div>
                </article>
                <article class="gp-card">
                    <h2 class="mb-3 text-sm font-bold">Contacts</h2>
                    @forelse($customer->contacts as $contact)
                        <div class="mb-3 text-sm">
                            <p class="font-semibold">{{ $contact->name }} @if($contact->is_primary)<span class="gp-badge">Principal</span>@endif</p>
                            <p class="text-xs text-gp-muted">{{ $contact->role }} · {{ $contact->email }} · {{ $contact->phone }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gp-muted">Aucun contact.</p>
                    @endforelse
                </article>
            </aside>
        </section>
    @endif

    @if(in_array($tab, ['purchases', 'invoices', 'payments'], true))
        <section class="gp-card px-6 py-16 text-center">
            <p class="text-lg font-bold">
                @if($tab === 'purchases') Achats client
                @elseif($tab === 'invoices') Factures
                @else Paiements
                @endif
            </p>
            <p class="mt-2 text-sm text-gp-muted">
                Cet onglet sera alimenté automatiquement par les modules
                @if($tab === 'purchases') Ventes / POS
                @elseif($tab === 'invoices') Facturation
                @else Paiements
                @endif.
            </p>
            <p class="mt-4 text-xs text-gp-muted">Préparation CRM · aucune donnée opérationnelle pour le moment.</p>
        </section>
    @endif

    @if($tab === 'history')
        <section class="gp-card">
            <ol class="space-y-4 border-l border-gp-border pl-4 dark:border-white/10">
                @forelse($customer->changeLogs as $log)
                    <li>
                        <p class="font-semibold">{{ $log->message }}</p>
                        <p class="text-xs text-gp-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->name ?: 'Système' }}</p>
                    </li>
                @empty
                    <li class="text-sm text-gp-muted">Aucun historique.</li>
                @endforelse
            </ol>
        </section>
    @endif

    @if($tab === 'documents')
        <section class="mb-4 grid gap-4 lg:grid-cols-3">
            <article class="gp-card lg:col-span-2 overflow-hidden p-0">
                @if($customer->documents->isEmpty())
                    <div class="px-6 py-14 text-center text-sm text-gp-muted">Aucun document.</div>
                @else
                    <ul class="divide-y divide-gp-border dark:divide-white/10">
                        @foreach($customer->documents as $document)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                                <div>
                                    <p class="font-semibold">{{ $document->title }}</p>
                                    <p class="text-xs text-gp-muted">{{ $document->category }} · {{ $document->created_at?->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ $document->url() }}" target="_blank" class="text-xs font-semibold text-gp-primary hover:underline">Ouvrir</a>
                                    @can('customers.update')
                                        <form method="post" action="{{ route('customers.documents.destroy', [$customer, $document]) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-rose-600">Supprimer</button></form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
            @can('customers.update')
                <aside class="gp-card">
                    <h2 class="mb-3 text-sm font-bold">Ajouter un document</h2>
                    <form method="post" action="{{ route('customers.documents.store', $customer) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="text" name="title" placeholder="Titre" class="w-full rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <select name="category" class="w-full rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                            <option value="id">Pièce d’identité</option>
                            <option value="contract">Contrat</option>
                            <option value="other">Autre</option>
                        </select>
                        <input type="file" name="document" required class="block w-full text-sm text-gp-muted">
                        <button class="gp-btn-primary w-full">Uploader</button>
                    </form>
                </aside>
            @endcan
        </section>
    @endif

    @if($tab === 'stats')
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA cumulé</p><p class="mt-2 text-2xl font-bold text-gp-primary">{{ number_format($customer->lifetime_revenue, 2, ',', ' ') }}</p></article>
            <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Solde</p><p class="mt-2 text-2xl font-bold">{{ number_format($customer->balance, 2, ',', ' ') }}</p></article>
            <article class="gp-kpi"><p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Dernier achat</p><p class="mt-2 text-xl font-bold">{{ $customer->last_purchase_at?->format('d/m/Y') ?: '—' }}</p></article>
        </section>
    @endif
@endsection
