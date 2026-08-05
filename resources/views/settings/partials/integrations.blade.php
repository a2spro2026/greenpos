<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Intégrations</h2>
        <p class="text-xs text-gp-muted">Connecteurs préparés pour les prochaines versions</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-gp-bg p-4 dark:bg-white/5">
            <p class="text-xs font-bold uppercase text-gp-muted">SMTP</p>
            <p class="mt-1 text-sm font-semibold">Préparé</p>
        </div>
        <div class="rounded-xl bg-gp-bg p-4 dark:bg-white/5">
            <p class="text-xs font-bold uppercase text-gp-muted">Passerelle SMS</p>
            <p class="mt-1 text-sm font-semibold">Préparé</p>
        </div>
        <div class="rounded-xl bg-gp-bg p-4 dark:bg-white/5">
            <p class="text-xs font-bold uppercase text-gp-muted">Paiement en ligne</p>
            <p class="mt-1 text-sm font-semibold">Préparé</p>
        </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="gp-label">URL Webhook</label>
            <input type="url" name="webhook_url" value="{{ $settings['webhook_url'] }}" class="gp-input" placeholder="https://">
        </div>
        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="api_enabled" value="1" class="rounded border-gp-border" @checked($settings['api_enabled'] ?? false)>
                API externe (préparé)
            </label>
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Notes</label>
            <textarea name="notes" rows="2" class="gp-input">{{ $settings['notes'] }}</textarea>
        </div>
    </div>
</article>
