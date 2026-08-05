/**
 * CRM Pipeline drag & drop
 */
(function () {
    const board = document.getElementById('crm-pipeline');
    if (! board) return;

    let dragged = null;

    board.querySelectorAll('.crm-card').forEach((card) => {
        card.addEventListener('dragstart', (e) => {
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.id);
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            board.querySelectorAll('.crm-column-body').forEach((z) => z.classList.remove('drag-over'));
            dragged = null;
        });
    });

    board.querySelectorAll('[data-dropzone]').forEach((zone) => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('drag-over');
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', async (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            const id = e.dataTransfer.getData('text/plain') || dragged?.dataset.id;
            if (! id || ! dragged) return;

            const column = zone.closest('.crm-column');
            const stage = column?.dataset.stage;
            if (! stage) return;

            zone.appendChild(dragged);

            const url = board.dataset.moveUrlTemplate.replace('__ID__', id);
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': board.dataset.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ stage, order: Array.from(zone.children).indexOf(dragged) }),
                });
                if (! res.ok) throw new Error('Move failed');
                // refresh counts quickly
                const head = column.querySelector('.crm-column-head p');
                if (head) {
                    const count = zone.querySelectorAll('.crm-card').length;
                    head.textContent = `${count} deals`;
                }
            } catch (err) {
                console.error(err);
                window.location.reload();
            }
        });
    });
})();
