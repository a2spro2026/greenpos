@extends('layouts.app')
@section('title', $mod['name'].' — Modules')
@section('breadcrumb', 'Catalogue des Modules')
@section('heading', $mod['name'])
@section('subtitle', $mod['description'])
@section('content')
@vite(['resources/css/modules.css'])

@php
    $stars = function (float $rating): string {
        $full = (int) round($rating);
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $out .= $i <= $full ? '★' : '☆';
        }
        return $out;
    };
@endphp

<div class="ms-detail" style="--ms-accent: {{ $mod['color'] ?? '#0d9488' }}">
    <a href="{{ route('modules.index') }}" class="ms-back">← Retour au catalogue</a>

    <header class="ms-hero">
        <div class="ms-hero-banner" aria-hidden="true"></div>
        <div class="ms-hero-body">
            <span class="ms-icon ms-icon--xl">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $mod['icon_path'] }}"/></svg>
            </span>
            <div class="ms-hero-copy">
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
                <h2 class="ms-hero-title">{{ $mod['name'] }}</h2>
                <p class="ms-hero-sub">{{ $mod['long_description'] }}</p>
                <div class="ms-hero-meta">
                    <span>Par <strong>{{ $mod['developer'] }}</strong></span>
                    <span>v{{ $mod['version'] }}</span>
                    <span class="ms-stars">{{ $stars($mod['rating']) }}</span>
                    <span>{{ number_format($mod['installs'], 0, ',', ' ') }} entreprises</span>
                </div>
                <div class="ms-hero-actions">
                    @if($mod['action'] === 'deactivate')
                        <form method="POST" action="{{ route('modules.toggle', $mod['key']) }}">
                            @csrf
                            <input type="hidden" name="enable" value="0">
                            <button type="submit" class="ms-btn ms-btn--off ms-btn--lg">Désactiver</button>
                        </form>
                    @elseif($mod['action'] === 'activate')
                        <form method="POST" action="{{ route('modules.toggle', $mod['key']) }}">
                            @csrf
                            <input type="hidden" name="enable" value="1">
                            <button type="submit" class="ms-btn ms-btn--on ms-btn--lg">Installer</button>
                        </form>
                    @elseif($mod['action'] === 'upgrade')
                        <a href="{{ route('site.pricing') }}" class="ms-btn ms-btn--upgrade ms-btn--lg" target="_blank" rel="noopener">Passer à Business</a>
                    @elseif($mod['action'] === 'locked')
                        <button type="button" class="ms-btn ms-btn--locked ms-btn--lg" disabled>Installé</button>
                    @else
                        <button type="button" class="ms-btn ms-btn--soon ms-btn--lg" disabled>Bientôt disponible</button>
                    @endif
                    <a href="{{ route('modules.index') }}" class="ms-btn ms-btn--ghost ms-btn--lg">Catalogue</a>
                </div>
            </div>
        </div>
    </header>

    <div class="ms-detail-grid">
        <section class="ms-panel">
            <h3 class="ms-panel-title">Fonctionnalités</h3>
            <ul class="ms-feature-list">
                @forelse($mod['features'] as $feature)
                    <li>{{ $feature }}</li>
                @empty
                    <li>Fonctionnalités détaillées à venir.</li>
                @endforelse
            </ul>
        </section>

        <aside class="ms-side">
            <section class="ms-panel">
                <h3 class="ms-panel-title">Compatibilité</h3>
                <div class="ms-compat-pills">
                    @foreach($mod['compatibility'] as $planName)
                        <span class="ms-pill">{{ $planName }}</span>
                    @endforeach
                </div>
            </section>
            <section class="ms-panel">
                <h3 class="ms-panel-title">Prérequis</h3>
                @if(count($mod['prerequisite_modules']))
                    <ul class="ms-mini-list">
                        @foreach($mod['prerequisite_modules'] as $pre)
                            <li><a href="{{ route('modules.show', $pre['key']) }}">{{ $pre['name'] }}</a></li>
                        @endforeach
                    </ul>
                @else
                    <p class="ms-muted">Aucun prérequis.</p>
                @endif
            </section>
            <section class="ms-panel">
                <h3 class="ms-panel-title">Version</h3>
                <p class="ms-muted">v{{ $mod['version'] }} · Développeur GreenPOS</p>
            </section>
        </aside>
    </div>

    <section class="ms-panel ms-panel--wide">
        <h3 class="ms-panel-title">Captures d’écran</h3>
        <div class="ms-shots">
            @for($i = 1; $i <= (int) ($mod['screenshots'] ?? 3); $i++)
                <div class="ms-shot" data-shot="{{ $i }}">
                    <div class="ms-shot-chrome">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="ms-shot-body">
                        <div class="ms-shot-bar"></div>
                        <div class="ms-shot-grid">
                            <div></div><div></div><div></div>
                        </div>
                    </div>
                    <span class="ms-shot-label">Aperçu {{ $i }}</span>
                </div>
            @endfor
        </div>
    </section>

    <section class="ms-panel ms-panel--wide">
        <h3 class="ms-panel-title">Historique des mises à jour</h3>
        <ol class="ms-changelog">
            @foreach($mod['changelog'] as $entry)
                <li>
                    <div class="ms-change-head">
                        <strong>v{{ $entry['version'] }}</strong>
                        <time>{{ $entry['date'] }}</time>
                    </div>
                    <p>{{ $entry['notes'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @if(count($mod['related_modules']))
        <section class="ms-panel ms-panel--wide">
            <h3 class="ms-panel-title">Modules associés</h3>
            <div class="ms-related">
                @foreach($mod['related_modules'] as $rel)
                    <a href="{{ route('modules.show', $rel['key']) }}" class="ms-related-card" style="--ms-accent: {{ $rel['color'] ?? '#0d9488' }}">
                        <span class="ms-icon ms-icon--sm">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $rel['icon_path'] }}"/></svg>
                        </span>
                        <div>
                            <strong>{{ $rel['name'] }}</strong>
                            <span>{{ $rel['category'] }} · v{{ $rel['version'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
