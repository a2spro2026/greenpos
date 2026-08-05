<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold">Sauvegarde</h2>
                <p class="text-xs text-gp-muted">Politique de backup de l’entreprise</p>
            </div>
            <a href="{{ route('system.backups') }}" class="gp-btn-secondary text-xs">Ouvrir Sauvegardes & Santé</a>
        </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Fréquence</label>
            <select name="frequency" class="gp-input">
                @foreach(['daily' => 'Quotidienne', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuelle'] as $val => $label)
                    <option value="{{ $val }}" @selected(($settings['frequency'] ?? '') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="gp-label">Rétention (jours)</label>
            <input type="number" name="retention_days" value="{{ $settings['retention_days'] }}" class="gp-input" min="1">
        </div>
        <div class="flex flex-col gap-3 sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="auto_backup" value="1" class="rounded border-gp-border" @checked($settings['auto_backup'])>
                Sauvegarde automatique
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="include_files" value="1" class="rounded border-gp-border" @checked($settings['include_files'])>
                Inclure les fichiers / médias
            </label>
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Note</label>
            <textarea name="note" rows="2" class="gp-input">{{ $settings['note'] }}</textarea>
        </div>
    </div>
    <div class="rounded-xl border border-dashed border-gp-border px-4 py-3 text-xs text-gp-muted dark:border-white/10">
        Dernière sauvegarde : {{ $settings['last_backup_at'] ?? 'Jamais' }}
    </div>
</article>
