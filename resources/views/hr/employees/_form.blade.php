@php
    $isEdit = isset($employee);
@endphp
<form method="post" action="{{ $isEdit ? route('hr.employees.update', $employee) : route('hr.employees.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Identité</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Code</span><input type="text" name="code" value="{{ old('code', $isEdit ? $employee->code : ($suggestedCode ?? '')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">@error('code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Prénom *</span><input type="text" name="first_name" value="{{ old('first_name', $isEdit ? $employee->first_name : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">@error('first_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Nom *</span><input type="text" name="last_name" value="{{ old('last_name', $isEdit ? $employee->last_name : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">@error('last_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Email</span><input type="email" name="email" value="{{ old('email', $isEdit ? $employee->email : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Téléphone</span><input type="text" name="phone" value="{{ old('phone', $isEdit ? $employee->phone : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Date d’embauche</span><input type="date" name="hire_date" value="{{ old('hire_date', $isEdit && $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Rôle</span>
                <select name="job_role" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($jobRoles as $key => $label)
                        <option value="{{ $key }}" @selected(old('job_role', $isEdit ? $employee->job_role : 'cashier') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Statut</span>
                <select name="status" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $isEdit ? $employee->status : 'active') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Boutique</span>
                <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $employee->store_id : '') === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Compte utilisateur lié</span>
                <select name="user_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('user_id', $isEdit ? $employee->user_id : '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Photo</span><input type="file" name="photo" accept="image/*" class="w-full rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]"></label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Rémunération</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Type de paie</span>
                <select name="pay_type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($payTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('pay_type', $isEdit ? $employee->pay_type : 'monthly') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Taux de base</span><input type="number" step="0.01" name="base_rate" value="{{ old('base_rate', $isEdit ? $employee->base_rate : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Multiplicateur heures supp.</span><input type="number" step="0.01" name="overtime_multiplier" value="{{ old('overtime_multiplier', $isEdit ? $employee->overtime_multiplier : 1.5) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Heures normales / jour</span><input type="number" step="0.01" name="normal_hours_per_day" value="{{ old('normal_hours_per_day', $isEdit ? $employee->normal_hours_per_day : 8) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Jour de repos</span><input type="text" name="rest_day" value="{{ old('rest_day', $isEdit ? $employee->rest_day : '') }}" placeholder="dimanche" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Pénalité d’absence</span><input type="number" step="0.01" name="absence_penalty" value="{{ old('absence_penalty', $isEdit ? $employee->absence_penalty : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Commission %</span><input type="number" step="0.01" name="commission_percent" value="{{ old('commission_percent', $isEdit ? $employee->commission_percent : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Commission fixe</span><input type="number" step="0.01" name="commission_fixed" value="{{ old('commission_fixed', $isEdit ? $employee->commission_fixed : 0) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
        </div>
    </section>

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Coordonnées bancaires</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">IBAN</span><input type="text" name="bank_iban" value="{{ old('bank_iban', $isEdit ? $employee->bank_iban : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm"><span class="mb-1.5 block font-semibold">Banque</span><input type="text" name="bank_name" value="{{ old('bank_name', $isEdit ? $employee->bank_name : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]"></label>
            <label class="block text-sm sm:col-span-2"><span class="mb-1.5 block font-semibold">Notes</span><textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $employee->notes : '') }}</textarea></label>
            @unless($isEdit)
                <label class="block text-sm sm:col-span-2"><span class="mb-1.5 block font-semibold">Documents</span><input type="file" name="documents[]" multiple class="w-full rounded-xl border border-gp-border px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]"></label>
            @endunless
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer l’employé' }}</button>
        <a href="{{ $isEdit ? route('hr.employees.show', $employee) : route('hr.employees.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>
