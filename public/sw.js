const CACHE = 'warehouse-v2';

// Static assets to pre-cache
const PRE_CACHE = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(PRE_CACHE).catch(() => {}))
    );
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    const req = e.request;

    // POST / non-GET → bypass
    if (req.method !== 'GET') return;

    // AJAX requests → bypass (dynamic data must not be cached)
    if (req.headers.get('X-Requested-With') === 'XMLHttpRequest') return;

    const url = new URL(req.url);

    // CDN assets → cache-first
    if (url.hostname !== self.location.hostname) {
        e.respondWith(
            caches.match(req).then(cached => cached || fetch(req).then(res => {
                if (res.ok) {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(req, clone));
                }
                return res;
            }).catch(() => new Response('', { status: 503 })))
        );
        return;
    }

    // App pages (HTML) → network-first (always fresh data)
    if (req.headers.get('accept')?.includes('text/html')) {
        e.respondWith(
            fetch(req).catch(() => caches.match(req))
        );
        return;
    }

    // Local static files only → cache-first
    if (/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf)(\?|$)/.test(url.pathname)) {
        e.respondWith(
            caches.match(req).then(cached => cached || fetch(req).then(res => {
                if (res.ok) {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(req, clone));
                }
                return res;
            }).catch(() => new Response('', { status: 503 })))
        );
        return;
    }

    // Everything else (app routes, API) → bypass
});
