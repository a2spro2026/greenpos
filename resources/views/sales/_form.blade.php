@php $isEdit = isset($sale); @endphp

<form method="POST" action="{{ $isEdit ? route('sales.update', $sale) : route('sales.store') }}" id="sale-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <label class="gp-label">Client</label>
            <select name="customer_id" class="gp-select w-full">
                <option value="">Client de passage</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ old('customer_id', $isEdit ? $sale->customer_id : '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Boutique *</label>
            <select name="store_id" class="gp-select w-full" required>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ old('store_id', $isEdit ? $sale->store_id : $stores->first()?->id) == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Commercial</label>
            <select name="salesperson_id" class="gp-select w-full">
                <option value="">—</option>
                @foreach($salespeople as $sp)
                    <option value="{{ $sp->id }}" {{ old('salesperson_id', $isEdit ? $sale->salesperson_id : '') == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Date de vente *</label>
            <input type="date" name="sold_at" value="{{ old('sold_at', $isEdit ? $sale->sold_at?->format('Y-m-d') : now()->format('Y-m-d')) }}" class="gp-input w-full" required>
        </div>
        <div>
            <label class="gp-label">Référence</label>
            <input type="text" name="reference" value="{{ old('reference', $isEdit ? $sale->reference : '') }}" class="gp-input w-full" placeholder="Optionnel">
        </div>
        <div>
            <label class="gp-label">Devise</label>
            <input type="text" name="currency" value="{{ old('currency', $isEdit ? $sale->currency : $currency) }}" class="gp-input w-full">
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Notes</label>
            <textarea name="notes" class="gp-input w-full" rows="2">{{ old('notes', $isEdit ? $sale->notes : '') }}</textarea>
        </div>
    </div>

    {{-- Product lines --}}
    <section class="gp-card mb-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-bold">Produits</h2>
            <button type="button" id="add-line" class="gp-btn-secondary text-xs">+ Ajouter ligne</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="lines-table">
                <thead class="border-b border-gp-border text-xs uppercase text-gp-muted dark:border-white/10">
                    <tr>
                        <th class="px-2 py-2 text-left w-72">Produit</th>
                        <th class="px-2 py-2 text-right w-20">Qté</th>
                        <th class="px-2 py-2 text-right w-28">Prix unitaire</th>
                        <th class="px-2 py-2 text-right w-20">TVA %</th>
                        <th class="px-2 py-2 text-right w-20">Remise %</th>
                        <th class="px-2 py-2 text-right w-28">Total TTC</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody id="lines-body"></tbody>
                <tfoot class="border-t border-gp-border dark:border-white/10">
                    <tr>
                        <td colspan="5" class="px-2 py-2 text-right font-semibold text-gp-muted">Total HT</td>
                        <td class="px-2 py-2 text-right font-bold" id="total-ht">0,00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="px-2 py-2 text-right font-semibold text-gp-muted">TVA</td>
                        <td class="px-2 py-2 text-right font-bold" id="total-tax">0,00</td>
                        <td></td>
                    </tr>
                    <tr class="text-lg">
                        <td colspan="5" class="px-2 py-2 text-right font-bold">Total TTC</td>
                        <td class="px-2 py-2 text-right font-bold text-gp-primary" id="total-ttc">0,00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div class="flex flex-wrap justify-end gap-3">
        <a href="{{ $isEdit ? route('sales.show', $sale) : route('sales.index') }}" class="gp-btn-secondary">Annuler</a>
        <button type="submit" class="gp-btn-primary">{{ $isEdit ? 'Enregistrer' : 'Brouillon' }}</button>
        @unless($isEdit)
            <button type="submit" name="confirm" value="1" class="gp-btn-primary !bg-emerald-600 !hover:bg-emerald-700">Confirmer et décrémenter stock</button>
        @endunless
    </div>
</form>

<script>
    @php
        $existingLinesData = ($isEdit ? $sale->lines : collect())->map(function($l) {
            return ['product_id' => $l->product_id, 'quantity' => (float)$l->quantity, 'unit_price' => (float)$l->unit_price, 'tax_rate' => (float)$l->tax_rate, 'discount_percent' => (float)$l->discount_percent];
        });
    @endphp
    const products = @json($products);
    const existingLines = @json($existingLinesData);

    let lineIndex = 0;
    const body = document.getElementById('lines-body');

    function addLine(data = {}) {
        const i = lineIndex++;
        const pid = data.product_id || '';
        const qty = data.quantity || 1;
        const price = data.unit_price || '';
        const tax = data.tax_rate ?? 20;
        const disc = data.discount_percent || 0;

        const optionsHtml = '<option value="">— Produit —</option>' + products.map(p =>
            `<option value="${p.id}" data-price="${p.sale_price}" data-tax="${p.tax_rate ?? 20}" ${p.id == pid ? 'selected' : ''}>${p.name} (${p.sku || '—'})</option>`
        ).join('');

        const tr = document.createElement('tr');
        tr.classList.add('line-row');
        tr.innerHTML = `
            <td class="px-2 py-2"><select name="lines[${i}][product_id]" class="gp-select w-full prod-select" required>${optionsHtml}</select></td>
            <td class="px-2 py-2"><input type="number" name="lines[${i}][quantity]" value="${qty}" min="0.001" step="any" class="gp-input w-full text-right qty" required></td>
            <td class="px-2 py-2"><input type="number" name="lines[${i}][unit_price]" value="${price}" min="0" step="any" class="gp-input w-full text-right price" required></td>
            <td class="px-2 py-2"><input type="number" name="lines[${i}][tax_rate]" value="${tax}" min="0" max="100" step="any" class="gp-input w-full text-right tax"></td>
            <td class="px-2 py-2"><input type="number" name="lines[${i}][discount_percent]" value="${disc}" min="0" max="100" step="any" class="gp-input w-full text-right disc"></td>
            <td class="px-2 py-2 text-right font-bold line-total">0,00</td>
            <td class="px-2 py-2"><button type="button" class="text-rose-500 hover:text-rose-700 remove-line">✕</button></td>
        `;
        body.appendChild(tr);

        const sel = tr.querySelector('.prod-select');
        sel.addEventListener('change', () => {
            const opt = sel.options[sel.selectedIndex];
            if (opt.dataset.price) tr.querySelector('.price').value = opt.dataset.price;
            if (opt.dataset.tax) tr.querySelector('.tax').value = opt.dataset.tax;
            recalc();
        });
        tr.querySelectorAll('input').forEach(inp => inp.addEventListener('input', recalc));
        tr.querySelector('.remove-line').addEventListener('click', () => { tr.remove(); recalc(); });

        if (!price && pid) {
            const p = products.find(x => x.id == pid);
            if (p) { tr.querySelector('.price').value = p.sale_price; tr.querySelector('.tax').value = p.tax_rate ?? 20; }
        }
        recalc();
    }

    function recalc() {
        let ht = 0, tax = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const q = parseFloat(row.querySelector('.qty').value) || 0;
            const p = parseFloat(row.querySelector('.price').value) || 0;
            const t = parseFloat(row.querySelector('.tax').value) || 0;
            const d = parseFloat(row.querySelector('.disc').value) || 0;
            const gross = q * p;
            const disc = gross * (d / 100);
            const net = gross - disc;
            const taxAmt = net * (t / 100);
            row.querySelector('.line-total').textContent = fmt(net + taxAmt);
            ht += net;
            tax += taxAmt;
        });
        document.getElementById('total-ht').textContent = fmt(ht);
        document.getElementById('total-tax').textContent = fmt(tax);
        document.getElementById('total-ttc').textContent = fmt(ht + tax);
    }

    function fmt(n) { return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ').replace('.', ','); }

    document.getElementById('add-line').addEventListener('click', () => addLine());
    if (existingLines.length) { existingLines.forEach(l => addLine(l)); } else { addLine(); }
</script>
