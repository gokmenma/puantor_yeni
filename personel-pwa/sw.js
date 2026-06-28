const CACHE_NAME = 'puantor-personel-v19';
const ASSETS = [
  './manifest.json',
  './css/app.css',
  './js/app.js',
  './static/Logo-ai.svg',
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.allSettled(ASSETS.map(url => cache.add(url).catch(() => {})));
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.url.includes('.php') || event.request.url.endsWith('/') || event.request.url.includes('?route=')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
  } else {
    event.respondWith(
      caches.match(event.request).then((response) => {
        return response || fetch(event.request);
      })
    );
  }
});

self.addEventListener('push', (event) => {
  let data = { title: 'Puantor', body: 'Yeni bildirim.' };
  if (event.data) {
    try { data = event.data.json(); } catch (e) { data.body = event.data.text(); }
  }
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body:    data.body,
      icon:    './static/Logo-ai.svg',
      badge:   './static/Logo-ai.svg',
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
