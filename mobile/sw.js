const CACHE_NAME = 'puantor-v3';
const ASSETS_TO_CACHE = [
  'index.php',
  'manifest.json',
  'css/mobile.css',
  'https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css',
  'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
  'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js'
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) return caches.delete(cache);
        })
      );
    })
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.url.includes('download.php')) return;
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});

self.addEventListener('push', (event) => {
  let data = { title: 'Puantor', body: 'Yeni bildirim.' };
  if (event.data) {
    try { data = event.data.json(); } catch (e) { data.body = event.data.text(); }
  }
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body:    data.body,
      icon:    '../static/png/icon-192x192.png',
      badge:   '../static/png/icon-192x192.png',
      vibrate: [200, 100, 200],
      data:    data.data || {},
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const relUrl = event.notification.data?.url || '';
  const targetUrl = relUrl ? (self.registration.scope + relUrl) : self.registration.scope;
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const client of list) {
        if (client.url.startsWith(self.registration.scope) && 'navigate' in client) {
          return client.navigate(targetUrl).then(() => client.focus());
        }
      }
      return clients.openWindow(targetUrl);
    })
  );
});
