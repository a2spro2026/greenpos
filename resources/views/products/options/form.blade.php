@extends('layouts.app')
@php $editing = $option->exists; $variants = old('variants', $editing ? $option->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'extra_price' => $v->extra_price])->all() : [['name' => '', 'extra_price' => '']]); @endphp
@section('heading', $editing ? 'Modifier l\'option' : 'Nouvelle option')
@section('content')
<form method="POST" action="{{ $editing ? route('products.options.update', $option) : route('products.options.store') }}" class="gp-card max-w-2xl space-y-4">
    @csrf @if($editing) @method('PUT') @endif
    <div><label class="gp-label">Nom</label><input name="name" value="{{ old('name', $option->name) }}" class="gp-input w-full" required></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="gp-label">Sélection</label>
            <select name="selection_mode" class="gp-select w-full">
                @foreach(\App\Models\ProductOption::SELECTION_MODES as $k => $v)
                    <option value="{{ $k }}" @selected(old('selection_mode', $option->selection_mode) === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-4 pb-2">
            <label class="text-sm"><input type="checkbox" name="is_required" value="1" @checked(old('is_required', $option->is_required))> Obligatoire</label>
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $option->is_active ?? true))> Active</label>
        </div>
    </div>
    <div>
        <p class="mb-2 text-xs font-bold uppercase text-gp-muted">Choix et supplément</p>
        <div id="option-variants" class="space-y-2">
            @foreach($variants as $i => $variant)
                <div class="grid grid-cols-5 gap-2">
                    <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant['id'] ?? '' }}">
                    <input name="variants[{{ $i }}][name]" value="{{ $variant['name'] ?? '' }}" placeholder="Nom" class="gp-input col-span-3">
                    <input type="number" step="0.01" name="variants[{{ $i }}][extra_price]" value="{{ $variant['extra_price'] ?? '' }}" placeholder="Prix +" class="gp-input col-span-2">
                </div>
            @endforeach
        </div>
        <button type="button" class="mt-2 text-xs font-semibold text-gp-primary" onclick="addVariant()">Ajouter un choix</button>
    </div>
    <button class="gp-btn-primary">Enregistrer</button>
</form>
<script>
function addVariant() {
    const box = document.getElementById('option-variants');
    const i = box.children.length;
    const row = document.createElement('div');
    row.className = 'grid grid-cols-5 gap-2';
    row.innerHTML = `<input name="variants[${i}][name]" placeholder="Nom" class="gp-input col-span-3"><input type="number" step="0.01" name="variants[${i}][extra_price]" placeholder="Prix +" class="gp-input col-span-2">`;
    box.appendChild(row);
}
</script>
@endsection
