@php
    $isEdit = isset($product);
    $variants = old('variants', $isEdit ? $product->variants->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->name,
        'sku' => $v->sku,
        'barcode' => $v->barcode,
        'sale_price' => $v->sale_price,
        'size' => $v->attributes['size'] ?? '',
        'color' => $v->attributes['color'] ?? '',
        'status' => $v->status,
    ])->toArray() : [['name' => '', 'sku' => '', 'barcode' => '', 'sale_price' => '', 'size' => '', 'color' => '', 'status' => 'active']]);
    $selectedStores = old('store_ids', $isEdit ? $product->stores->pluck('id')->all() : [$workspaceStore?->id]);
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="gp-card space-y-4">
            <h2 class="text-sm font-bold text-gp-text dark:text-white">Identité</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Nom * <x-gp-tip text="Nom commercial visible en caisse, catalogues et factures." /></label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required class="gp-input">
                    @error('name')<p class="gp-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Type *</label>
                    <select name="type" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $product->type ?? 'physical') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Statut *</label>
                    <select name="status" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $product->status ?? 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Description courte</label>
                    <input type="text" name="short_description" value="{{ old('short_description', $product->short_description ?? '') }}" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Description</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
            </div>
        </section>

        <section class="gp-card space-y-4">
            <h2 class="text-sm font-bold text-gp-text dark:text-white">Références</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="Auto si vide" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    @error('sku')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Code-barres</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    @error('barcode')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">QR Code</label>
                    <input type="text" name="qr_code" value="{{ old('qr_code', $product->qr_code ?? '') }}" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Unité *</label>
                    <select name="unit" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        @foreach($units as $key => $label)
                            <option value="{{ $key }}" @selected(old('unit', $product->unit ?? 'pce') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="gp-card space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-gp-text dark:text-white">Variantes</h2>
                <button type="button" id="add-variant" class="gp-btn-secondary text-xs">Ajouter une variante</button>
            </div>
            <div id="variants-list" class="space-y-3">
                @foreach($variants as $index => $variant)
                    <div class="variant-row grid gap-2 rounded-xl border border-gp-border p-3 sm:grid-cols-6 dark:border-white/10">
                        <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] ?? '' }}">
                        <input type="text" name="variants[{{ $index }}][name]" value="{{ $variant['name'] ?? '' }}" placeholder="Nom" class="rounded-lg border border-gp-border px-2 py-2 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
                        <input type="text" name="variants[{{ $index }}][sku]" value="{{ $variant['sku'] ?? '' }}" placeholder="SKU" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <input type="text" name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" placeholder="Taille" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <input type="text" name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" placeholder="Couleur" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <input type="number" step="0.01" name="variants[{{ $index }}][sale_price]" value="{{ $variant['sale_price'] ?? '' }}" placeholder="Prix" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="gp-card space-y-4">
            <h2 class="text-sm font-bold text-gp-text dark:text-white">Classification</h2>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Catégorie</label>
                <select name="category_id" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Marque</label>
                <select name="brand_id" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id ?? '') === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Fournisseur</label>
                <select name="supplier_id" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                    <option value="">—</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <section class="gp-card space-y-4">
            <h2 class="text-sm font-bold text-gp-text dark:text-white">Prix & taxes</h2>
            @if($canViewPurchase)
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Prix d’achat</label>
                    <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                </div>
            @else
                <input type="hidden" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}">
            @endif
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Prix de vente *</label>
                <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? 0) }}" required class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Taxe % *</label>
                <input type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate ?? 20) }}" required class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Remise</label>
                    <select name="discount_type" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                        <option value="">Aucune</option>
                        <option value="percent" @selected(old('discount_type', $product->discount_type ?? '') === 'percent')>%</option>
                        <option value="amount" @selected(old('discount_type', $product->discount_type ?? '') === 'amount')>Montant</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gp-muted">Valeur</label>
                    <input type="number" step="0.01" name="discount_value" value="{{ old('discount_value', $product->discount_value ?? '') }}" class="w-full rounded-xl border border-gp-border bg-white px-3 py-2.5 text-sm dark:border-white/10 dark:bg-[#0f1614]">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gp-text dark:text-white">
                <input type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $product->track_stock ?? true))>
                Suivi de stock
            </label>
        </section>

        <section class="gp-card space-y-4">
            <h2 class="text-sm font-bold text-gp-text dark:text-white">Image</h2>
            @if($isEdit && $product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="" class="h-28 w-28 rounded-xl object-cover">
            @endif
            <input type="file" name="image" accept="image/*" class="block w-full text-sm text-gp-muted">
            @error('image')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        </section>

        <section class="gp-card space-y-3">
            <h2 class="text-sm font-bold text-gp-text dark:text-white">Boutiques</h2>
            @foreach($stores as $store)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="store_ids[]" value="{{ $store->id }}" @checked(in_array($store->id, $selectedStores ?? [], false) || in_array((string) $store->id, $selectedStores ?? [], true))>
                    {{ $store->name }}
                </label>
            @endforeach
        </section>
    </div>
</div>

<script>
document.getElementById('add-variant')?.addEventListener('click', () => {
    const list = document.getElementById('variants-list');
    const index = list.querySelectorAll('.variant-row').length;
    const row = document.createElement('div');
    row.className = 'variant-row grid gap-2 rounded-xl border border-gp-border p-3 sm:grid-cols-6 dark:border-white/10';
    row.innerHTML = `
        <input type="hidden" name="variants[${index}][id]" value="">
        <input type="text" name="variants[${index}][name]" placeholder="Nom" class="rounded-lg border border-gp-border px-2 py-2 text-sm sm:col-span-2 dark:border-white/10 dark:bg-[#0f1614]">
        <input type="text" name="variants[${index}][sku]" placeholder="SKU" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
        <input type="text" name="variants[${index}][size]" placeholder="Taille" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
        <input type="text" name="variants[${index}][color]" placeholder="Couleur" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
        <input type="number" step="0.01" name="variants[${index}][sale_price]" placeholder="Prix" class="rounded-lg border border-gp-border px-2 py-2 text-sm dark:border-white/10 dark:bg-[#0f1614]">
    `;
    list.appendChild(row);
});
</script>
