<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Sécurité</h2>
        <p class="text-xs text-gp-muted">Session, mots de passe et journalisation</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Durée de session (minutes)</label>
            <input type="number" name="session_lifetime" value="{{ $settings['session_lifetime'] }}" class="gp-input" min="15">
        </div>
        <div>
            <label class="gp-label">Longueur min. mot de passe</label>
            <input type="number" name="password_min_length" value="{{ $settings['password_min_length'] }}" class="gp-input" min="6">
        </div>
        <div>
            <label class="gp-label">Tentatives de connexion max</label>
            <input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] }}" class="gp-input" min="1">
        </div>
        <div class="flex flex-col justify-end gap-3 pb-1 sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="password_require_mixed" value="1" class="rounded border-gp-border" @checked($settings['password_require_mixed'])>
                Majuscules / minuscules obligatoires
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="password_require_numbers" value="1" class="rounded border-gp-border" @checked($settings['password_require_numbers'])>
                Chiffres obligatoires
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="password_require_symbols" value="1" class="rounded border-gp-border" @checked($settings['password_require_symbols'])>
                Symboles obligatoires
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="two_factor_enabled" value="1" class="rounded border-gp-border" @checked($settings['two_factor_enabled'])>
                Double authentification (préparé)
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="audit_logging" value="1" class="rounded border-gp-border" @checked($settings['audit_logging'])>
                Journalisation des actions
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="allowed_devices_only" value="1" class="rounded border-gp-border" @checked($settings['allowed_devices_only'])>
                Appareils autorisés uniquement (préparé)
            </label>
        </div>
    </div>
</article>
