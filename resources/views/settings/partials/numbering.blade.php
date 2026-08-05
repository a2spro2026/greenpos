<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">Numérotation des documents</h2>
        <p class="text-xs text-gp-muted">Préfixes et padding des références</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="gp-label">Préfixe factures</label>
            <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Padding factures</label>
            <input type="number" name="invoice_padding" value="{{ $settings['invoice_padding'] }}" class="gp-input" min="1" max="10">
        </div>
        <div>
            <label class="gp-label">Préfixe devis</label>
            <input type="text" name="quote_prefix" value="{{ $settings['quote_prefix'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Padding devis</label>
            <input type="number" name="quote_padding" value="{{ $settings['quote_padding'] }}" class="gp-input" min="1" max="10">
        </div>
        <div>
            <label class="gp-label">Préfixe ventes</label>
            <input type="text" name="sale_prefix" value="{{ $settings['sale_prefix'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Padding ventes</label>
            <input type="number" name="sale_padding" value="{{ $settings['sale_padding'] }}" class="gp-input" min="1" max="10">
        </div>
        <div>
            <label class="gp-label">Préfixe tickets POS</label>
            <input type="text" name="pos_prefix" value="{{ $settings['pos_prefix'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Padding tickets</label>
            <input type="number" name="pos_padding" value="{{ $settings['pos_padding'] }}" class="gp-input" min="1" max="10">
        </div>
        <div>
            <label class="gp-label">Préfixe avoirs</label>
            <input type="text" name="credit_note_prefix" value="{{ $settings['credit_note_prefix'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Préfixe commandes achat</label>
            <input type="text" name="purchase_prefix" value="{{ $settings['purchase_prefix'] }}" class="gp-input">
        </div>
        <div class="flex flex-col justify-end gap-3 pb-1 sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="include_year" value="1" class="rounded border-gp-border" @checked($settings['include_year'])>
                Inclure l'année dans le numéro
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="reset_yearly" value="1" class="rounded border-gp-border" @checked($settings['reset_yearly'])>
                Réinitialiser chaque année
            </label>
        </div>
    </div>
    <div class="rounded-xl bg-gp-bg px-4 py-3 text-xs text-gp-muted dark:bg-white/5">
        Exemple facture : <strong class="text-gp-text dark:text-white">{{ $settings['invoice_prefix'] }}-{{ now()->format('Y') }}-{{ str_pad('1', (int)$settings['invoice_padding'], '0', STR_PAD_LEFT) }}</strong>
    </div>
</article>
