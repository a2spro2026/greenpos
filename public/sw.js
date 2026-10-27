/**
 * Rimal POS — service worker versionné.
 * La version vient de l'URL d'enregistrement (/sw.js?v=<hash build>) : chaque nouveau build
 * installe un nouveau cache et purge les anciens caches « rimal-pos-* » / « greenpos-pos-* » du navigateur.
 * Stratégie network-first : le serveur reste la source de vérité ; le cache ne sert que hors connexion.
 * Les POST (paiement, synchro) ne passent jamais par le cache : la file hors ligne est dans IndexedDB.
 */
const VERSION = new URL(self.location.href).searchParams.get('v') || 'dev';
const CACHE = 'rimal-pos-' + VERSION;
const PRECACHE = ['/js/pos-offline.js'];

function cacheable(url) {
    return url.origin === self.location.origin && (
        url.pathname === '/pos/terminal'
        || url.pathname.startsWith('/pos/catalog')
        || url.pathname.startsWith('/pos/offline/snapshot')
        || url.pathname.startsWith('/js/pos-offline.js')
        || url.pathname.startsWith('/build/')
    );
}

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)).catch(() => {}).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys
            .filter((k) => (k.startsWith('rimal-pos-') || k.startsWith('greenpos-pos-')) && k !== CACHE)
            .map((k) => caches.delete(k)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data === 'version') event.source && event.source.postMessage({ type: 'sw-version', version: VERSION });
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (!cacheable(url)) return;

    event.respondWith((async () => {
        try {
            const fresh = await fetch(request);
            if (fresh.ok && !fresh.redirected) {
                const cache = await caches.open(CACHE);
                cache.put(request, fresh.clone());
            }
            return fresh;
        } catch (error) {
            const cached = await caches.match(request, { ignoreSearch: url.pathname === '/pos/terminal' });
            if (cached) return cached;
            throw error;
        }
    })());
});
