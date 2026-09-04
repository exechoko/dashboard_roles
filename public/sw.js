// Service worker de la app móvil (/movil). Solo cachea el "app shell"
// (CSS, íconos, manifest y la página offline); todo lo demás (páginas /movil/*
// y los JSON de datos) va siempre a la red: son datos policiales, no se
// guardan en el celular.
const CACHE_NAME = 'car911-movil-shell-v1';
const APP_SHELL = [
    '/css/movil.css',
    '/img/pwa-192.png',
    '/img/pwa-512.png',
    '/manifest.webmanifest',
    '/movil/offline',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (APP_SHELL.includes(url.pathname)) {
        // Stale-while-revalidate: responde YA con lo cacheado si existe (rápido,
        // funciona offline), pero en paralelo pide la versión actual y la deja
        // guardada para la próxima vez. Así una actualización de movil.css se ve
        // en la segunda visita, sin depender de acordarse de bumpear CACHE_NAME.
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) =>
                cache.match(request).then((cached) => {
                    const actualizado = fetch(request).then((response) => {
                        cache.put(request, response.clone());
                        return response;
                    }).catch(() => cached);

                    return cached || actualizado;
                })
            )
        );
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/movil/offline'))
        );
    }
});
