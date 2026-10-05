@extends('layouts.app')
@php $editing = $list->exists; @endphp
@section('title', $editing ? 'Modifier la liste' : 'Nouvelle liste')
@section('heading', $editing ? 'Modifier '.$list->name : 'Nouvelle entrée')
@section('content')
<div class="flex flex-col gap-6 lg:flex-row">
    @include('settings._nav', ['section' => 'lists'])
    <form method="POST" action="{{ $editing ? route('settings.lists.update', $list) : route('settings.lists.store') }}" class="gp-card max-w-2xl flex-1 space-y-4">
        @csrf
        @if($editing) @method('PUT') @endif
        <div>
            <label class="gp-label">Type</label>
            <select name="type" class="gp-select w-full">
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(old('type', $list->type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="gp-label">Nom</label><input name="name" value="{{ old('name', $list->name) }}" class="gp-input w-full" required></div>
            <div><label class="gp-label">Code</label><input name="code" value="{{ old('code', $list->code) }}" class="gp-input w-full" placeholder="auto si vide"></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="gp-label">Ordre</label><input type="number" name="sort_order" value="{{ old('sort_order', $list->sort_order ?? 0) }}" class="gp-input w-full"></div>
            <div class="flex items-end gap-4 pb-2">
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $list->is_active ?? true))> Active</label>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $list->is_default))> Défaut</label>
            </div>
        </div>
        @php $meta = $list->metadata ?? []; @endphp
        <fieldset class="space-y-3 rounded-xl border border-gp-border p-4 dark:border-white/10">
            <legend class="px-1 text-xs font-bold uppercase text-gp-muted">Métadonnées</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="gp-label">Mode opérationnel</label>
                    <select name="operational_mode" class="gp-select w-full">
                        @foreach(\App\Models\CustomList::OPERATIONAL_MODES as $k => $v)
                            <option value="{{ $k }}" @selected(($meta['operational_mode'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="mt-6 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="requires_delivery_agent" value="1" @checked($meta['requires_delivery_agent'] ?? false)> Livreur / plateforme requis</label>
                <div><label class="gp-label">Méthode de paiement</label>
                    <select name="method" class="gp-select w-full">
                        @foreach(\App\Models\SalePayment::METHODS as $k => $v)
                            <option value="{{ $k }}" @selected(($meta['method'] ?? 'cash') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="gp-label">Échéance</label>
                    <select name="timing" class="gp-select w-full">
                        <option value="immediate" @selected(($meta['timing'] ?? 'immediate') === 'immediate')>Immédiat</option>
                        <option value="deferred" @selected(($meta['timing'] ?? '') === 'deferred')>Différé</option>
                    </select>
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-semibold text-gp-muted">Champs obligatoires (modes de paiement)</p>
                <div class="flex flex-wrap gap-3">
                    @foreach(\App\Models\CustomList::PAYMENT_FIELDS as $key => $label)
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="required_fields[]" value="{{ $key }}" @checked(in_array($key, $meta['required_fields'] ?? [], true))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><label class="gp-label">Taux taxe %</label><input type="number" step="0.01" name="rate" value="{{ $meta['rate'] ?? '' }}" class="gp-input w-full"></div>
                <div><label class="gp-label">Type remise</label><select name="discount_type" class="gp-select w-full"><option value="percent" @selected(($meta['discount_type'] ?? '') === 'percent')>%</option><option value="amount" @selected(($meta['discount_type'] ?? '') === 'amount')>Montant</option></select></div>
                <div><label class="gp-label">Valeur remise</label><input type="number" step="0.01" name="value" value="{{ $meta['value'] ?? '' }}" class="gp-input w-full"></div>
                <div><label class="gp-label">Groupe ticket</label><input name="group" value="{{ $meta['group'] ?? '' }}" class="gp-input w-full"></div>
                <div><label class="gp-label">Nature dépense</label><select name="expense_kind" class="gp-select w-full"><option value="fixed" @selected(($meta['expense_kind'] ?? '') === 'fixed')>Fixe</option><option value="variable" @selected(($meta['expense_kind'] ?? 'variable') === 'variable')>Variable</option></select></div>
            </div>
        </fieldset>
        <div class="flex gap-2">
            <button class="gp-btn-primary">Enregistrer</button>
            <a href="{{ route('settings.lists.index', ['type' => $list->type]) }}" class="gp-btn-secondary">Retour</a>
        </div>
    </form>
</div>
@endsection
