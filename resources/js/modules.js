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

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-module-setup]');
    if (!root) return;

    const search = root.querySelector('[data-ms-search]');
    const chips = [...root.querySelectorAll('[data-ms-chip]')];
    const cards = [...root.querySelectorAll('[data-ms-card]')];
    const sections = [...root.querySelectorAll('[data-ms-section]')];
    const emptyFilter = root.querySelector('[data-ms-empty-filter]');
    const countEl = document.querySelector('[data-ms-count]');
    let category = 'Tous';

    const refreshCount = () => {
        if (countEl) {
            countEl.textContent = String(cards.filter((c) => c.classList.contains('is-picked')).length);
        }
    };

    const applyFilter = () => {
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

        sections.forEach((section) => {
            const hasVisible = [...section.querySelectorAll('[data-ms-card]')].some(
                (card) => !card.classList.contains('is-filtered-out')
            );
            section.classList.toggle('is-filtered-out', !hasVisible);
        });

        if (emptyFilter) {
            const showEmpty = visible === 0;
            emptyFilter.hidden = !showEmpty;
            emptyFilter.classList.toggle('is-hidden', !showEmpty);
        }
    };

    const toggleCard = (card) => {
        if (card.dataset.selectable !== '1') return;
        const on = !card.classList.contains('is-picked');
        card.classList.toggle('is-picked', on);
        const input = card.querySelector('.ms-pick-input');
        if (input) input.checked = on;
        const btn = card.querySelector('.ms-pick-btn');
        if (btn) btn.textContent = on ? 'Ajouté' : 'Ajouter';
        const state = card.querySelector('.ms-pick-state');
        if (state) state.textContent = on ? 'Sélectionné' : 'Disponible';
        refreshCount();
    };

    cards.forEach((card) => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            toggleCard(card);
        });
        card.querySelector('.ms-pick-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleCard(card);
        });
    });

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            category = chip.dataset.msChip || 'Tous';
            chips.forEach((c) => c.classList.toggle('is-active', c === chip));
            applyFilter();
            const target = chip.dataset.msScroll;
            if (category !== 'Tous' && target && !search?.value) {
                document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                root.querySelector('.ms-setup-main')?.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    });

    search?.addEventListener('input', applyFilter);
    applyFilter();
    refreshCount();
});
