@php
    $isEdit = isset($store);
    $hours = old('opening_hours_summary', $isEdit ? ($store->opening_hours['summary'] ?? '') : '');
    $local = $isEdit ? ($store->local_settings ?? []) : [];
    $selectedUsers = old('user_ids', $isEdit ? $store->users->pluck('id')->all() : []);
@endphp

<div class="space-y-6">
    <article class="gp-card space-y-5">
        <div class="border-b border-gp-border pb-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Identité</h2>
        </div>
        @if($isEdit)
            <div class="flex items-center gap-4">
                @if($store->logoUrl())
                    <img src="{{ $store->logoUrl() }}" alt="" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-gp-border">
                @else
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gp-primary-soft text-lg font-bold text-gp-primary">{{ $store->initials() }}</span>
                @endif
                <div class="flex-1">
                    <label class="gp-label">Logo</label>
                    <input type="file" name="logo" accept="image/*" class="gp-input">
                </div>
            </div>
        @else
            <div>
                <label class="gp-label">Logo</label>
                <input type="file" name="logo" accept="image/*" class="gp-input">
            </div>
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="gp-label">Nom *</label>
                <input type="text" name="name" value="{{ old('name', $store->name ?? '') }}" class="gp-input" required>
            </div>
            <div>
                <label class="gp-label">Code</label>
                <input type="text" name="code" value="{{ old('code', $store->code ?? '') }}" class="gp-input">
            </div>
            <div class="sm:col-span-2">
                <label class="gp-label">Adresse</label>
                <input type="text" name="address" value="{{ old('address', $store->address ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Ville</label>
                <input type="text" name="city" value="{{ old('city', $store->city ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Région</label>
                <input type="text" name="region" value="{{ old('region', $store->region ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Pays</label>
                <input type="text" name="country" value="{{ old('country', $store->country ?? 'Maroc') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Code postal</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $store->postal_code ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone', $store->phone ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $store->email ?? '') }}" class="gp-input">
            </div>
        </div>
    </article>

    <article class="gp-card space-y-5">
        <div class="border-b border-gp-border pb-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Responsable & horaires</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="gp-label">Responsable</label>
                <select name="manager_user_id" class="gp-input">
                    <option value="">—</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('manager_user_id', $store->manager_user_id ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gp-label">Horaires d'ouverture</label>
                <input type="text" name="opening_hours_summary" value="{{ $hours }}" class="gp-input" placeholder="Lun–Sam 9h–19h">
            </div>
            <div>
                <label class="gp-label">Latitude (carte préparée)</label>
                <input type="text" name="latitude" value="{{ old('latitude', $store->latitude ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $store->longitude ?? '') }}" class="gp-input">
            </div>
        </div>
    </article>

    <article class="gp-card space-y-5">
        <div class="border-b border-gp-border pb-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Utilisateurs autorisés</h2>
            <p class="text-xs text-gp-muted">Limite l'accès pour les rôles « boutique seule »</p>
        </div>
        <div class="grid max-h-56 gap-2 overflow-y-auto sm:grid-cols-2">
            @foreach($users as $user)
                <label class="inline-flex items-center gap-2 rounded-xl bg-gp-bg px-3 py-2 text-sm dark:bg-white/5">
                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="rounded border-gp-border" @checked(in_array($user->id, $selectedUsers, false) || in_array((string)$user->id, $selectedUsers, true))>
                    {{ $user->name }}
                </label>
            @endforeach
        </div>
    </article>

    <article class="gp-card space-y-5">
        <div class="border-b border-gp-border pb-4 dark:border-white/10">
            <h2 class="text-sm font-bold">Paramètres locaux</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="gp-label">Imprimante locale</label>
                <input type="text" name="local_default_printer" value="{{ old('local_default_printer', $local['default_printer'] ?? '') }}" class="gp-input">
            </div>
            <div>
                <label class="gp-label">Pied de ticket local</label>
                <input type="text" name="local_receipt_footer" value="{{ old('local_receipt_footer', $local['receipt_footer'] ?? '') }}" class="gp-input">
            </div>
            <div class="sm:col-span-2">
                <label class="gp-label">Notes</label>
                <textarea name="notes" rows="2" class="gp-input">{{ old('notes', $store->notes ?? '') }}</textarea>
            </div>
            <div class="flex flex-wrap gap-4 sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gp-border" @checked(old('is_active', $store->is_active ?? true))>
                    Active
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-gp-border" @checked(old('is_default', $store->is_default ?? false))>
                    Boutique par défaut
                </label>
            </div>
        </div>
    </article>
</div>
