@php
    $isEdit = isset($supplier);
@endphp
<form method="post" action="{{ $isEdit ? route('suppliers.update', $supplier) : route('suppliers.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Informations générales</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Code</span>
                <input type="text" name="code" value="{{ old('code', $isEdit ? $supplier->code : ($suggestedCode ?? '')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                @error('code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Nom *</span>
                <input type="text" name="name" value="{{ old('name', $isEdit ? $supplier->name : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Société</span>
                <input type="text" name="company_name" value="{{ old('company_name', $isEdit ? $supplier->company_name : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Catégorie</span>
                <select name="category" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $isEdit ? $supplier->category : 'general') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Statut</span>
                <select name="status" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $isEdit ? $supplier->status : 'active') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Contact</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Téléphone</span><input type="text" name="phone" value="{{ old('phone', $isEdit ? $supplier->phone : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Mobile</span><input type="text" name="mobile" value="{{ old('mobile', $isEdit ? $supplier->mobile : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Email</span><input type="email" name="email" value="{{ old('email', $isEdit ? $supplier->email : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm sm:col-span-2"><span class="mb-1.5 block font-semibold">Site web</span><input type="text" name="website" value="{{ old('website', $isEdit ? $supplier->website : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Adresse</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm sm:col-span-2 lg:col-span-3"><span class="mb-1.5 block font-semibold">Adresse</span><input type="text" name="address" value="{{ old('address', $isEdit ? $supplier->address : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Ville</span><input type="text" name="city" value="{{ old('city', $isEdit ? $supplier->city : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Région</span><input type="text" name="region" value="{{ old('region', $isEdit ? $supplier->region : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Pays</span><input type="text" name="country" value="{{ old('country', $isEdit ? $supplier->country : 'Maroc') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Code postal</span><input type="text" name="postal_code" value="{{ old('postal_code', $isEdit ? $supplier->postal_code : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Informations commerciales</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Devise</span><input type="text" name="currency" value="{{ old('currency', $isEdit ? $supplier->currency : $currency) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Conditions de paiement</span><input type="text" name="payment_terms" value="{{ old('payment_terms', $isEdit ? $supplier->payment_terms : '') }}" placeholder="30 jours net" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Délai livraison (jours)</span><input type="number" name="delivery_delay_days" value="{{ old('delivery_delay_days', $isEdit ? $supplier->delivery_delay_days : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">N° fiscal</span><input type="text" name="tax_id" value="{{ old('tax_id', $isEdit ? $supplier->tax_id : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm sm:col-span-2 lg:col-span-3"><span class="mb-1.5 block font-semibold">Remarques</span><textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $supplier->notes : '') }}</textarea></label>
        </div>
    </section>

    @unless($isEdit)
        <section class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Documents</h2>
            <p class="mb-3 text-sm text-gp-muted">Contrats, attestations, RIB… (optionnel, max 10 Mo)</p>
            <input type="file" name="documents[]" multiple class="block w-full text-sm text-gp-muted">
        </section>
    @endunless

    <div class="flex flex-wrap gap-2">
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer le fournisseur' }}</button>
        <a href="{{ $isEdit ? route('suppliers.show', $supplier) : route('suppliers.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>
