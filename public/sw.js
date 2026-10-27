/**
 * Network-first cache for the POS catalog snapshot and static shell assets.
 * The Blade terminal itself is not served as an offline guest app: checkout
 * still needs the session cookie, and queued tickets sync when the network returns.
 */
const CACHE = 'greenpos-pos-v1';

function cacheable(url) {
    return url.origin === self.location.origin && (
        url.pathname.startsWith('/pos/catalog')
        || url.pathname.startsWith('/pos/offline/snapshot')
        || url.pathname.startsWith('/js/pos-offline.js')
        || url.pathname.startsWith('/build/')
    );
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (!cacheable(url)) return;

    event.respondWith((async () => {
        try {
            const fresh = await fetch(request);
            if (fresh.ok) {
                const cache = await caches.open(CACHE);
                cache.put(request, fresh.clone());
            }
            return fresh;
        } catch (error) {
            const cached = await caches.match(request);
            if (cached) return cached;
            throw error;
        }
    })());
});
