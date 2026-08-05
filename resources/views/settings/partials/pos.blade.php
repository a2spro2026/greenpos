<article class="gp-card space-y-5">
    <div class="border-b border-gp-border pb-4 dark:border-white/10">
        <h2 class="text-sm font-bold">POS & Caisse</h2>
        <p class="text-xs text-gp-muted">Sessions, impression et périphériques</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-3 rounded-xl bg-gp-bg p-4 dark:bg-white/5 sm:col-span-2">
            <p class="text-xs font-bold uppercase tracking-wide text-gp-muted">Sessions</p>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="auto_open_session" value="1" class="rounded border-gp-border" @checked($settings['auto_open_session'])>
                    Ouverture automatique
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="auto_close_session" value="1" class="rounded border-gp-border" @checked($settings['auto_close_session'])>
                    Fermeture automatique
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="allow_held_tickets" value="1" class="rounded border-gp-border" @checked($settings['allow_held_tickets'])>
                    Tickets en attente
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="require_customer" value="1" class="rounded border-gp-border" @checked($settings['require_customer'])>
                    Client obligatoire
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_stock_warning" value="1" class="rounded border-gp-border" @checked($settings['show_stock_warning'])>
                    Alerte stock
                </label>
            </div>
        </div>
        <div>
            <label class="gp-label">Imprimante par défaut</label>
            <input type="text" name="default_printer" value="{{ $settings['default_printer'] }}" class="gp-input" placeholder="Ex. Epson TM-T20">
        </div>
        <div>
            <label class="gp-label">Caisse / tiroir par défaut</label>
            <input type="text" name="default_cash_drawer" value="{{ $settings['default_cash_drawer'] }}" class="gp-input">
        </div>
        <div>
            <label class="gp-label">Copies ticket</label>
            <input type="number" name="print_copies" value="{{ $settings['print_copies'] }}" class="gp-input" min="0" max="5">
        </div>
        <div class="flex flex-col justify-end gap-3 pb-1">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="print_ticket" value="1" class="rounded border-gp-border" @checked($settings['print_ticket'])>
                Impression ticket
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="barcode_reader" value="1" class="rounded border-gp-border" @checked($settings['barcode_reader'])>
                Lecteur code-barres
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="scanner_enabled" value="1" class="rounded border-gp-border" @checked($settings['scanner_enabled'])>
                Scanner
            </label>
        </div>
        <div class="sm:col-span-2">
            <label class="gp-label">Pied de ticket</label>
            <textarea name="receipt_footer" rows="2" class="gp-input">{{ $settings['receipt_footer'] }}</textarea>
        </div>
    </div>
</article>
