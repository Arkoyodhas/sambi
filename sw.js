/* Service Worker PWA - cache aman untuk halaman publik */
const CACHE = 'desa-pwa-' + (self.location.search || 'v7-face');
const OFFLINE_HTML = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center;padding:24px"><div><h2>Anda sedang offline</h2><p>Periksa koneksi internet lalu muat ulang halaman.</p></div></body></html>';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

function isSensitive(url) {
  const path = url.pathname.toLowerCase();
  if (path.includes('/admin') || path.includes('/api/') || path.includes('/uploads/protected')) return true;
  if (path.includes('/layanan/cek') || path.includes('/pengaduan/cek')) return true;
  for (const key of ['tiket', 'ticket', 'token', 'nonce', 'reset']) {
    if (url.searchParams.has(key)) return true;
  }
  return false;
}

function cacheable(response) {
  if (!response || !response.ok || response.type !== 'basic') return false;
  const cc = String(response.headers.get('Cache-Control') || '').toLowerCase();
  return !cc.includes('no-store') && !cc.includes('private');
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (isSensitive(url)) {
    event.respondWith(fetch(request, { cache: 'no-store' }));
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (cacheable(response)) {
            const copy = response.clone();
            event.waitUntil(caches.open(CACHE).then((cache) => cache.put(request, copy)));
          }
          return response;
        })
        .catch(async () => (await caches.match(request)) || new Response(OFFLINE_HTML, {
          headers: {
            'Content-Type': 'text/html; charset=utf-8',
            'Cache-Control': 'no-store'
          }
        }))
    );
    return;
  }

  // Aset berversi (?v=) & foto upload (nama acak) tidak berubah: cache-first tanpa unduh ulang.
  const immutable = (url.pathname.startsWith('/assets/') && url.searchParams.has('v'))
    || /^\/uploads\/(?!protected\/).+\.(?:webp|jpe?g|png|gif|svg|avif)$/i.test(url.pathname)
    || /\.woff2$/i.test(url.pathname);
  if (immutable) {
    event.respondWith(caches.match(request).then((cached) => cached || fetch(request).then((response) => {
      if (cacheable(response)) {
        const copy = response.clone();
        event.waitUntil(caches.open(CACHE).then((cache) => cache.put(request, copy)));
      }
      return response;
    })));
    return;
  }

  // Aset statis lain: stale-while-revalidate. Respons privat tidak pernah disimpan.
  event.respondWith(
    caches.match(request).then((cached) => {
      const network = fetch(request).then((response) => {
        if (cacheable(response)) {
          const copy = response.clone();
          event.waitUntil(caches.open(CACHE).then((cache) => cache.put(request, copy)));
        }
        return response;
      }).catch(() => cached);
      return cached || network;
    })
  );
});

/* Push: pesan dikirim tanpa payload; ambil isi notifikasi terbaru dari server */
self.addEventListener('push', (event) => {
  event.waitUntil(
    fetch(self.registration.scope + 'api/push-latest', { cache: 'no-store' })
      .then((response) => response.json())
      .catch(() => null)
      .then((notification) => self.registration.showNotification(
        (notification && notification.title) || 'Informasi Desa',
        {
          body: (notification && notification.body) || 'Ada informasi baru dari desa.',
          icon: (notification && notification.icon) || undefined,
          badge: (notification && notification.icon) || undefined,
          data: { url: (notification && notification.url) || self.registration.scope }
        }
      ))
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || self.registration.scope;
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const client of list) {
        if (client.url === url && 'focus' in client) return client.focus();
      }
      return clients.openWindow(url);
    })
  );
});
