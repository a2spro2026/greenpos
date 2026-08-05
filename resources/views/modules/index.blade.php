@extends('layouts.app')
@section('title', 'Catalogue des Modules')
@section('breadcrumb', 'Administration')
@section('heading', 'Catalogue des Modules')
@section('subtitle', 'Construisez votre ERP en activant uniquement les fonctionnalités dont votre entreprise a besoin.')
@section('content')
@vite(['resources/css/modules.css', 'resources/js/modules.js'])

@php
    $stars = function (float $rating): string {
        $full = (int) round($rating);
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $out .= $i <= $full ? '★' : '☆';
        }
        return $out;
    };
    $fmtInstalls = fn (int $n) => $n >= 1000 ? number_format($n / 1000, 1, ',', ' ').' k' : (string) $n;
@endphp

<div class="ms-store" data-module-store>
    <section class="ms-stats">
        <article class="ms-stat">
            <span class="ms-stat-label">Modules installés</span>
            <strong class="ms-stat-value ms-stat-value--ok">{{ $stats['installed'] }}</strong>
        </article>
        <article class="ms-stat">
            <span class="ms-stat-label">Modules disponibles</span>
            <strong class="ms-stat-value">{{ $stats['available'] }}</strong>
        </article>
        <article class="ms-stat">
            <span class="ms-stat-label">Modules Premium</span>
            <strong class="ms-stat-value ms-stat-value--premium">{{ $stats['premium'] }}</strong>
        </article>
        <article class="ms-stat">
            <span class="ms-stat-label">Dernières mises à jour</span>
            <strong class="ms-stat-value ms-stat-value--new">{{ $stats['updated'] }}</strong>
        </article>
    </section>

    @if($plan)
        <p class="ms-plan-hint">Plan actif : <strong>{{ $plan->name }}</strong> — les modules hors offre affichent un accès Premium.</p>
    @else
        <p class="ms-plan-hint ms-plan-hint--demo">Mode démonstration — tous les modules du catalogue sont installables.</p>
    @endif

    <div class="ms-toolbar">
        <label class="ms-search">
            <svg class="ms-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Rechercher un module..." data-ms-search autocomplete="off">
        </label>
    </div>

    <div class="ms-chips" role="tablist" aria-label="Catégories">
        @foreach($filtersList as $chip)
            <button
                type="button"
                class="ms-chip {{ ($filters['category'] ?: 'Tous') === $chip ? 'is-active' : '' }}"
                data-ms-chip="{{ $chip }}"
                role="tab"
                aria-selected="{{ ($filters['category'] ?: 'Tous') === $chip ? 'true' : 'false' }}"
            >{{ $chip }}</button>
        @endforeach
    </div>

    <div class="ms-grid" data-ms-grid>
        @forelse($catalog as $mod)
            <article
                class="ms-card {{ $mod['is_enabled'] ? 'is-on' : '' }} {{ $mod['action'] === 'upgrade' ? 'is-premium' : '' }}"
                data-ms-card
                data-name="{{ strtolower($mod['name'].' '.$mod['description'].' '.$mod['category']) }}"
                data-category="{{ $mod['category'] }}"
                style="--ms-accent: {{ $mod['color'] ?? '#0d9488' }}"
            >
                <a href="{{ route('modules.show', $mod['key']) }}" class="ms-card-link" aria-label="Voir {{ $mod['name'] }}">
                    <div class="ms-card-top">
                        <span class="ms-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $mod['icon_path'] }}"/></svg>
                        </span>
                        <div class="ms-card-badges">
                            @foreach($mod['store_badges'] as $badge)
                                <span class="ms-badge ms-badge--{{ $badge }}">
                                    @switch($badge)
                                        @case('installe') Installé @break
                                        @case('disponible') Disponible @break
                                        @case('premium') Premium @break
                                        @case('nouveau') Nouveau @break
                                        @case('updated') Mis à jour @break
                                        @case('bientot') Bientôt @break
                                        @default {{ ucfirst($badge) }}
                                    @endswitch
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <h3 class="ms-card-title">{{ $mod['name'] }}</h3>
                    <p class="ms-card-desc">{{ $mod['description'] }}</p>
                    <div class="ms-card-meta">
                        <span class="ms-cat">{{ $mod['category'] }}</span>
                        <span>v{{ $mod['version'] }}</span>
                    </div>
                    <div class="ms-card-dev">
                        <span>Par <strong>{{ $mod['developer'] }}</strong></span>
                        <span class="ms-compat">{{ implode(' · ', $mod['compatibility']) }}</span>
                    </div>
                    <div class="ms-card-rating">
                        <span class="ms-stars" aria-label="Note {{ $mod['rating'] }}/5">{{ $stars($mod['rating']) }}</span>
                        <span class="ms-installs">{{ $fmtInstalls($mod['installs']) }} entreprises</span>
                    </div>
                </a>

                <div class="ms-card-actions">
                    @if($mod['action'] === 'deactivate')
                        <form method="POST" action="{{ route('modules.toggle', $mod['key']) }}">
                            @csrf
                            <input type="hidden" name="enable" value="0">
                            <button type="submit" class="ms-btn ms-btn--off">Désactiver</button>
                        </form>
                    @elseif($mod['action'] === 'activate')
                        <form method="POST" action="{{ route('modules.toggle', $mod['key']) }}">
                            @csrf
                            <input type="hidden" name="enable" value="1">
                            <button type="submit" class="ms-btn ms-btn--on">Activer</button>
                        </form>
                    @elseif($mod['action'] === 'upgrade')
                        <a href="{{ route('site.pricing') }}" class="ms-btn ms-btn--upgrade" target="_blank" rel="noopener">Passer à Business</a>
                    @elseif($mod['action'] === 'locked')
                        <button type="button" class="ms-btn ms-btn--locked" disabled>Installé</button>
                    @else
                        <button type="button" class="ms-btn ms-btn--soon" disabled>Bientôt</button>
                    @endif
                    <a href="{{ route('modules.show', $mod['key']) }}" class="ms-btn ms-btn--ghost">Détails</a>
                </div>
            </article>
        @empty
            <p class="ms-empty" data-ms-empty>Aucun module ne correspond à votre recherche.</p>
        @endforelse
        <p class="ms-empty is-hidden" data-ms-empty-filter hidden>Aucun module dans cette catégorie.</p>
    </div>
</div>
@endsection
