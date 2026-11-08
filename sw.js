/**
 * Falhen Media — Lightweight Offline & Slow-Network Service Worker
 * Caches core landing shell, responsive Hero backup poster, and direct stream clips
 * for instant repeat loads even on slow 2G/3G or offline networks.
 */

const CACHE_VERSION = 'falhen-shell-v1';
const CORE_ASSETS = [
  '/',
  '/assets/img/hero.jpg',
  '/assets/img/icons/logo.png',
  '/assets/img/icons/favicon.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION)
      .then((cache) => cache.addAll(CORE_ASSETS).catch(() => {}))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => k.startsWith('falhen-shell-') && k !== CACHE_VERSION)
            .map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// Allow main.js to warm specific dynamic Hero assets (Cloudinary poster / MP4)
self.addEventListener('message', (event) => {
  const data = event.data || {};
  if (data.type === 'WARM_HERO_ASSETS' && Array.isArray(data.urls)) {
    event.waitUntil(
      caches.open(CACHE_VERSION).then((cache) =>
        Promise.all(
          data.urls.filter(Boolean).map((url) =>
            fetch(url, { mode: 'cors', credentials: 'omit' })
              .then((res) => {
                if (res && res.ok) return cache.put(url, res);
              })
              .catch(() => {})
          )
        )
      )
    );
  }
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Never intercept admin, deploy, cron, client portal, or dynamic API queries
  if (
    url.hostname.startsWith('client.') ||
    url.pathname.startsWith('/admin') ||
    url.pathname.startsWith('/deploy') ||
    url.pathname.startsWith('/cron') ||
    url.pathname.includes('client-portal') ||
    url.searchParams.has('ajax') ||
    url.searchParams.has('action') ||
    url.searchParams.has('key') ||
    url.searchParams.has('token')
  ) {
    return;
  }

  // Never intercept third-party video embeds or analytics
  if (
    url.hostname.includes('youtube') ||
    url.hostname.includes('ytimg') ||
    url.hostname.includes('vimeo') ||
    url.hostname.includes('googletagmanager') ||
    url.hostname.includes('google-analytics')
  ) {
    return;
  }

  // 1. Static local assets & Cloudinary Hero images/videos: Stale-While-Revalidate
  const isStaticAsset =
    (url.origin === self.location.origin && url.pathname.startsWith('/assets/')) ||
    (url.hostname === 'res.cloudinary.com' && (url.pathname.includes('/image/upload/') || url.pathname.includes('/video/upload/')));

  if (isStaticAsset && !req.headers.has('range')) {
    event.respondWith(
      caches.open(CACHE_VERSION).then(async (cache) => {
        const cached = await cache.match(req);
        const networkPromise = fetch(req)
          .then((res) => {
            if (res && res.status === 200) {
              cache.put(req, res.clone()).catch(() => {});
            }
            return res;
          })
          .catch(() => cached);

        return cached || networkPromise;
      })
    );
    return;
  }

  // 2. Landing page HTML navigation ('/' or '/index.php'): Network-first with 3.5s slow-network fallback
  if (
    req.mode === 'navigate' &&
    url.origin === self.location.origin &&
    (url.pathname === '/' || url.pathname === '/index.php')
  ) {
    event.respondWith(
      caches.open(CACHE_VERSION).then(async (cache) => {
        const timeoutPromise = new Promise((_, reject) =>
          setTimeout(() => reject(new Error('slow-network-timeout')), 3500)
        );
        try {
          const fresh = await Promise.race([fetch(req), timeoutPromise]);
          if (fresh && fresh.status === 200) {
            cache.put('/', fresh.clone()).catch(() => {});
          }
          return fresh;
        } catch (_) {
          const cachedShell = (await cache.match(req)) || (await cache.match('/'));
          if (cachedShell) return cachedShell;
          return fetch(req);
        }
      })
    );
  }
});
