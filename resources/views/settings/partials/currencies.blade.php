<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Devises</h2>
        <p class="text-xs text-gp-muted">Devise principale et format monétaire</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Devise par défaut</label>
            <input type="text" name="default_currency" value="{{ $settings['default_currency'] }}" class="gp-input" maxlength="3">
        </div>
        <div>
            <label class="gp-label">Devises disponibles (séparées par virgule)</label>
            <input type="text" name="available" value="{{ implode(', ', $settings['available'] ?? []) }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Décimales</label>
            <input type="number" name="decimal_places" value="{{ $settings['decimal_places'] }}" class="gp-input" min="0" max="4">
        </div>
        <div>
            <label class="gp-label">Séparateur milliers</label>
            <input type="text" name="thousand_separator" value="{{ $settings['thousand_separator'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Séparateur décimal</label>
            <input type="text" name="decimal_separator" value="{{ $settings['decimal_separator'] }}" class="gp-input" maxlength="1">
        </div>
        <div>
            <label class="gp-label">Position symbole</label>
            <select name="symbol_position" class="gp-input">
                <option value="after" @selected(($settings['symbol_position'] ?? '') === 'after')>Après (100 MAD)</option>
                <option value="before" @selected(($settings['symbol_position'] ?? '') === 'before')>Avant (MAD 100)</option>
            </select>
        </div>
        <div>
            <label class="gp-label">Mode d'arrondi</label>
            <select name="rounding_mode" class="gp-input">
                <option value="standard" @selected(($settings['rounding_mode'] ?? '') === 'standard')>Standard</option>
                <option value="up" @selected(($settings['rounding_mode'] ?? '') === 'up')>Toujours supérieur</option>
                <option value="down" @selected(($settings['rounding_mode'] ?? '') === 'down')>Toujours inférieur</option>
            </select>
        </div>
    </div>
</article>
