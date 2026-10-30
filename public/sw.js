// Service worker mínimo para permitir instalar o app na tela inicial (T-25).
// Não guarda dados em cache: as demandas precisam estar sempre atualizadas.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});
