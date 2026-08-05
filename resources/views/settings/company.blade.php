@extends('layouts.app')

@section('title', 'Entreprise')
@section('breadcrumb', 'Paramètres / Entreprise')
@section('heading', 'Informations de l\'entreprise')
@section('subtitle', 'Identité légale, logo et coordonnées fiscales.')

@section('content')
    <div class="flex flex-col gap-6 lg:flex-row">
        @include('settings._nav')

        <div class="min-w-0 flex-1">
            <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <article class="gp-card space-y-5">
                    <div class="flex items-center justify-between gap-3 border-b border-gp-border pb-4 dark:border-white/10">
                        <div>
                            <h2 class="text-sm font-bold">Identité</h2>
                            <p class="text-xs text-gp-muted">Nom commercial et raison sociale</p>
                        </div>
                        <span class="gp-badge">Profil</span>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gp-primary-soft ring-1 ring-gp-border dark:ring-white/10">
                            @if($company->logoUrl())
                                <img src="{{ $company->logoUrl() }}" alt="Logo" class="h-full w-full object-cover">
                            @else
                                <span class="text-2xl font-bold text-gp-primary">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="gp-label">Logo</label>
                            <input type="file" name="logo" accept="image/*" class="gp-input">
                            <p class="mt-1 text-xs text-gp-muted">PNG, JPG · max 2 Mo</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="gp-label">Nom *</label>
                            <input type="text" name="name" value="{{ old('name', $company->name) }}" class="gp-input" required>
                        </div>
                        <div>
                            <label class="gp-label">Raison sociale</label>
                            <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Activité</label>
                            <input type="text" name="activity" value="{{ old('activity', $company->activity) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Site web</label>
                            <input type="text" name="website" value="{{ old('website', $company->website) }}" class="gp-input" placeholder="https://">
                        </div>
                    </div>
                </article>

                <article class="gp-card space-y-5">
                    <div class="border-b border-gp-border pb-4 dark:border-white/10">
                        <h2 class="text-sm font-bold">Coordonnées</h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="gp-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $company->email) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Téléphone</label>
                            <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="gp-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="gp-label">Adresse</label>
                            <input type="text" name="address" value="{{ old('address', $company->address) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Ville</label>
                            <input type="text" name="city" value="{{ old('city', $company->city) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Région</label>
                            <input type="text" name="region" value="{{ old('region', $company->region) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Code postal</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Pays</label>
                            <input type="text" name="country" value="{{ old('country', $company->country ?? 'Maroc') }}" class="gp-input">
                        </div>
                    </div>
                </article>

                <article class="gp-card space-y-5">
                    <div class="border-b border-gp-border pb-4 dark:border-white/10">
                        <h2 class="text-sm font-bold">Identifiants fiscaux</h2>
                        <p class="text-xs text-gp-muted">ICE, IF, RC, Patente et autres</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="gp-label">ICE</label>
                            <input type="text" name="ice" value="{{ old('ice', $company->ice) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">IF</label>
                            <input type="text" name="if_number" value="{{ old('if_number', $company->if_number) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">RC</label>
                            <input type="text" name="rc" value="{{ old('rc', $company->rc) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Patente</label>
                            <input type="text" name="patente" value="{{ old('patente', $company->patente) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Identifiant fiscal</label>
                            <input type="text" name="tax_id" value="{{ old('tax_id', $company->tax_id) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">CNSS</label>
                            <input type="text" name="cnss" value="{{ old('cnss', $company->cnss) }}" class="gp-input">
                        </div>
                        <div>
                            <label class="gp-label">Devise</label>
                            <input type="text" name="currency" value="{{ old('currency', $company->currency) }}" class="gp-input" maxlength="3">
                        </div>
                        <div>
                            <label class="gp-label">Fuseau horaire</label>
                            <input type="text" name="timezone" value="{{ old('timezone', $company->timezone) }}" class="gp-input">
                        </div>
                    </div>
                </article>

                @can('settings.update')
                <div class="flex justify-end gap-2">
                    <button type="submit" class="gp-btn-primary">Enregistrer</button>
                </div>
                @endcan
            </form>
        </div>
    </div>
@endsection
