@extends('layouts.app')

@section('title', 'Paramètres')
@section('breadcrumb', 'Administration / Paramètres')
@section('heading', 'Paramètres généraux')
@section('subtitle', 'Centre de configuration de votre entreprise GreenPOS.')

@section('content')
    <div class="flex flex-col gap-6 lg:flex-row">
        @include('settings._nav', ['section' => 'index'])

        <div class="min-w-0 flex-1 space-y-6">
            <section class="grid gap-4 sm:grid-cols-3">
                <article class="gp-kpi">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Entreprise</p>
                    <p class="mt-2 truncate text-xl font-bold">{{ $company->name }}</p>
                    <p class="mt-1 text-xs text-gp-muted">{{ $company->currency }} · {{ $company->timezone }}</p>
                </article>
                <article class="gp-kpi">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Boutiques</p>
                    <p class="mt-2 text-3xl font-bold">{{ $storesCount }}</p>
                </article>
                <article class="gp-kpi">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gp-muted">Sections configurées</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $configured }} <span class="text-base font-medium text-gp-muted">/ 12</span></p>
                </article>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($sections as $s)
                    @php
                        $href = isset($s['params']) ? route($s['route'], $s['params']) : route($s['route']);
                    @endphp
                    <a href="{{ $href }}" class="settings-tile group">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-gp-text group-hover:text-gp-primary dark:text-white">{{ $s['label'] }}</h3>
                                <p class="mt-1 text-sm text-gp-muted">{{ $s['desc'] }}</p>
                            </div>
                            <span class="settings-tile-arrow">→</span>
                        </div>
                    </a>
                @endforeach
            </section>
        </div>
    </div>
@endsection
