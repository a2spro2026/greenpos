@extends('layouts.app')

@section('title', 'GreenPOS AI')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600 dark:text-teal-400">Intelligence</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-gp-text">GreenPOS AI</h1>
        <p class="mt-1 text-sm text-gp-muted">Assistant intelligent · conversations · insights · automatisations validées</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('ai.providers') }}" class="gp-btn-secondary">Providers LLM</a>
        <button type="button" class="gp-btn-primary" onclick="window.GreenPosAI?.open()">Ouvrir l’assistant</button>
    </div>
</div>

<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="gp-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Conversations</p>
        <p class="mt-2 text-3xl font-bold text-gp-text">{{ $stats['conversation_count'] }}</p>
    </article>
    <article class="gp-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Messages</p>
        <p class="mt-2 text-3xl font-bold text-gp-text">{{ $stats['message_count'] }}</p>
    </article>
    <article class="gp-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Suggestions</p>
        <p class="mt-2 text-3xl font-bold text-teal-600">{{ $stats['suggestion_count'] }}</p>
    </article>
    <article class="gp-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">CA du mois</p>
        <p class="mt-2 text-2xl font-bold text-gp-text">{{ number_format($stats['insights']['revenue']['current'] ?? 0, 0, ',', ' ') }} <span class="text-sm text-gp-muted">MAD</span></p>
        <p class="mt-1 text-xs {{ ($stats['insights']['revenue']['delta_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $stats['insights']['revenue']['delta_pct'] ?? 0 }}% vs mois préc.</p>
    </article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-3">
    <article class="gp-card xl:col-span-2 overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gp-border px-5 py-4">
            <h2 class="text-sm font-bold text-gp-text">Conversations récentes</h2>
        </div>
        <ul class="divide-y divide-gp-border">
            @forelse($stats['conversations'] as $c)
                <li>
                    <a href="{{ route('ai.conversations.show', $c) }}" class="flex items-center justify-between gap-3 px-5 py-3 transition hover:bg-gp-surface-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gp-text">{{ $c->title ?: 'Conversation' }}</p>
                            <p class="text-xs text-gp-muted">{{ $c->prompt?->name ?? 'Assistant' }} · {{ $c->context_module }} · {{ optional($c->last_message_at)->diffForHumans() }}</p>
                        </div>
                        <span class="rounded-full bg-teal-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-teal-700">{{ $c->message_count }} msg</span>
                    </a>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-sm text-gp-muted">Aucune conversation — ouvrez l’assistant flottant.</li>
            @endforelse
        </ul>
    </article>

    <article class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Personas</h2></div>
        <ul class="divide-y divide-gp-border">
            @foreach($stats['prompts'] as $p)
                <li class="px-5 py-3">
                    <p class="font-semibold text-gp-text">{{ $p->icon }} {{ $p->name }}</p>
                    <p class="text-xs text-gp-muted">{{ $p->personaLabel() }}</p>
                </li>
            @endforeach
        </ul>
    </article>
</section>

<section class="mb-6 grid gap-4 xl:grid-cols-2">
    <article class="gp-card overflow-hidden p-0">
        <div class="border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Suggestions & recommandations</h2></div>
        <ul class="divide-y divide-gp-border">
            @forelse($stats['suggestions'] as $s)
                <li class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-teal-600">{{ $s->category }}</p>
                            <p class="mt-1 font-semibold text-gp-text">{{ $s->title }}</p>
                            <p class="mt-1 text-sm text-gp-muted">{{ $s->body }}</p>
                            @if($s->action_url)
                                <a href="{{ $s->action_url }}" class="mt-2 inline-block text-xs font-semibold text-teal-600 hover:underline">{{ $s->action_label ?: 'Ouvrir' }} →</a>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('ai.suggestions.dismiss', $s) }}">@csrf<button class="text-xs text-gp-muted hover:text-gp-text">Masquer</button></form>
                    </div>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-gp-muted">Aucune suggestion</li>
            @endforelse
        </ul>
    </article>

    <article class="gp-card p-5">
        <h2 class="mb-4 text-sm font-bold">Insights métier</h2>
        <div class="space-y-4 text-sm">
            <div>
                <p class="text-xs font-semibold uppercase text-gp-muted">Top produits</p>
                <ul class="mt-2 space-y-1">
                    @forelse($stats['insights']['top_products'] as $p)
                        <li class="flex justify-between"><span>{{ $p['name'] }}</span><span class="text-gp-muted">{{ number_format($p['revenue'], 0, ',', ' ') }}</span></li>
                    @empty
                        <li class="text-gp-muted">Pas assez de données</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gp-muted">Ruptures possibles</p>
                <ul class="mt-2 space-y-1">
                    @forelse($stats['insights']['low_stock'] as $p)
                        <li>{{ $p['name'] }} <span class="text-rose-600">({{ $p['qty'] }}/{{ $p['min'] }})</span></li>
                    @empty
                        <li class="text-gp-muted">Stock OK</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </article>
</section>

<section class="gp-card overflow-hidden p-0">
    <div class="border-b border-gp-border px-5 py-4"><h2 class="text-sm font-bold">Historique des automatisations</h2></div>
    <div class="overflow-x-auto">
        <table class="gp-table">
            <thead><tr><th>Action</th><th>Statut</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($stats['actions'] as $a)
                <tr>
                    <td class="font-medium">{{ $a->action_type }}</td>
                    <td>{{ $a->status }}</td>
                    <td class="text-gp-muted">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-10 text-center text-gp-muted">Aucune action proposée</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
