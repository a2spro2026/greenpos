<article class="gp-card space-y-5">
    <div class="flex items-center justify-between border-b border-gp-border pb-4 dark:border-white/10">
        <div>
            <h2 class="text-sm font-bold">Fiscalité & TVA</h2>
            <p class="text-xs text-gp-muted">Taux par défaut et affichage</p>
        </div>
        <span class="gp-badge">Taxe</span>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="gp-label">Taux TVA par défaut (%)</label>
            <input type="number" step="0.01" name="default_tax_rate" value="{{ $settings['default_tax_rate'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Libellé taxe</label>
            <input type="text" name="tax_label" value="{{ $settings['tax_label'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Libellé n° fiscal</label>
            <input type="text" name="tax_number_label" value="{{ $settings['tax_number_label'] }}" class="gp-input">
        </div>
        <div class="flex flex-col justify-end gap-3 pb-1">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="prices_include_tax" value="1" class="rounded border-gp-border" @checked($settings['prices_include_tax'])>
                Prix TTC
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="show_tax_on_tickets" value="1" class="rounded border-gp-border" @checked($settings['show_tax_on_tickets'])>
                Afficher la TVA sur les tickets
            </label>
        </div>
    </div>
    @if(!empty($settings['tax_rates']))
        <div class="rounded-xl bg-gp-bg p-4 dark:bg-white/5">
            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gp-muted">Taux prédéfinis</p>
            <div class="flex flex-wrap gap-2">
                @foreach($settings['tax_rates'] as $rate)
                    <span class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold ring-1 ring-gp-border dark:bg-white/10 dark:ring-white/10">
                        {{ $rate['name'] }} · {{ $rate['rate'] }}%
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</article>
