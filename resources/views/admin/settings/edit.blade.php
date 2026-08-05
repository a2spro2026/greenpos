@extends('layouts.admin')
@section('title', 'Paramètres')
@section('breadcrumb', 'Plateforme')
@section('heading', 'Paramètres plateforme')
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="pa-card max-w-2xl space-y-4">
@csrf
@method('PUT')
<div class="pa-grid-2">
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Nom plateforme</label>
<input type="text" name="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}" class="pa-input" required>
</div>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">E-mail support</label>
<input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" class="pa-input">
</div>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Téléphone support</label>
<input type="text" name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" class="pa-input">
</div>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Essai par défaut (jours)</label>
<input type="number" name="default_trial_days" value="{{ old('default_trial_days', $settings['default_trial_days']) }}" class="pa-input" min="1" max="90">
</div>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Devise</label>
<input type="text" name="default_currency" value="{{ old('default_currency', $settings['default_currency']) }}" class="pa-input" maxlength="3">
</div>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Pays</label>
<input type="text" name="default_country" value="{{ old('default_country', $settings['default_country']) }}" class="pa-input">
</div>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Préfixe facture</label>
<input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}" class="pa-input">
</div>
</div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="maintenance_mode" value="1" @checked($settings['maintenance_mode'])> Mode maintenance</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_self_signup" value="1" @checked($settings['allow_self_signup'])> Autoriser l’inscription libre</label>
<div>
<label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-500">Note interne</label>
<textarea name="note" rows="3" class="pa-textarea">{{ old('note', $settings['note']) }}</textarea>
</div>
<button class="pa-btn pa-btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
