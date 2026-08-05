@extends('layouts.admin')
@section('title', 'Modules')
@section('breadcrumb', 'Plateforme')
@section('heading', 'Modules')
@section('content')
<div class="space-y-4">
@foreach($plans as $plan)
<form method="POST" action="{{ route('admin.modules.plan.update', $plan) }}" class="pa-card">
@csrf
@method('PUT')
<div class="mb-3 flex items-center justify-between">
<h2 class="font-bold text-white">{{ $plan->name }}</h2>
<button class="pa-btn pa-btn-primary" type="submit">Enregistrer</button>
</div>
<div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
@foreach($catalog as $key => $mod)
<label class="flex items-start gap-2 rounded-lg border border-white/5 px-3 py-2 text-sm">
<input type="checkbox" name="modules[]" value="{{ $key }}" class="mt-1" @checked(in_array($key, $plan->modules ?? [], true))>
<span>
<span class="font-semibold text-zinc-100">{{ $mod['name'] }}</span>
<span class="block text-xs text-zinc-500">{{ $mod['description'] ?? '' }}</span>
</span>
</label>
@endforeach
</div>
</form>
@endforeach
</div>
@endsection
