/**
 * Falhen Media - Vendor Production Portal Service Worker
 * Version: 1.0.0
 */

const CACHE_NAME = 'falhen-vendor-v1';
const STATIC_ASSETS = [
    '/assets/img/icons/logo.png',
    '/assets/img/icons/favicon.png',
    '/assets/img/icons/pwa-192.png',
    '/assets/img/icons/pwa-512.png',
    '/assets/img/icons/pwa-maskable.png',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'
];

// Install: Cache Static App Shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[PWA SW] Pre-caching warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate: Clean old caches and claim clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Network-first for vendor app pages, Stale-while-revalidate for static assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests within our scope or static CDN
    if (request.method !== 'GET') {
        return;
    }

    // Static Assets & Images: Cache-first / Stale-While-Revalidate
    if (
        url.pathname.startsWith('/assets/') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com') ||
        url.hostname.includes('cdnjs.cloudflare.com')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Update cache in background
                    fetch(request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            caches.open(CACHE_NAME).then((cache) => cache.put(request, networkResponse));
                        }
                    }).catch(() => {});
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                        return networkResponse;
                    }
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Vendor Portal HTML / Dynamic Content: Network-First with Cache Fallback
    if (url.pathname.startsWith('/vendor/')) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // If no cached version for this specific URL, try base portal
                    const baseCached = await caches.match('/vendor/index.php');
                    if (baseCached) {
                        return baseCached;
                    }
                    return new Response(
                        `<!DOCTYPE html>
                        <html lang="en">
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Offline &mdash; Falhen Vendor Portal</title>
                            <style>
                                body { font-family: -apple-system, sans-serif; background: #0f172a; color: #ffffff; text-align: center; padding: 60px 20px; }
                                h1 { color: #dc2626; font-size: 1.6rem; }
                                p { color: #94a3b8; max-width: 400px; margin: 12px auto 24px; line-height: 1.5; }
                                button { background: #dc2626; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; }
                            </style>
                        </head>
                        <body>
                            <h1>📡 Offline Mode</h1>
                            <p>You appear to be offline or on location without cellular reception. Previously viewed call sheets and schedule data are available when you reconnect.</p>
                            <button onclick="window.location.reload()">Retry Connection</button>
                        </body>
                        </html>`,
                        { headers: { 'Content-Type': 'text/html' } }
                    );
                })
        );
    }
});
