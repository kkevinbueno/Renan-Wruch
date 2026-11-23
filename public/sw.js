/*
 * Service worker do Wruch Personal.
 * - Guarda em cache SOMENTE arquivos estáticos públicos (CSS, JS, ícones) e a página offline.
 * - Páginas, API e /media (fotos, vídeos, dados de saúde) NUNCA são armazenados em cache.
 */
const VERSION = 'wp-2.0.0';
const STATIC_CACHE = `static-${VERSION}`;
const PRECACHE = ['/offline', '/assets/css/app.css', '/assets/js/app.js', '/assets/icons/sprite.svg', '/assets/icons/icon-192.png', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).catch(() => null));
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k)));
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Arquivos estáticos versionados: cache primeiro, atualizando em segundo plano.
  if (url.pathname.startsWith('/assets/')) {
    event.respondWith((async () => {
      const cache = await caches.open(STATIC_CACHE);
      const cached = await cache.match(req);
      const network = fetch(req).then((res) => {
        if (res.ok && res.type === 'basic') cache.put(req, res.clone());
        return res;
      }).catch(() => cached);
      return cached || network;
    })());
    return;
  }

  // Navegação: sempre rede (conteúdo privado). Sem conexão, mostra a página offline.
  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(async () => (await caches.match('/offline')) || new Response('Sem conexão', { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } })));
  }
  // Demais requisições (API, /media): direto da rede, sem cache.
});
