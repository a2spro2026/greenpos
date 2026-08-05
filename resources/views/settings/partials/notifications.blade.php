<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Notifications</h2>
        <p class="text-xs text-gp-muted">Email, SMS (préparé) et alertes internes</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach([
            'email_enabled' => 'Notifications email',
            'sms_enabled' => 'SMS (préparé — non connecté)',
            'internal_enabled' => 'Notifications internes',
            'stock_alerts' => 'Alertes stock',
            'stock_alert_threshold' => 'Seuil stock bas',
            'cash_alerts' => 'Alertes caisse',
            'invoice_overdue' => 'Factures en retard',
            'low_stock_email' => 'Email stock bas',
            'daily_summary' => 'Résumé quotidien',
        ] as $key => $label)
            <label class="inline-flex items-center gap-2 rounded-xl bg-gp-bg px-3 py-2.5 text-sm dark:bg-white/5">
                <input type="checkbox" name="{{ $key }}" value="1" class="rounded border-gp-border" @checked($settings[$key] ?? false)>
                {{ $label }}
            </label>
        @endforeach
    </div>
    <p class="text-xs text-gp-muted">Le canal SMS est préparé pour une intégration future.</p>
</article>
