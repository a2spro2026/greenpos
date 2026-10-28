@php
    // Rimal V2 — message d'aide sur les graphiques vides (aucune donnée fictive).
    $rn = (string) request()->route()?->getName();
    $prefix = explode('.', $rn)[0] ?? '';
    $guides = [
        'home' => ['Les graphiques se remplissent avec vos ventes.', 'Ouvrir la caisse', 'pos.terminal'],
        'pos' => ['Encaissez un premier ticket pour voir l’activité de la caisse.', 'Ouvrir la caisse', 'pos.terminal'],
        'sales' => ['Aucune vente sur la période. Créez une vente ou encaissez depuis la caisse.', 'Nouvelle vente', 'sales.create'],
        'quotes' => ['Aucun devis sur la période.', 'Créer un devis', 'quotes.create'],
        'invoices' => ['Aucune facture sur la période.', 'Créer une facture', 'invoices.create'],
        'purchases' => ['Aucun achat sur la période. Créez un bon de commande puis réceptionnez-le.', 'Nouveau bon de commande', 'purchases.orders.create'],
        'suppliers' => ['Les statistiques apparaissent après vos premiers achats fournisseurs.', 'Nouveau bon de commande', 'purchases.orders.create'],
        'stock' => ['Aucun mouvement de stock. Réceptionnez un achat ou faites un inventaire.', 'Voir les réceptions', 'purchases.receipts.index'],
        'customers' => ['Ajoutez des clients et rattachez-les aux ventes pour voir ces statistiques.', 'Nouveau client', 'customers.create'],
        'crm' => ['Ajoutez des prospects et opportunités pour alimenter le CRM.', 'Voir le CRM', 'crm.dashboard'],
        'hr' => ['Ajoutez vos employés et leurs pointages pour voir ces graphiques.', 'Voir les employés', 'hr.employees.index'],
        'users' => ['Invitez des utilisateurs pour voir leur activité.', 'Nouvel utilisateur', 'users.create'],
        'reports' => ['Pas encore de données sur la période choisie. Élargissez les dates ou encaissez des ventes.', 'Ouvrir la caisse', 'pos.terminal'],
        'stores' => ['L’activité par boutique apparaît après les premières ventes.', 'Ouvrir la caisse', 'pos.terminal'],
    ];
    $g = $guides[$prefix] ?? ['Pas encore de données à afficher sur cette période.', null, null];
    $guide = ['text' => $g[0], 'label' => $g[1], 'url' => ($g[2] && \Illuminate\Support\Facades\Route::has($g[2])) ? route($g[2]) : null];
@endphp
<script>
    window.addEventListener('load', function () {
        if (!window.Chart || !Chart.instances) return;
        const guide = @json($guide);
        Object.values(Chart.instances).forEach(function (chart) {
            const sets = (chart.data && chart.data.datasets) || [];
            const hasData = sets.some(function (d) {
                return (d.data || []).some(function (v) {
                    const n = (v && typeof v === 'object') ? (v.y ?? v.r ?? v.x) : v;
                    return Number(n) !== 0 && n !== null && n !== undefined && !Number.isNaN(Number(n));
                });
            });
            if (hasData) return;
            const canvas = chart.canvas;
            const box = canvas.parentElement;
            if (!box || box.querySelector('.gp-chart-empty')) return;
            if (getComputedStyle(box).position === 'static') box.style.position = 'relative';
            canvas.style.opacity = '0.15';
            const el = document.createElement('div');
            el.className = 'gp-chart-empty absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center';
            const p = document.createElement('p');
            p.className = 'max-w-xs text-sm text-gp-muted';
            p.textContent = guide.text;
            el.appendChild(p);
            if (guide.url) {
                const a = document.createElement('a');
                a.href = guide.url; a.textContent = guide.label;
                a.className = 'text-sm font-semibold text-gp-primary hover:underline';
                el.appendChild(a);
            }
            box.appendChild(el);
        });
    });
</script>
