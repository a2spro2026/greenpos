@extends('layouts.admin')

@section('title', 'Modifier '.$company->name)
@section('breadcrumb', 'Entreprises')
@section('heading', 'Modifier — '.$company->name)
@section('actions')
    <a href="{{ route('admin.companies.show', $company) }}" class="pa-btn pa-btn-ghost">Retour</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.companies.update', $company) }}" class="pa-card space-y-4 max-w-3xl">
    @csrf
    @method('PUT')
    <div class="pa-grid-2">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Nom</label>
            <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Activité</label>
            <input type="text" name="activity" value="{{ old('activity', $company->activity) }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Email</label>
            <input type="email" name="email" value="{{ old('email', $company->email) }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Téléphone</label>
            <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="pa-input">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Adresse</label>
            <input type="text" name="address" value="{{ old('address', $company->address) }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Pays</label>
            <input type="text" name="country" value="{{ old('country', $company->country) }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Ville</label>
            <input type="text" name="city" value="{{ old('city', $company->city) }}" class="pa-input">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Statut</label>
            <select name="status" class="pa-select">
                @foreach(['active','inactive','archived'] as $st)
                    <option value="{{ $st }}" @selected(old('status', $company->status) === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Plan</label>
            <select name="saas_plan_id" class="pa-select">
                <option value="">— conserver —</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((string) old('saas_plan_id', $tenant?->currentSubscription?->saas_plan_id) === (string) $plan->id)>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <button class="pa-btn pa-btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
