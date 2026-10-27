@php $isEdit = isset($agent); @endphp
<form method="post" action="{{ $isEdit ? route('delivery.update', $agent) : route('delivery.store') }}" class="space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm sm:col-span-2">
                <span class="mb-1.5 block font-semibold">Nom *</span>
                <input type="text" name="name" value="{{ old('name', $isEdit ? $agent->name : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Type *</span>
                <select name="type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $isEdit ? $agent->type : 'internal') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Téléphone</span>
                <input type="text" name="phone" value="{{ old('phone', $isEdit ? $agent->phone : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Email</span>
                <input type="email" name="email" value="{{ old('email', $isEdit ? $agent->email : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Boutique</span>
                <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $agent->store_id : '') === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Plateforme</span>
                <input type="text" name="platform_name" value="{{ old('platform_name', $isEdit ? $agent->platform_name : '') }}" placeholder="Glovo, Uber Eats…" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Commission %</span>
                <input type="number" step="0.01" min="0" max="100" name="commission_percent" value="{{ old('commission_percent', $isEdit ? $agent->commission_percent : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Commission fixe</span>
                <input type="number" step="0.01" min="0" name="commission_fixed" value="{{ old('commission_fixed', $isEdit ? $agent->commission_fixed : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="inline-flex items-center gap-2 text-sm font-semibold">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $agent->is_active : true))>
                Actif
            </label>
            <label class="block text-sm sm:col-span-2 lg:col-span-3">
                <span class="mb-1.5 block font-semibold">Notes</span>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $agent->notes : '') }}</textarea>
            </label>
        </div>
    </section>

    <div class="flex gap-2">
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer' }}</button>
        <a href="{{ route('delivery.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>
