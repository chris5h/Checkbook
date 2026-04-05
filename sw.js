/* =============================================
   Checkbook — Service Worker
   ============================================= */

const CACHE = 'checkbook-v1';

// Static assets to pre-cache on install
const PRECACHE = [
  'cb.css',
  'cb.js',
  'icon.png',
  'manifest.json',
];

self.addEventListener('install', e => {
  self.skipWaiting();
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(PRECACHE))
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => k !== CACHE).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  if (!e.request.url.startsWith('http')) return;

  const url = new URL(e.request.url);
  const isStaticAsset = /\.(css|js|png|ico|jpg|jpeg|gif|svg|woff2?|ttf|eot|json)(\?.*)?$/.test(url.pathname);

  if (isStaticAsset) {
    // Cache-first: serve from cache, fetch and cache on miss
    e.respondWith(
      caches.match(e.request).then(cached => {
        if (cached) return cached;
        return fetch(e.request).then(res => {
          if (res.ok) {
            const clone = res.clone();
            caches.open(CACHE).then(c => c.put(e.request, clone));
          }
          return res;
        });
      })
    );
  }
  // PHP pages and API calls go through normally (no SW interception)
});
