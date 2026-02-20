/**
 * Wasetzon Service Worker
 * Strategy: Cache-first for static assets, Network-first for HTML/API
 */

const CACHE_NAME = 'wasetzon-v1';
const STATIC_CACHE = 'wasetzon-static-v1';
const OFFLINE_URL = '/offline';

// Static assets to pre-cache on install
const PRECACHE_ASSETS = [
    '/',
    '/offline',
    '/manifest.json',
];

// ─── Install ──────────────────────────────────────────────────────────────────

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[SW] Pre-cache failed for some assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// ─── Activate ─────────────────────────────────────────────────────────────────

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// ─── Fetch ────────────────────────────────────────────────────────────────────

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET and cross-origin requests
    if (request.method !== 'GET' || url.origin !== location.origin) {
        return;
    }

    // Skip Livewire update requests — always network
    if (url.pathname.startsWith('/livewire/')) {
        return;
    }

    // Skip admin routes — always network
    if (url.pathname.startsWith('/admin')) {
        return;
    }

    // Static assets (build/, fonts, icons, images) → Cache-first
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/css/') ||
        url.pathname.startsWith('/js/') ||
        /\.(woff2?|ttf|otf|eot|svg|png|jpg|jpeg|gif|webp|ico)$/.test(url.pathname)
    ) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // HTML pages → Network-first with offline fallback
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOfflineFallback(request));
        return;
    }

    // Everything else → Network-first
    event.respondWith(networkFirst(request));
});

// ─── Strategies ───────────────────────────────────────────────────────────────

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 408, statusText: 'Offline' });
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        return cached || new Response('', { status: 408, statusText: 'Offline' });
    }
}

async function networkFirstWithOfflineFallback(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        // Show offline page
        const offlinePage = await caches.match(OFFLINE_URL);
        return offlinePage || new Response(
            `<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>غير متصل — Wasetzon</title>
<style>
body{font-family:system-ui,sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f9fafb;color:#374151;text-align:center;padding:1rem}
h1{font-size:1.5rem;margin-bottom:.5rem}p{color:#6b7280;margin-bottom:1.5rem}
a{background:#f97316;color:#fff;padding:.625rem 1.25rem;border-radius:.5rem;text-decoration:none;font-weight:500}
</style></head>
<body>
<h1>🔌 لا يوجد اتصال بالإنترنت</h1>
<p>تحقق من اتصالك وحاول مرة أخرى.</p>
<a href="/">العودة للرئيسية</a>
</body></html>`,
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

// ─── Push notifications (placeholder) ─────────────────────────────────────────

self.addEventListener('push', (event) => {
    const data = event.data?.json() ?? {};
    event.waitUntil(
        self.registration.showNotification(data.title ?? 'Wasetzon', {
            body: data.body ?? '',
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-72x72.png',
            dir: 'rtl',
            lang: 'ar',
            data: { url: data.url ?? '/' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data?.url ?? '/')
    );
});
