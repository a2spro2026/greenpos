@php
    $isEdit = isset($invoice);
    $lines = old('lines', $isEdit ? $invoice->lines->map(fn ($l) => [
        'product_id' => $l->product_id,
        'quantity' => $l->quantity,
        'unit_price' => $l->unit_price,
        'discount_percent' => $l->discount_percent,
        'tax_rate' => $l->tax_rate,
        'description' => $l->description,
    ])->all() : [['product_id' => '', 'quantity' => 1, 'unit_price' => '', 'discount_percent' => 0, 'tax_rate' => 20, 'description' => '']]);
@endphp

<form method="post" action="{{ $isEdit ? route('invoices.update', $invoice) : route('invoices.store') }}" class="space-y-4" id="inv-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <label class="block text-sm sm:col-span-2">
            <span class="mb-1.5 block font-semibold">Client</span>
            <select name="customer_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Sélectionner…</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                        data-terms="{{ $customer->payment_terms }}"
                        @selected((string) old('customer_id', $isEdit ? $invoice->customer_id : ($prefillCustomerId ?? '')) === (string) $customer->id)>
                        {{ $customer->displayName() }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Boutique</span>
            <select name="store_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $invoice->store_id : $workspaceStore?->id) === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">N° (auto si brouillon)</span>
            <input type="text" value="{{ $isEdit ? $invoice->number : 'Automatique' }}" disabled class="w-full rounded-xl border border-gp-border bg-slate-50 px-3 py-2.5 text-gp-muted dark:border-white/10 dark:bg-white/5">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Date facture</span>
            <input type="date" name="invoiced_at" value="{{ old('invoiced_at', $isEdit ? optional($invoice->invoiced_at)->format('Y-m-d') : now()->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Échéance</span>
            <input type="date" name="due_at" id="inv-due" value="{{ old('due_at', $isEdit ? optional($invoice->due_at)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Devise</span>
            <input type="text" name="currency" value="{{ old('currency', $isEdit ? $invoice->currency : $currency) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Référence</span>
            <input type="text" name="reference" value="{{ old('reference', $isEdit ? $invoice->reference : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm sm:col-span-2">
            <span class="mb-1.5 block font-semibold">Conditions de paiement</span>
            <input type="text" name="payment_terms" id="inv-terms" value="{{ old('payment_terms', $isEdit ? $invoice->payment_terms : '') }}" placeholder="30 jours net, comptant…" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm sm:col-span-2 lg:col-span-3">
            <span class="mb-1.5 block font-semibold">Notes internes</span>
            <textarea name="notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $invoice->notes : '') }}</textarea>
        </label>
        <label class="block text-sm sm:col-span-2 lg:col-span-3">
            <span class="mb-1.5 block font-semibold">Notes client (sur facture)</span>
            <textarea name="customer_notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('customer_notes', $isEdit ? $invoice->customer_notes : '') }}</textarea>
        </label>
    </section>

    <section class="gp-card">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold">Produits & services</h2>
            <button type="button" id="add-inv-line" class="gp-btn-secondary text-xs">Ajouter une ligne</button>
        </div>
        @error('lines')<p class="mb-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="inv-lines">
                <thead class="text-xs uppercase text-gp-muted">
                    <tr>
                        <th class="px-2 py-2 text-left">Produit</th>
                        <th class="px-2 py-2 text-left">Qté</th>
                        <th class="px-2 py-2 text-left">Prix</th>
                        <th class="px-2 py-2 text-left">Remise %</th>
                        <th class="px-2 py-2 text-left">TVA %</th>
                        <th class="px-2 py-2 text-right">Sous-total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $i => $line)
                        <tr class="inv-line border-t border-gp-border dark:border-white/10">
                            <td class="px-2 py-2 min-w-[220px]">
                                <select name="lines[{{ $i }}][product_id]" class="inv-product w-full rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]" required>
                                    <option value="">Produit…</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                            data-price="{{ $product->sale_price }}"
                                            data-tax="{{ $product->tax_rate }}"
                                            @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>
                                            {{ $product->name }} ({{ $product->sku }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-2"><input type="number" step="0.001" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" class="inv-qty w-24 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]" required></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][unit_price]" value="{{ $line['unit_price'] ?? '' }}" class="inv-price w-28 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][discount_percent]" value="{{ $line['discount_percent'] ?? 0 }}" class="inv-discount w-20 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][tax_rate]" value="{{ $line['tax_rate'] ?? 20 }}" class="inv-tax w-20 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2 text-right font-semibold inv-subtotal">—</td>
                            <td class="px-2 py-2"><button type="button" class="remove-inv-line text-xs text-rose-600">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 grid gap-2 border-t border-gp-border pt-4 text-sm dark:border-white/10 sm:ml-auto sm:max-w-xs">
            <div class="flex justify-between"><span class="text-gp-muted">Total HT</span><span id="inv-ht" class="font-bold">0,00</span></div>
            <div class="flex justify-between"><span class="text-gp-muted">Remises</span><span id="inv-disc" class="font-bold">0,00</span></div>
            <div class="flex justify-between"><span class="text-gp-muted">TVA</span><span id="inv-tax" class="font-bold">0,00</span></div>
            <div class="flex justify-between text-base"><span class="font-semibold">Total TTC</span><span id="inv-ttc" class="font-bold text-gp-primary">0,00</span></div>
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        <button type="submit" name="save" value="1" class="gp-btn-secondary">{{ $isEdit ? 'Enregistrer brouillon' : 'Enregistrer brouillon' }}</button>
        <button type="submit" name="issue" value="1" class="gp-btn-primary">{{ $isEdit ? 'Émettre la facture' : 'Créer et émettre' }}</button>
        <a href="{{ $isEdit ? route('invoices.show', $invoice) : route('invoices.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>

<script>
(function () {
    const tbody = document.querySelector('#inv-lines tbody');
    const fmt = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);

    function calc() {
        let ht = 0, tax = 0, disc = 0;
        tbody.querySelectorAll('.inv-line').forEach(row => {
            const qty = parseFloat(row.querySelector('.inv-qty').value) || 0;
            const price = parseFloat(row.querySelector('.inv-price').value) || 0;
            const d = parseFloat(row.querySelector('.inv-discount').value) || 0;
            const rate = parseFloat(row.querySelector('.inv-tax').value) || 0;
            const gross = qty * price;
            const dAmt = gross * (d / 100);
            const sub = gross - dAmt;
            const t = sub * (rate / 100);
            row.querySelector('.inv-subtotal').textContent = fmt(sub);
            ht += sub; tax += t; disc += dAmt;
        });
        document.getElementById('inv-ht').textContent = fmt(ht);
        document.getElementById('inv-disc').textContent = fmt(disc);
        document.getElementById('inv-tax').textContent = fmt(tax);
        document.getElementById('inv-ttc').textContent = fmt(ht + tax);
    }

    function bindRow(row) {
        row.querySelector('.inv-product')?.addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            if (!opt) return;
            if (opt.dataset.price) row.querySelector('.inv-price').value = opt.dataset.price;
            if (opt.dataset.tax) row.querySelector('.inv-tax').value = opt.dataset.tax;
            calc();
        });
        row.querySelectorAll('input').forEach(i => i.addEventListener('input', calc));
        row.querySelector('.remove-inv-line')?.addEventListener('click', () => {
            if (tbody.querySelectorAll('.inv-line').length > 1) { row.remove(); calc(); }
        });
    }

    document.querySelector('[name="customer_id"]')?.addEventListener('change', (e) => {
        const opt = e.target.selectedOptions[0];
        if (opt?.dataset.terms) document.getElementById('inv-terms').value = opt.dataset.terms;
    });

    tbody.querySelectorAll('.inv-line').forEach(bindRow);
    document.getElementById('add-inv-line')?.addEventListener('click', () => {
        const index = tbody.querySelectorAll('.inv-line').length;
        const first = tbody.querySelector('.inv-line');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('select, input').forEach(el => {
            const name = el.getAttribute('name') || '';
            el.setAttribute('name', name.replace(/lines\[\d+]/, `lines[${index}]`));
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else if (el.classList.contains('inv-qty')) el.value = 1;
            else if (el.classList.contains('inv-discount')) el.value = 0;
            else if (el.classList.contains('inv-tax')) el.value = 20;
            else el.value = '';
        });
        clone.querySelector('.inv-subtotal').textContent = '—';
        tbody.appendChild(clone);
        bindRow(clone);
        calc();
    });
    calc();
})();
</script>
