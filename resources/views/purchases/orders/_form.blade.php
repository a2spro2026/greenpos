@php
    $isEdit = isset($order);
    $lines = old('lines', $isEdit ? $order->lines->map(fn ($l) => [
        'product_id' => $l->product_id,
        'quantity' => $l->quantity,
        'unit_price' => $l->unit_price,
        'discount_percent' => $l->discount_percent,
        'tax_rate' => $l->tax_rate,
    ])->all() : [['product_id' => '', 'quantity' => 1, 'unit_price' => '', 'discount_percent' => 0, 'tax_rate' => 20]]);
@endphp

<form method="post" action="{{ $isEdit ? route('purchases.orders.update', $order) : route('purchases.orders.store') }}" class="space-y-4" id="po-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <section class="gp-card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <label class="block text-sm sm:col-span-2 lg:col-span-1">
            <span class="mb-1.5 block font-semibold">Fournisseur</span>
            <select name="supplier_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                <option value="">Sélectionner…</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $isEdit ? $order->supplier_id : '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Boutique</span>
            <select name="store_id" required class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) old('store_id', $isEdit ? $order->store_id : $workspaceStore?->id) === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Date</span>
            <input type="date" name="ordered_at" value="{{ old('ordered_at', $isEdit ? optional($order->ordered_at)->format('Y-m-d') : now()->format('Y-m-d')) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Référence</span>
            <input type="text" name="reference" value="{{ old('reference', $isEdit ? $order->reference : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Devise</span>
            <input type="text" name="currency" value="{{ old('currency', $isEdit ? $order->currency : $currency) }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm">
            <span class="mb-1.5 block font-semibold">Livraison prévue</span>
            <input type="date" name="expected_at" value="{{ old('expected_at', $isEdit ? optional($order->expected_at)->format('Y-m-d') : '') }}" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">
        </label>
        <label class="block text-sm sm:col-span-2 lg:col-span-3">
            <span class="mb-1.5 block font-semibold">Notes</span>
            <textarea name="notes" rows="2" class="w-full rounded-xl border border-gp-border px-3 py-2.5 dark:border-white/10 dark:bg-[#0f1614]">{{ old('notes', $isEdit ? $order->notes : '') }}</textarea>
        </label>
    </section>

    <section class="gp-card">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold">Articles</h2>
            <button type="button" id="add-po-line" class="gp-btn-secondary text-xs">Ajouter une ligne</button>
        </div>
        @error('lines')<p class="mb-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="po-lines">
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
                        <tr class="po-line border-t border-gp-border dark:border-white/10">
                            <td class="px-2 py-2 min-w-[220px]">
                                <select name="lines[{{ $i }}][product_id]" class="po-product w-full rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]" required>
                                    <option value="">Produit…</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                            data-price="{{ $product->purchase_price }}"
                                            data-tax="{{ $product->tax_rate }}"
                                            @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>
                                            {{ $product->name }} ({{ $product->sku }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-2"><input type="number" step="0.001" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" class="po-qty w-24 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]" required></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][unit_price]" value="{{ $line['unit_price'] ?? '' }}" class="po-price w-28 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][discount_percent]" value="{{ $line['discount_percent'] ?? 0 }}" class="po-discount w-20 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" name="lines[{{ $i }}][tax_rate]" value="{{ $line['tax_rate'] ?? 20 }}" class="po-tax w-20 rounded-lg border border-gp-border px-2 py-2 dark:border-white/10 dark:bg-[#0f1614]"></td>
                            <td class="px-2 py-2 text-right font-semibold po-subtotal">—</td>
                            <td class="px-2 py-2"><button type="button" class="remove-po-line text-xs text-rose-600">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 grid gap-2 border-t border-gp-border pt-4 text-sm dark:border-white/10 sm:ml-auto sm:max-w-xs">
            <div class="flex justify-between"><span class="text-gp-muted">Total HT</span><span id="po-ht" class="font-bold">0,00</span></div>
            <div class="flex justify-between"><span class="text-gp-muted">TVA</span><span id="po-tax" class="font-bold">0,00</span></div>
            <div class="flex justify-between text-base"><span class="font-semibold">Total TTC</span><span id="po-ttc" class="font-bold text-gp-primary">0,00</span></div>
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        <button class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Créer le bon de commande' }}</button>
        <a href="{{ $isEdit ? route('purchases.orders.show', $order) : route('purchases.orders.index') }}" class="gp-btn-secondary">Annuler</a>
    </div>
</form>

<script>
(function () {
    const tbody = document.querySelector('#po-lines tbody');
    const fmt = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);

    function calc() {
        let ht = 0, tax = 0;
        tbody.querySelectorAll('.po-line').forEach(row => {
            const qty = parseFloat(row.querySelector('.po-qty').value) || 0;
            const price = parseFloat(row.querySelector('.po-price').value) || 0;
            const disc = parseFloat(row.querySelector('.po-discount').value) || 0;
            const rate = parseFloat(row.querySelector('.po-tax').value) || 0;
            const gross = qty * price;
            const sub = gross - gross * (disc / 100);
            const t = sub * (rate / 100);
            row.querySelector('.po-subtotal').textContent = fmt(sub);
            ht += sub; tax += t;
        });
        document.getElementById('po-ht').textContent = fmt(ht);
        document.getElementById('po-tax').textContent = fmt(tax);
        document.getElementById('po-ttc').textContent = fmt(ht + tax);
    }

    function bindRow(row) {
        row.querySelector('.po-product')?.addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            if (!opt) return;
            if (opt.dataset.price) row.querySelector('.po-price').value = opt.dataset.price;
            if (opt.dataset.tax) row.querySelector('.po-tax').value = opt.dataset.tax;
            calc();
        });
        row.querySelectorAll('input').forEach(i => i.addEventListener('input', calc));
        row.querySelector('.remove-po-line')?.addEventListener('click', () => {
            if (tbody.querySelectorAll('.po-line').length > 1) { row.remove(); calc(); }
        });
    }

    tbody.querySelectorAll('.po-line').forEach(bindRow);
    document.getElementById('add-po-line')?.addEventListener('click', () => {
        const index = tbody.querySelectorAll('.po-line').length;
        const first = tbody.querySelector('.po-line');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('select, input').forEach(el => {
            const name = el.getAttribute('name') || '';
            el.setAttribute('name', name.replace(/lines\[\d+]/, `lines[${index}]`));
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else if (el.classList.contains('po-qty')) el.value = 1;
            else if (el.classList.contains('po-discount')) el.value = 0;
            else if (el.classList.contains('po-tax')) el.value = 20;
            else el.value = '';
        });
        clone.querySelector('.po-subtotal').textContent = '—';
        tbody.appendChild(clone);
        bindRow(clone);
        calc();
    });
    calc();
})();
</script>
