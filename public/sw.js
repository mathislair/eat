/**
 * eat — service worker.
 *
 * Navigations are network-first (fresh data) with an offline fallback to the
 * cached shell; build assets and icons are stale-while-revalidate so the app
 * loads instantly and survives a flaky connection.
 */
const CACHE = 'eat-v1';
const SHELL = ['/', '/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // App navigations: try the network, fall back to cache / the shell offline.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(request).then((cached) => cached || caches.match('/'))),
        );
        return;
    }

    // Static assets: serve from cache immediately, refresh in the background.
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname === '/manifest.webmanifest'
    ) {
        event.respondWith(
            caches.open(CACHE).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        cache.put(request, response.clone());
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            }),
        );
    }
});
