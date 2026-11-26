const CACHE = 'scount-scout-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(['/manifest-scout.webmanifest'])).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }
    const url = new URL(event.request.url);
    if (!url.pathname.includes('/scout') && !url.pathname.startsWith('/partidas/')) {
        return;
    }
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request).then((r) => r || caches.match('/partidas/1/scout')))
    );
});