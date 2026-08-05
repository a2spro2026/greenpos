<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Paiements</h2>
        <p class="text-xs text-gp-muted">Modes acceptés, arrondi et remboursements</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Devise par défaut</label>
            <input type="text" name="default_currency" value="{{ $settings['default_currency'] }}" class="gp-input" maxlength="3">
        </div>
        <div>
            <label class="gp-label">Pas d'arrondi</label>
            <input type="number" step="0.01" name="rounding_step" value="{{ $settings['rounding_step'] }}" class="gp-input">
        </div>
        <div class="flex flex-col gap-3 sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="allow_partial" value="1" class="rounded border-gp-border" @checked($settings['allow_partial'])>
                Paiements partiels
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="allow_refunds" value="1" class="rounded border-gp-border" @checked($settings['allow_refunds'])>
                Autoriser les remboursements
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="rounding_enabled" value="1" class="rounded border-gp-border" @checked($settings['rounding_enabled'])>
                Arrondi activé
            </label>
        </div>
        <div class="sm:col-span-2">
            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gp-muted">Modes de paiement</p>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'cash' => 'Espèces',
                    'card' => 'Carte',
                    'bank_transfer' => 'Virement',
                    'mobile' => 'Mobile money',
                    'check' => 'Chèque',
                    'other' => 'Autre',
                ] as $key => $label)
                    <label class="inline-flex items-center gap-2 rounded-xl bg-gp-bg px-3 py-2.5 text-sm dark:bg-white/5">
                        <input type="checkbox" name="methods[{{ $key }}]" value="1" class="rounded border-gp-border" @checked(data_get($settings, 'methods.'.$key))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</article>
