@php $isEdit = isset($company); @endphp
<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Identité</h2>
    </div>
    @if($isEdit && $company->logoUrl())
        <div class="flex items-center gap-4">
            <img src="{{ $company->logoUrl() }}" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-gp-border" alt="">
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
            <label class="gp-label">Nom commercial *</label>
            <input type="text" name="name" value="{{ old('name', $company->name ?? '') }}" class="gp-input" required>
        </div>
        <div>
            <label class="gp-label">Raison sociale</label>
            <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name ?? '') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Secteur d'activité</label>
            <input type="text" name="activity" value="{{ old('activity', $company->activity ?? '') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Site web</label>
            <input type="text" name="website" value="{{ old('website', $company->website ?? '') }}" class="gp-input" placeholder="https://">
        </div>
    </div>
</article>

<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Coordonnées</h2>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="gp-label">Adresse</label>
            <input type="text" name="address" value="{{ old('address', $company->address ?? '') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Ville</label>
            <input type="text" name="city" value="{{ old('city', $company->city ?? '') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Région</label>
            <input type="text" name="region" value="{{ old('region', $company->region ?? '') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Pays</label>
            <input type="text" name="country" value="{{ old('country', $company->country ?? 'Maroc') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Téléphone</label>
            <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '') }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $company->email ?? '') }}" class="gp-input">
        </div>
    </div>
</article>

<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Localisation & devise</h2>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="gp-label">Devise</label>
            <input type="text" name="currency" value="{{ old('currency', $company->currency ?? 'MAD') }}" class="gp-input" maxlength="3">
        </div>
        <div>
            <label class="gp-label">Langue</label>
            <select name="locale" class="gp-input">
                @foreach(['fr' => 'Français', 'ar' => 'Arabe', 'en' => 'English'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('locale', $company->locale ?? 'fr') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Fuseau horaire</label>
            <input type="text" name="timezone" value="{{ old('timezone', $company->timezone ?? 'Africa/Casablanca') }}" class="gp-input">
        </div>
    </div>
</article>
