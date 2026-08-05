/**
 * GreenPOS Module Store — recherche & filtres chips (client).
 */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-module-store]');
    if (!root) return;

    const search = root.querySelector('[data-ms-search]');
    const chips = [...root.querySelectorAll('[data-ms-chip]')];
    const cards = [...root.querySelectorAll('[data-ms-card]')];
    const emptyFilter = root.querySelector('[data-ms-empty-filter]');

    let category = chips.find((c) => c.classList.contains('is-active'))?.dataset.msChip || 'Tous';

    const apply = () => {
        const q = (search?.value || '').trim().toLowerCase();
        let visible = 0;

        cards.forEach((card) => {
            const name = card.dataset.name || '';
            const cat = card.dataset.category || '';
            const matchCat = category === 'Tous' || cat === category;
            const matchQ = !q || name.includes(q);
            const show = matchCat && matchQ;
            card.classList.toggle('is-filtered-out', !show);
            if (show) visible += 1;
        });

        if (emptyFilter) {
            const showEmpty = visible === 0 && cards.length > 0;
            emptyFilter.hidden = !showEmpty;
            emptyFilter.classList.toggle('is-hidden', !showEmpty);
        }
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            category = chip.dataset.msChip || 'Tous';
            chips.forEach((c) => {
                const on = c === chip;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            apply();
        });
    });

    search?.addEventListener('input', apply);
    apply();
});
