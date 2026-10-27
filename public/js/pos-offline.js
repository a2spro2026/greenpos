/**
 * Offline POS depth for the Blade terminal.
 *
 * Limits — this is not full SPA / IndexedDB parity:
 * - A valid Blade session cookie is required. There is no offline guest login.
 * - The catalog snapshot and the pending sales queue live in IndexedDB
 *   database `greenpos-pos`. The queue is also mirrored to localStorage
 *   key `pos-offline-queue` so a browser without IndexedDB still keeps tickets.
 * - Stock shown offline is the last snapshot. The server re-checks stock at sync.
 * - Several terminals can queue the same product; the server accepts the first
 *   valid sync and rejects the rest when stock is gone.
 * - No offline product edit, refund, session open/close, or guest checkout.
 * - Sync fails while the cash session is closed.
 */
(function () {
    const DB_NAME = 'greenpos-pos';
    const DB_VERSION = 1;
    const QUEUE_KEY = 'pos-offline-queue';

    function openDb() {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                resolve(null);
                return;
            }
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains('catalog')) {
                    db.createObjectStore('catalog');
                }
                if (!db.objectStoreNames.contains('pendingSales')) {
                    db.createObjectStore('pendingSales', { keyPath: 'client_uuid' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    function readQueue() {
        try {
            return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function writeQueue(rows) {
        localStorage.setItem(QUEUE_KEY, JSON.stringify(rows));
    }

    async function idbGet(store, key) {
        const db = await openDb();
        if (!db) return null;
        return new Promise((resolve, reject) => {
            const tx = db.transaction(store, 'readonly');
            const req = tx.objectStore(store).get(key);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => reject(req.error);
        });
    }

    async function idbPut(store, value, key) {
        const db = await openDb();
        if (!db) return;
        return new Promise((resolve, reject) => {
            const tx = db.transaction(store, 'readwrite');
            const req = key === undefined ? tx.objectStore(store).put(value) : tx.objectStore(store).put(value, key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }

    async function idbAll(store) {
        const db = await openDb();
        if (!db) return [];
        return new Promise((resolve, reject) => {
            const tx = db.transaction(store, 'readonly');
            const req = tx.objectStore(store).getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async function idbDelete(store, key) {
        const db = await openDb();
        if (!db) return;
        return new Promise((resolve, reject) => {
            const tx = db.transaction(store, 'readwrite');
            const req = tx.objectStore(store).delete(key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }

    async function products() {
        const snapshot = await idbGet('catalog', 'current');
        if (snapshot && Array.isArray(snapshot.products) && snapshot.products.length) {
            return snapshot.products;
        }
        return [];
    }

    async function saveSnapshot(snapshot) {
        await idbPut('catalog', snapshot, 'current');
    }

    async function enqueue(sale) {
        const row = Object.assign({ queued_at: new Date().toISOString() }, sale);
        await idbPut('pendingSales', row);
        const queue = readQueue().filter((item) => item.client_uuid !== row.client_uuid);
        queue.push(row);
        writeQueue(queue);
        return row;
    }

    async function pending() {
        const rows = await idbAll('pendingSales');
        if (rows.length) return rows;
        return readQueue();
    }

    async function count() {
        return (await pending()).length;
    }

    async function remove(uuid) {
        await idbDelete('pendingSales', uuid);
        writeQueue(readQueue().filter((item) => item.client_uuid !== uuid));
    }

    async function flush(syncUrl, csrf) {
        const sales = await pending();
        if (!sales.length) return { results: [] };
        const res = await fetch(syncUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                sales: sales.map((sale) => ({
                    client_uuid: sale.client_uuid,
                    items: sale.items,
                    payments: sale.payments,
                    customer_id: sale.customer_id,
                    notes: sale.notes,
                    service_mode_list_id: sale.service_mode_list_id,
                    ticket_name: sale.ticket_name,
                    ticket_group: sale.ticket_group,
                    predefined_ticket_id: sale.predefined_ticket_id,
                    delivery_platform_id: sale.delivery_platform_id,
                    delivery_address: sale.delivery_address,
                })),
            }),
        });
        const data = await res.json().catch(() => ({ results: [] }));
        if (!res.ok && !data.results) {
            throw new Error(data.message || 'Synchronisation impossible');
        }
        for (const result of data.results || []) {
            if (result.ok) await remove(result.client_uuid);
        }
        return data;
    }

    window.GreenPosOffline = { products, saveSnapshot, enqueue, pending, count, flush, remove };
})();
