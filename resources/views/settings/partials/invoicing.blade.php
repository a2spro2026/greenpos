<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Facturation</h2>
        <p class="text-xs text-gp-muted">Conditions, délais et modèle PDF</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Conditions de paiement</label>
            <input type="text" name="default_payment_terms" value="{{ $settings['default_payment_terms'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Délai (jours)</label>
            <input type="number" name="default_due_days" value="{{ $settings['default_due_days'] }}" class="gp-input" min="0">
        </div>
        <div>
            <label class="gp-label">Modèle PDF</label>
            <select name="pdf_template" class="gp-input">
                <option value="standard" @selected(($settings['pdf_template'] ?? '') === 'standard')>Standard</option>
                <option value="compact" @selected(($settings['pdf_template'] ?? '') === 'compact')>Compact</option>
                <option value="premium" @selected(($settings['pdf_template'] ?? '') === 'premium')>Premium</option>
            </select>
        </div>
        <div class="flex flex-col justify-end gap-3 pb-1">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="show_logo" value="1" class="rounded border-gp-border" @checked($settings['show_logo'])>
                Afficher le logo
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="auto_send_email" value="1" class="rounded border-gp-border" @checked($settings['auto_send_email'])>
                Envoi email automatique
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="numbering_aligned" value="1" class="rounded border-gp-border" @checked($settings['numbering_aligned'])>
                Aligner sur la numérotation globale
            </label>
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Notes de bas de page</label>
            <textarea name="footer_notes" rows="2" class="gp-input">{{ $settings['footer_notes'] }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Mentions légales</label>
            <textarea name="legal_mentions" rows="3" class="gp-input">{{ $settings['legal_mentions'] }}</textarea>
        </div>
    </div>
</article>
