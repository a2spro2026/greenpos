@php $isEdit = isset($user); @endphp

<form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="gp-card mb-6">
        <h2 class="mb-4 text-sm font-bold">Informations personnelles</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="gp-label">Prénom *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $isEdit ? $user->first_name : '') }}" class="gp-input w-full" required>
            </div>
            <div>
                <label class="gp-label">Nom *</label>
                <input type="text" name="last_name" value="{{ old('last_name', $isEdit ? $user->last_name : '') }}" class="gp-input w-full" required>
            </div>
            <div>
                <label class="gp-label">Photo</label>
                <input type="file" name="photo" accept="image/*" class="gp-input w-full">
                @if($isEdit && $user->photoUrl())
                    <img src="{{ $user->photoUrl() }}" alt="" class="mt-2 h-14 w-14 rounded-full object-cover">
                @endif
            </div>
            <div>
                <label class="gp-label">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone', $isEdit ? $user->phone : '') }}" class="gp-input w-full">
            </div>
            <div>
                <label class="gp-label">Email *</label>
                <input type="email" name="email" value="{{ old('email', $isEdit ? $user->email : '') }}" class="gp-input w-full" required>
            </div>
        </div>
    </section>

    <section class="gp-card mb-6">
        <h2 class="mb-4 text-sm font-bold">Informations professionnelles</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="gp-label">Fonction</label>
                <input type="text" name="job_title" value="{{ old('job_title', $isEdit ? $user->job_title : '') }}" class="gp-input w-full" placeholder="Ex. Responsable caisse">
            </div>
            <div>
                <label class="gp-label">Département</label>
                <select name="department" class="gp-select w-full">
                    <option value="">—</option>
                    @foreach($departments as $k => $v)
                        <option value="{{ $k }}" {{ old('department', $isEdit ? $user->department : '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gp-label">Date d'embauche</label>
                <input type="date" name="hired_at" value="{{ old('hired_at', $isEdit && $user->hired_at ? $user->hired_at->format('Y-m-d') : '') }}" class="gp-input w-full">
            </div>
            <div class="sm:col-span-2 xl:col-span-3">
                <label class="gp-label">Boutiques</label>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @php $selectedStores = old('store_ids', $isEdit ? $user->stores->where('company_id', $company->id)->pluck('id')->all() : []); @endphp
                    @foreach($stores as $st)
                        <label class="flex items-center gap-2 rounded-xl border border-gp-border px-3 py-2 text-sm dark:border-white/10">
                            <input type="checkbox" name="store_ids[]" value="{{ $st->id }}" {{ in_array($st->id, $selectedStores) ? 'checked' : '' }} class="rounded border-gp-border text-gp-primary">
                            {{ $st->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="gp-card mb-6">
        <h2 class="mb-4 text-sm font-bold">Compte</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="gp-label">Nom d'utilisateur</label>
                <input type="text" name="username" value="{{ old('username', $isEdit ? $user->username : '') }}" class="gp-input w-full" placeholder="optionnel">
            </div>
            <div>
                <label class="gp-label">Mot de passe {{ $isEdit ? '' : '*' }}</label>
                <input type="password" name="password" class="gp-input w-full" {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
                @if($isEdit)<p class="mt-1 text-xs text-gp-muted">Laisser vide pour conserver.</p>@endif
            </div>
            <div>
                <label class="gp-label">Confirmation {{ $isEdit ? '' : '*' }}</label>
                <input type="password" name="password_confirmation" class="gp-input w-full" {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
            </div>
        </div>
    </section>

    <section class="gp-card mb-6">
        <h2 class="mb-4 text-sm font-bold">Accès</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="gp-label">Rôle *</label>
                <select name="role" class="gp-select w-full" required>
                    @foreach($roles as $k => $v)
                        <option value="{{ $k }}" {{ old('role', $isEdit ? $user->roleIn($company) : 'sales') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gp-label">Statut *</label>
                <select name="status" class="gp-select w-full" required>
                    @foreach($statuses as $k => $v)
                        @if($k !== 'invited')
                            <option value="{{ $k }}" {{ old('status', $isEdit ? $user->status : 'active') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap justify-end gap-3">
        <a href="{{ $isEdit ? route('users.show', $user) : route('users.index') }}" class="gp-btn-secondary">Annuler</a>
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer l\'utilisateur' }}</button>
    </div>
</form>
