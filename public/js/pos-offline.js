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
                sales: sales.slice(0, 50).map((sale) => {
                    const row = Object.assign({}, sale);
                    delete row.queued_at;
                    delete row.held_sale_id;
                    row.offline_at = row.offline_at || sale.queued_at;
                    return row;
                }),
            }),
        });
        const data = await res.json().catch(() => ({ results: [] }));
        if (!res.ok && !data.results) {
            throw new Error(data.message || 'Synchronisation impossible');
        }
        for (const result of data.results || []) {
            // ok = enregistré (ou déjà reçu) ; conflict = consigné côté serveur (écran Conflits de synchro).
            if (result.ok || result.conflict) await remove(result.client_uuid);
        }
        return data;
    }

    function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        const b = new Uint8Array(16);
        (window.crypto || {}).getRandomValues ? crypto.getRandomValues(b) : b.forEach((_, i) => { b[i] = Math.floor(Math.random() * 256); });
        b[6] = (b[6] & 0x0f) | 0x40; b[8] = (b[8] & 0x3f) | 0x80;
        const h = Array.from(b, (x) => x.toString(16).padStart(2, '0')).join('');
        return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
    }

    async function snapshot() {
        return idbGet('catalog', 'current');
    }

    window.GreenPosOffline = { products, saveSnapshot, snapshot, enqueue, pending, count, flush, remove, uuid };
})();
