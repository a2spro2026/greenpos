@php $isEdit = isset($expense); @endphp
<form method="post" action="{{ $isEdit ? route('accounting.expenses.update', $expense) : route('accounting.expenses.store') }}" class="space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card">
        <h2 class="mb-4 text-sm font-bold">Détails</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-sm sm:col-span-2">
                <span class="mb-1.5 block font-semibold">Libellé *</span>
                <input type="text" name="label" value="{{ old('label', $isEdit ? $expense->label : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                @error('label')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Date *</span>
                <input type="date" name="expense_date" value="{{ old('expense_date', $isEdit ? $expense->expense_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Montant *</span>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $isEdit ? $expense->amount : '') }}" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Devise</span>
                <input type="text" name="currency" value="{{ old('currency', $isEdit ? $expense->currency : ($currency ?? 'MAD')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Type *</span>
                <select name="expense_type" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('expense_type', $isEdit ? $expense->expense_type : 'variable') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Catégorie</span>
                <select name="category" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $isEdit ? $expense->category : 'other') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Boutique</span>
                <select name="store_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $expense->store_id : '') === (string) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Fournisseur</span>
                <select name="supplier_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $isEdit ? $expense->supplier_id : '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Paiement</span>
                <input type="text" name="payment_method" value="{{ old('payment_method', $isEdit ? $expense->payment_method : '') }}" placeholder="Espèces, virement…" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="inline-flex items-center gap-2 text-sm font-semibold sm:col-span-2">
                <input type="checkbox" name="is_recurring" value="1" @checked(old('is_recurring', $isEdit ? $expense->is_recurring : false))>
                Récurrente
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Récurrence</span>
                <select name="recurrence" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($recurrences as $key => $label)
                        <option value="{{ $key }}" @selected(old('recurrence', $isEdit ? $expense->recurrence : '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1.5 block font-semibold">Prochaine échéance</span>
                <input type="date" name="next_due_date" value="{{ old('next_due_date', $isEdit ? $expense->next_due_date?->format('Y-m-d') : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
            </label>
            <label class="block text-sm sm:col-span-2 lg:col-span-3">
                <span class="mb-1.5 block font-semibold">Notes</span>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $expense->notes : '') }}</textarea>
            </label>
            @unless($isEdit)
                <label class="inline-flex items-center gap-2 text-sm text-gp-muted sm:col-span-2">
                    <input type="checkbox" name="skip_journal" value="1">
                    Ne pas poster au journal de caisse
                </label>
            @endunless
        </div>
    </section>

    <div class="flex gap-2">
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer' }}</button>
        <a href="{{ route('accounting.expenses.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>
