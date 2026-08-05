(() => {
    const root = document.querySelector('[data-branding-root]');
    if (!root) return;

    const shell = root.querySelector('[data-preview-shell]');
    const bar = root.querySelector('[data-preview-bar]');
    const btn = root.querySelector('[data-preview-btn]');
    const link = root.querySelector('[data-preview-link]');
    const invoiceAccent = root.querySelector('[data-preview-invoice-accent]');

    const apply = () => {
        const primary = root.querySelector('[data-brand-field="primary"]')?.value || root.dataset.primary;
        const secondary = root.querySelector('[data-brand-field="secondary"]')?.value || root.dataset.secondary;
        const button = root.querySelector('[data-brand-field="button"]')?.value || root.dataset.button;
        const linkColor = root.querySelector('[data-brand-field="link"]')?.value || root.dataset.link;
        const invoiceColor = root.querySelector('[data-brand-field="invoice_color"]')?.value || primary;
        const theme = root.querySelector('[data-brand-field="theme"]')?.value || root.dataset.theme;
        const density = root.querySelector('[data-brand-field="density"]')?.value || root.dataset.density;

        root.style.setProperty('--preview-primary', primary);
        root.style.setProperty('--preview-button', button);
        root.style.setProperty('--preview-link', linkColor);
        root.style.setProperty('--preview-invoice', invoiceColor);
        if (bar) bar.style.background = `linear-gradient(90deg, ${primary}, ${secondary})`;
        if (btn) btn.style.background = button;
        if (link) link.style.color = linkColor;
        if (invoiceAccent) invoiceAccent.style.background = invoiceColor;

        const trade = root.querySelector('[data-brand-field="trade"]')?.value;
        const tagline = root.querySelector('[data-brand-field="tagline"]')?.value;
        const welcome = root.querySelector('[data-brand-field="welcome"]')?.value;
        const footer = root.querySelector('[data-brand-field="footer"]')?.value;
        const invHeader = root.querySelector('[data-brand-field="invoice_header"]')?.value;
        const invFooter = root.querySelector('[data-brand-field="invoice_footer"]')?.value;

        const setText = (sel, val) => {
            const el = root.querySelector(sel);
            if (el && typeof val === 'string') el.textContent = val || el.textContent;
        };
        if (trade !== undefined) setText('[data-preview-trade]', trade);
        if (tagline !== undefined) setText('[data-preview-tagline]', tagline || 'Slogan de votre marque');
        if (welcome !== undefined) setText('[data-preview-welcome]', welcome);
        if (footer !== undefined) setText('[data-preview-footer]', footer);
        if (invHeader !== undefined) setText('[data-preview-invoice-header]', invHeader || 'En-tête document');
        if (invFooter !== undefined) setText('[data-preview-invoice-footer]', invFooter || 'Pied de page');

        if (shell) {
            shell.classList.toggle('is-dark', theme === 'dark');
            shell.classList.toggle('is-compact', density === 'compact');
            shell.classList.toggle('is-comfortable', density === 'comfortable');
        }
    };

    root.querySelectorAll('[data-brand-field]').forEach((el) => {
        el.addEventListener('input', apply);
        el.addEventListener('change', apply);
    });
    apply();
})();
