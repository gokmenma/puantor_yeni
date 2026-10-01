self.addEventListener('install', (event) => {
    event.waitUntil(
      caches.open('v3').then((cache) => {
        return cache.addAll([
          '/',
          '/anasayfa',
          '/dist/css/style.css',
          '/script.js',
          '/icon-168x168.png',
          '/icon-192x192.png',
          '/icon-512x512.png',
        ]).catch((error) => {
          console.error('Cache addAll failed:', error);
        });
      })
    );
  });
  