@php
    $isEdit = isset($customer);
    $contacts = old('contacts', $isEdit ? $customer->contacts->map(fn ($c) => [
        'name' => $c->name,
        'role' => $c->role,
        'email' => $c->email,
        'phone' => $c->phone,
        'mobile' => $c->mobile,
        'is_primary' => $c->is_primary,
    ])->all() : [['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'mobile' => '', 'is_primary' => true]]);
@endphp
<form method="post" action="{{ $isEdit ? route('customers.update', $customer) : route('customers.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Informations générales</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Code</span><input type="text" name="code" value="{{ old('code', $isEdit ? $customer->code : ($suggestedCode ?? '')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">@error('code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Type</span>
                <select name="type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $isEdit ? $customer->type : 'individual') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Nom *</span><input type="text" name="name" value="{{ old('name', $isEdit ? $customer->name : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Société</span><input type="text" name="company_name" value="{{ old('company_name', $isEdit ? $customer->company_name : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Catégorie</span>
                <select name="category" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $isEdit ? $customer->category : 'standard') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Statut</span>
                <select name="status" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $isEdit ? $customer->status : 'active') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Boutique</span>
                <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $customer->store_id : $workspaceStore?->id) === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Contact</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Téléphone</span><input type="text" name="phone" value="{{ old('phone', $isEdit ? $customer->phone : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Mobile</span><input type="text" name="mobile" value="{{ old('mobile', $isEdit ? $customer->mobile : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Email</span><input type="email" name="email" value="{{ old('email', $isEdit ? $customer->email : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm sm:col-span-2"><span class="mb-1.5 block font-semibold">Site web</span><input type="text" name="website" value="{{ old('website', $isEdit ? $customer->website : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Adresse</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm sm:col-span-2 lg:col-span-3"><span class="mb-1.5 block font-semibold">Adresse</span><input type="text" name="address" value="{{ old('address', $isEdit ? $customer->address : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Ville</span><input type="text" name="city" value="{{ old('city', $isEdit ? $customer->city : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Région</span><input type="text" name="region" value="{{ old('region', $isEdit ? $customer->region : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Pays</span><input type="text" name="country" value="{{ old('country', $isEdit ? $customer->country : 'Maroc') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Code postal</span><input type="text" name="postal_code" value="{{ old('postal_code', $isEdit ? $customer->postal_code : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Informations commerciales</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Limite de crédit</span><input type="number" step="0.01" name="credit_limit" value="{{ old('credit_limit', $isEdit ? $customer->credit_limit : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Conditions de paiement</span><input type="text" name="payment_terms" value="{{ old('payment_terms', $isEdit ? $customer->payment_terms : '') }}" placeholder="Comptant / 30 jours" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Devise</span><input type="text" name="currency" value="{{ old('currency', $isEdit ? $customer->currency : $currency) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">N° fiscal</span><input type="text" name="tax_id" value="{{ old('tax_id', $isEdit ? $customer->tax_id : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm sm:col-span-2 lg:col-span-3"><span class="mb-1.5 block font-semibold">Notes</span><textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $customer->notes : '') }}</textarea></label>
        </div>
    </section>

    <section class="gp-card">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold">Contacts</h2>
            <button type="button" id="add-contact" class="gp-btn-secondary text-xs">Ajouter un contact</button>
        </div>
        <div id="contacts-list" class="space-y-3">
            @foreach($contacts as $i => $contact)
                <div class="contact-row grid gap-2 rounded-xl border border-gp-border p-3 sm:grid-cols-6 dark:border-white/10">
                    <input type="text" name="contacts[{{ $i }}][name]" value="{{ $contact['name'] ?? '' }}" placeholder="Nom" class="rounded-lg border border-gp-border px-2 py-2 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
                    <input type="text" name="contacts[{{ $i }}][role]" value="{{ $contact['role'] ?? '' }}" placeholder="Fonction" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <input type="email" name="contacts[{{ $i }}][email]" value="{{ $contact['email'] ?? '' }}" placeholder="Email" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <input type="text" name="contacts[{{ $i }}][phone]" value="{{ $contact['phone'] ?? '' }}" placeholder="Tél." class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <label class="inline-flex items-center gap-2 text-xs text-gp-muted"><input type="checkbox" name="contacts[{{ $i }}][is_primary]" value="1" @checked(!empty($contact['is_primary']))> Principal</label>
                </div>
            @endforeach
        </div>
    </section>

    @unless($isEdit)
        <section class="gp-card">
            <h2 class="mb-4 text-sm font-bold">Documents</h2>
            <input type="file" name="documents[]" multiple class="block w-full text-sm text-gp-muted">
        </section>
    @endunless

    <div class="flex flex-wrap gap-2">
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer le client' }}</button>
        <a href="{{ $isEdit ? route('customers.show', $customer) : route('customers.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>

<script>
document.getElementById('add-contact')?.addEventListener('click', () => {
    const list = document.getElementById('contacts-list');
    const i = list.querySelectorAll('.contact-row').length;
    const row = document.createElement('div');
    row.className = 'contact-row grid gap-2 rounded-xl border border-gp-border p-3 sm:grid-cols-6 dark:border-white/10';
    row.innerHTML = `
        <input type="text" name="contacts[${i}][name]" placeholder="Nom" class="rounded-lg border border-gp-border px-2 py-2 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
        <input type="text" name="contacts[${i}][role]" placeholder="Fonction" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
        <input type="email" name="contacts[${i}][email]" placeholder="Email" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
        <input type="text" name="contacts[${i}][phone]" placeholder="Tél." class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
        <label class="inline-flex items-center gap-2 text-xs text-gp-muted"><input type="checkbox" name="contacts[${i}][is_primary]" value="1"> Principal</label>`;
    list.appendChild(row);
});
</script>
