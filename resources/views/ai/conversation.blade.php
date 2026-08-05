@extends('layouts.app')
@section('title', $conversation->title ?: 'Conversation AI')
@section('content')
<div class="mb-4 flex items-center justify-between gap-3">
    <div>
        <a href="{{ route('ai.dashboard') }}" class="text-xs font-semibold text-teal-600 hover:underline">← Dashboard AI</a>
        <h1 class="mt-1 text-xl font-bold text-gp-text">{{ $conversation->title }}</h1>
        <p class="text-xs text-gp-muted">{{ $conversation->prompt?->name }} · {{ $conversation->context_module }} · {{ $conversation->provider }}</p>
    </div>
    <button type="button" class="gp-btn-primary" onclick="window.GreenPosAI?.open({{ $conversation->id }})">Continuer</button>
</div>
<div class="gp-card space-y-4 p-5">
    @foreach($conversation->messages as $m)
        <div class="{{ $m->role === 'user' ? 'ml-8 rounded-2xl bg-teal-600/10 px-4 py-3' : 'mr-8 rounded-2xl bg-gp-surface-2 px-4 py-3' }}">
            <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-gp-muted">{{ $m->role }}</p>
            <div class="prose prose-sm max-w-none text-gp-text whitespace-pre-wrap">{{ $m->content }}</div>
        </div>
    @endforeach
</div>
@endsection
