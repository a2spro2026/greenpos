@extends('layouts.app')
@section('title', 'Providers AI')
@section('content')
<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <a href="{{ route('ai.dashboard') }}" class="text-xs font-semibold text-teal-600 hover:underline">← Dashboard AI</a>
        <h1 class="mt-1 text-2xl font-extrabold text-gp-text">Providers LLM</h1>
        <p class="mt-1 text-sm text-gp-muted">Architecture extensible · OpenAI · Azure · Claude · Gemini · Mistral · Ollama · Local</p>
    </div>
</div>
<div class="grid gap-4 lg:grid-cols-2">
@foreach($providers as $p)
    <article class="gp-card p-5">
        <div class="mb-3 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-bold text-gp-text">{{ $p->name }}</h2>
                <p class="sa-mono text-xs text-gp-muted">{{ $p->code }}</p>
            </div>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $p->is_enabled ? 'bg-emerald-500/15 text-emerald-700' : 'bg-slate-500/10 text-slate-500' }}">{{ $p->status }}</span>
        </div>
        <form method="POST" action="{{ route('ai.providers.update', $p) }}" class="space-y-3">
            @csrf @method('PUT')
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_enabled" value="1" {{ $p->is_enabled ? 'checked' : '' }} {{ $p->code === 'local' ? 'checked' : '' }}> Activé</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" {{ $p->is_default ? 'checked' : '' }}> Par défaut</label>
            <div><label class="mb-1 block text-xs text-gp-muted">Modèle</label><input name="model" value="{{ $p->model }}" class="gp-input"></div>
            <div><label class="mb-1 block text-xs text-gp-muted">Base URL</label><input name="base_url" value="{{ $p->base_url }}" class="gp-input" placeholder="optionnel"></div>
            @if($p->code !== 'local')
                <div><label class="mb-1 block text-xs text-gp-muted">API Key</label><input name="api_key" type="password" class="gp-input" placeholder="••••••••"></div>
            @else
                <p class="text-xs text-gp-muted">Moteur embarqué — fonctionne sans clé. Les autres providers basculent ici si non configurés.</p>
            @endif
            <button class="gp-btn-primary">Enregistrer</button>
        </form>
    </article>
@endforeach
</div>
@endsection
