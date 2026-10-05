@extends('layouts.app')
@php
    $editing = $printer->exists;
    $ticket = array_merge(\App\Models\Printer::defaultConfigs()['ticket_config'], $printer->ticket_config ?? []);
    $kitchen = array_merge(\App\Models\Printer::defaultConfigs()['kitchen_config'], $printer->kitchen_config ?? []);
    $advanced = array_merge(\App\Models\Printer::defaultConfigs()['advanced_config'], $printer->advanced_config ?? []);
@endphp
@section('heading', $editing ? 'Modifier le profil' : 'Nouveau profil')
@section('content')
@include('pos._nav')
<form method="POST" action="{{ $editing ? route('pos.printers.update', $printer) : route('pos.printers.store') }}" class="gp-card max-w-3xl space-y-4">
    @csrf @if($editing) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="gp-label">Nom</label><input name="name" value="{{ old('name', $printer->name) }}" class="gp-input w-full" required></div>
        <div><label class="gp-label">Rôle</label>
            <select name="role" class="gp-select w-full">
                @foreach(\App\Models\Printer::ROLES as $k => $v)<option value="{{ $k }}" @selected(old('role', $printer->role) === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div><label class="gp-label">Boutique</label>
            <select name="store_id" class="gp-select w-full"><option value="">Toutes</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected((string) old('store_id', $printer->store_id) === (string) $store->id)>{{ $store->name }}</option>@endforeach</select>
        </div>
        <div class="flex items-end gap-4 pb-2">
            <label class="text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $printer->is_default))> Défaut</label>
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $printer->is_active ?? true))> Actif</label>
        </div>
    </div>
    <fieldset class="rounded-xl border border-gp-border p-4 dark:border-white/10">
        <legend class="px-1 text-xs font-bold uppercase text-gp-muted">Ticket client</legend>
        <div class="mt-2 flex flex-wrap gap-4 text-sm">
            <label><input type="checkbox" name="show_logo" value="1" @checked($ticket['show_logo'] ?? false)> Logo</label>
            <label><input type="checkbox" name="show_ice" value="1" @checked($ticket['show_ice'] ?? false)> ICE</label>
            <label><input type="checkbox" name="show_qr" value="1" @checked($ticket['show_qr'] ?? false)> Code ticket</label>
            <label><input type="checkbox" name="ticket_auto_print" value="1" @checked($ticket['auto_print'] ?? true)> Impression auto</label>
        </div>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <input name="ticket_header" value="{{ $ticket['header'] ?? '' }}" placeholder="En-tête" class="gp-input">
            <input name="ticket_footer" value="{{ $ticket['footer'] ?? '' }}" placeholder="Pied" class="gp-input">
        </div>
    </fieldset>
    <fieldset class="rounded-xl border border-gp-border p-4 dark:border-white/10">
        <legend class="px-1 text-xs font-bold uppercase text-gp-muted">Cuisine</legend>
        <div class="mt-2 flex flex-wrap gap-4 text-sm">
            <label><input type="checkbox" name="group_by_category" value="1" @checked($kitchen['group_by_category'] ?? false)> Grouper par catégorie</label>
            <label><input type="checkbox" name="show_prices" value="1" @checked($kitchen['show_prices'] ?? false)> Prix</label>
            <label><input type="checkbox" name="kitchen_auto_print" value="1" @checked($kitchen['auto_print'] ?? false)> Impression auto</label>
        </div>
        <input name="kitchen_header" value="{{ $kitchen['header'] ?? 'CUISINE' }}" class="gp-input mt-3">
    </fieldset>
    <fieldset class="rounded-xl border border-gp-border p-4 dark:border-white/10">
        <legend class="px-1 text-xs font-bold uppercase text-gp-muted">Avancé</legend>
        <div class="mt-2 grid gap-3 sm:grid-cols-3">
            <label class="text-sm"><input type="checkbox" name="open_drawer" value="1" @checked($advanced['open_drawer'] ?? false)> Ouvrir le tiroir</label>
            <input type="number" name="copies" min="1" max="5" value="{{ $advanced['copies'] ?? 1 }}" class="gp-input" placeholder="Copies">
            <select name="paper_width" class="gp-select"><option value="80" @selected(($advanced['paper_width'] ?? 80) == 80)>80 mm</option><option value="58" @selected(($advanced['paper_width'] ?? 80) == 58)>58 mm</option></select>
        </div>
    </fieldset>
    <button class="gp-btn-primary">Enregistrer</button>
</form>
@endsection
