@extends('layouts.app')
@section('title', 'Pipeline CRM')
@section('breadcrumb', 'CRM Enterprise')
@section('heading', 'Pipeline commercial')
@section('subtitle', 'Glissez-déposez les opportunités entre les étapes.')
@section('actions')
    <a href="{{ route('crm.opportunities.create') }}" class="gp-btn-primary">Nouvelle opportunité</a>
@endsection
@section('content')
@include('crm._nav')
@vite(['resources/css/crm.css', 'resources/js/crm.js'])

<div id="crm-pipeline"
     class="crm-pipeline"
     data-move-url-template="{{ url('/crm/opportunities/__ID__/move') }}"
     data-csrf="{{ csrf_token() }}">
    @foreach($columns as $col)
        <section class="crm-column" data-stage="{{ $col['stage'] }}">
            <header class="crm-column-head">
                <span class="crm-dot {{ $col['color'] }}"></span>
                <div class="min-w-0 flex-1">
                    <h2>{{ $col['label'] }}</h2>
                    <p>{{ $col['count'] }} · {{ number_format($col['amount'], 0, ',', ' ') }} MAD</p>
                </div>
            </header>
            <div class="crm-column-body" data-dropzone>
                @foreach($col['items'] as $item)
                    <article class="crm-card" draggable="true" data-id="{{ $item->id }}">
                        <a href="{{ route('crm.opportunities.show', $item) }}" class="crm-card-title">{{ $item->name }}</a>
                        <p class="crm-card-meta">{{ $item->lead?->displayName() ?: ($item->customer?->name ?: '—') }}</p>
                        <div class="crm-card-foot">
                            <strong>{{ number_format($item->amount, 0, ',', ' ') }}</strong>
                            <span>{{ $item->probability }}%</span>
                        </div>
                        @if($item->owner)
                            <p class="crm-card-owner">{{ $item->owner->name ?: $item->owner->email }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
