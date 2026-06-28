const CACHE_NAME = 'puantor-personel-v17';
const ASSETS = [
  './',
  './index.php',
  './login.php',
  './js/app.js',
  './css/app.css',
  '../dist/js/pull-to-refresh.js',
  './manifest.json',
  './resources/Logo-ai.svg',
  './static/Logo-ai.svg',
  './index.html'
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
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
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      if (list.length > 0) return list[0].focus();
      return clients.openWindow('./');
    })
  );
});
