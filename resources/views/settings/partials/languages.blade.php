<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Langues</h2>
        <p class="text-xs text-gp-muted">Locale par défaut de l'interface</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Langue par défaut</label>
            <select name="default_locale" class="gp-input">
                @foreach(['fr' => 'Français', 'ar' => 'Arabe', 'en' => 'English'] as $code => $label)
                    <option value="{{ $code }}" @selected(($settings['default_locale'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Locale de secours</label>
            <select name="fallback_locale" class="gp-input">
                @foreach(['fr' => 'Français', 'ar' => 'Arabe', 'en' => 'English'] as $code => $label)
                    <option value="{{ $code }}" @selected(($settings['fallback_locale'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Langues disponibles (séparées par virgule)</label>
            <input type="text" name="available" value="{{ implode(', ', $settings['available'] ?? []) }}" class="gp-input">
        </div>
    </div>
</article>
