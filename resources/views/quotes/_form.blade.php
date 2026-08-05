@php
    $isEdit = isset($quote);
    $lines = old('lines', $isEdit ? $quote->lines->map(fn ($l) => [
        'product_id' => $l->product_id,
        'quantity' => $l->quantity,
        'unit_price' => $l->unit_price,
        'discount_percent' => $l->discount_percent,
        'tax_rate' => $l->tax_rate,
        'description' => $l->description,
    ])->all() : [['product_id' => '', 'quantity' => 1, 'unit_price' => '', 'discount_percent' => 0, 'tax_rate' => 20, 'description' => '']]);
@endphp

<form method="post" action="{{ $isEdit ? route('quotes.update', $quote) : route('quotes.store') }}" class="space-y-4" id="qt-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <label class="block text-sm sm:col-span-2">
            <span class="mb-1.5 block font-semibold">Client</span>
            <select name="customer_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Sélectionner…</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" data-terms="{{ $customer->payment_terms }}"
                        @selected((string) old('customer_id', $isEdit ? $quote->customer_id : ($prefillCustomerId ?? '')) === (string) $customer->id)>
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
                    <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $quote->store_id : $workspaceStore?->id) === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Commercial</span>
            <select name="salesperson_id" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">—</option>
                @foreach($salespeople as $sp)
                    <option value="{{ $sp->id }}" @selected((string) old('salesperson_id', $isEdit ? $quote->salesperson_id : auth()->id()) === (string) $sp->id)>{{ $sp->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Date</span>
            <input type="date" name="quoted_at" value="{{ old('quoted_at', $isEdit ? optional($quote->quoted_at)->format('Y-m-d') : now()->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Validité</span>
            <input type="date" name="valid_until" value="{{ old('valid_until', $isEdit ? optional($quote->valid_until)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Devise</span>
            <input type="text" name="currency" value="{{ old('currency', $isEdit ? $quote->currency : $currency) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm sm:col-span-2">
            <span class="mb-1.5 block font-semibold">Conditions</span>
            <input type="text" name="terms" id="qt-terms" value="{{ old('terms', $isEdit ? $quote->terms : '') }}" placeholder="Validité, délais, garanties…" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm sm:col-span-2 lg:col-span-3">
            <span class="mb-1.5 block font-semibold">Notes internes</span>
            <textarea name="notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $quote->notes : '') }}</textarea>
        </label>
        <label class="block text-sm sm:col-span-2 lg:col-span-3">
            <span class="mb-1.5 block font-semibold">Notes client (sur devis)</span>
            <textarea name="customer_notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('customer_notes', $isEdit ? $quote->customer_notes : '') }}</textarea>
        </label>
    </section>

    <section class="gp-card">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold">Produits</h2>
            <button type="button" id="add-qt-line" class="gp-btn-secondary text-xs">Ajouter une ligne</button>
        </div>
        @error('lines')<p class="mb-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="qt-lines">
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
                        <tr class="qt-line border-t border-gp-border dark:border-white/10">
                            <td class="px-2 py-2 min-w-[220px]">
                                <select name="lines[{{ $i }}][product_id]" class="qt-product w-full rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]" required>
                                    <option value="">Produit…</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->sale_price }}" data-tax="{{ $product->tax_rate }}"
                                            @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>
                                            {{ $product->name }} ({{ $product->sku }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-2"><input type="number" step="0.001" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" class="qt-qty w-24 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]" required></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][unit_price]" value="{{ $line['unit_price'] ?? '' }}" class="qt-price w-28 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][discount_percent]" value="{{ $line['discount_percent'] ?? 0 }}" class="qt-discount w-20 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][tax_rate]" value="{{ $line['tax_rate'] ?? 20 }}" class="qt-tax w-20 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2 text-right font-semibold qt-subtotal">—</td>
                            <td class="px-2 py-2"><button type="button" class="remove-qt-line text-xs text-rose-600">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 grid gap-2 border-t border-gp-border pt-4 text-sm dark:border-white/10 sm:ml-auto sm:max-w-xs">
            <div class="flex justify-between"><span class="text-gp-muted">Total HT</span><span id="qt-ht" class="font-bold">0,00</span></div>
            <div class="flex justify-between"><span class="text-gp-muted">Remises</span><span id="qt-disc" class="font-bold">0,00</span></div>
            <div class="flex justify-between"><span class="text-gp-muted">TVA</span><span id="qt-tax" class="font-bold">0,00</span></div>
            <div class="flex justify-between text-base"><span class="font-semibold">Total TTC</span><span id="qt-ttc" class="font-bold text-gp-primary">0,00</span></div>
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        <button type="submit" class="gp-btn-secondary">Enregistrer brouillon</button>
        @if(!$isEdit)
            <button type="submit" name="send" value="1" class="gp-btn-primary">Créer et envoyer</button>
        @endif
        <a href="{{ $isEdit ? route('quotes.show', $quote) : route('quotes.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>

<script>
(function () {
    const tbody = document.querySelector('#qt-lines tbody');
    const fmt = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
    function calc() {
        let ht = 0, tax = 0, disc = 0;
        tbody.querySelectorAll('.qt-line').forEach(row => {
            const qty = parseFloat(row.querySelector('.qt-qty').value) || 0;
            const price = parseFloat(row.querySelector('.qt-price').value) || 0;
            const d = parseFloat(row.querySelector('.qt-discount').value) || 0;
            const rate = parseFloat(row.querySelector('.qt-tax').value) || 0;
            const gross = qty * price;
            const dAmt = gross * (d / 100);
            const sub = gross - dAmt;
            const t = sub * (rate / 100);
            row.querySelector('.qt-subtotal').textContent = fmt(sub);
            ht += sub; tax += t; disc += dAmt;
        });
        document.getElementById('qt-ht').textContent = fmt(ht);
        document.getElementById('qt-disc').textContent = fmt(disc);
        document.getElementById('qt-tax').textContent = fmt(tax);
        document.getElementById('qt-ttc').textContent = fmt(ht + tax);
    }
    function bindRow(row) {
        row.querySelector('.qt-product')?.addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            if (!opt) return;
            if (opt.dataset.price) row.querySelector('.qt-price').value = opt.dataset.price;
            if (opt.dataset.tax) row.querySelector('.qt-tax').value = opt.dataset.tax;
            calc();
        });
        row.querySelectorAll('input').forEach(i => i.addEventListener('input', calc));
        row.querySelector('.remove-qt-line')?.addEventListener('click', () => {
            if (tbody.querySelectorAll('.qt-line').length > 1) { row.remove(); calc(); }
        });
    }
    document.querySelector('[name="customer_id"]')?.addEventListener('change', (e) => {
        const opt = e.target.selectedOptions[0];
        if (opt?.dataset.terms) document.getElementById('qt-terms').value = opt.dataset.terms;
    });
    tbody.querySelectorAll('.qt-line').forEach(bindRow);
    document.getElementById('add-qt-line')?.addEventListener('click', () => {
        const index = tbody.querySelectorAll('.qt-line').length;
        const clone = tbody.querySelector('.qt-line').cloneNode(true);
        clone.querySelectorAll('select, input').forEach(el => {
            const name = el.getAttribute('name') || '';
            el.setAttribute('name', name.replace(/lines\[\d+]/, `lines[${index}]`));
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else if (el.classList.contains('qt-qty')) el.value = 1;
            else if (el.classList.contains('qt-discount')) el.value = 0;
            else if (el.classList.contains('qt-tax')) el.value = 20;
            else el.value = '';
        });
        clone.querySelector('.qt-subtotal').textContent = '—';
        tbody.appendChild(clone);
        bindRow(clone);
        calc();
    });
    calc();
})();
</script>
