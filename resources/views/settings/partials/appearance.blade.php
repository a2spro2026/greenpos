<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Apparence</h2>
        <p class="text-xs text-gp-muted">Thème, couleurs et formats d'affichage</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Thème</label>
            <select name="theme" class="gp-input">
                <option value="light" @selected(($settings['theme'] ?? '') === 'light')>Clair</option>
                <option value="dark" @selected(($settings['theme'] ?? '') === 'dark')>Sombre</option>
                <option value="system" @selected(in_array($settings['theme'] ?? 'system', ['system', 'auto'], true))>Auto (système)</option>
            </select>
            <p class="mt-1.5 text-[11px] text-gp-muted">Le bouton thème du header cycle aussi Clair → Sombre → Auto.</p>
        </div>
        <div>
            <label class="gp-label">Couleur principale</label>
            <input type="color" name="primary_color" value="{{ $settings['primary_color'] ?? '#16a34a' }}" class="h-10 w-full cursor-pointer rounded-lg border border-gp-border bg-transparent p-1 dark:border-white/10">
        </div>
        <div>
            <label class="gp-label">Style sidebar</label>
            <select name="sidebar_style" class="gp-input">
                <option value="auto" @selected(in_array($settings['sidebar_style'] ?? 'auto', ['auto', ''], true))>Suivre le thème</option>
                <option value="light" @selected(($settings['sidebar_style'] ?? '') === 'light')>Toujours claire</option>
                <option value="dark" @selected(($settings['sidebar_style'] ?? '') === 'dark')>Toujours sombre</option>
            </select>
            <p class="mt-1.5 text-[11px] text-gp-muted">Par défaut, le menu latéral suit le mode clair ou sombre.</p>
        </div>
        <div>
            <label class="gp-label">Format des dates</label>
            <select name="date_format" class="gp-input">
                @foreach(['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'] as $fmt)
                    <option value="{{ $fmt }}" @selected(($settings['date_format'] ?? '') === $fmt)>{{ $fmt }} ({{ now()->format($fmt) }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Format de l'heure</label>
            <select name="time_format" class="gp-input">
                <option value="H:i" @selected(($settings['time_format'] ?? '') === 'H:i')>24h (H:i)</option>
                <option value="h:i A" @selected(($settings['time_format'] ?? '') === 'h:i A')>12h (h:i A)</option>
            </select>
        </div>
        <div>
            <label class="gp-label">Format des montants</label>
            <select name="amount_format" class="gp-input">
                <option value="fr" @selected(($settings['amount_format'] ?? '') === 'fr')>Français (1 234,56)</option>
                <option value="en" @selected(($settings['amount_format'] ?? '') === 'en')>Anglais (1,234.56)</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="compact_mode" value="1" class="rounded border-gp-border" @checked($settings['compact_mode'] ?? false)>
                Mode compact
            </label>
        </div>
    </div>
</article>
