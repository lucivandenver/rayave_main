const CACHE_NAME = 'rayave-pwa-v2';

const STATIC_FILES = [
    './offline.html',
    './manifest.webmanifest',
    './assets/css/custom.css',
    './assets/js/main.js',
    './assets/icons/rayave-icon-192.png',
    './assets/icons/rayave-icon-512.png',
    './assets/icons/rayave-icon-maskable.png'
];

const IS_LOCAL_DEV = self.location.hostname === 'localhost' || self.location.hostname === '127.0.0.1';

self.addEventListener('install', (event) => {
    if (IS_LOCAL_DEV) {
        self.skipWaiting();
        return;
    }
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_FILES))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => IS_LOCAL_DEV || key !== CACHE_NAME)
                .map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (IS_LOCAL_DEV || request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const isSameOrigin = url.origin === self.location.origin;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('./offline.html'))
        );
        return;
    }

    const isStaticAsset = isSameOrigin && (
        url.pathname.includes('/assets/') ||
        /\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?)$/i.test(url.pathname)
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }

                return fetch(request).then((response) => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, responseClone));
                    return response;
                });
            })
        );
        return;
    }

    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
