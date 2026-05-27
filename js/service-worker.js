const CACHE_NAME = 'site-cache-v5';
const urlsToCache = [
    '/hidrantes/',
    '/hidrantes/index.php',
    '/hidrantes/css/style.css',
    '/hidrantes/js/indexedDB.js',
    '/hidrantes/inspecao.php',
    '/hidrantes/sucesso.php',
    '/hidrantes/gerenciar_inspecoes.php',
    '/hidrantes/dashboard.php',
    '/hidrantes/login.php'
];

// Instalando o Service Worker e armazenando os recursos críticos no cache
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Recursos essenciais armazenados no cache local');
                return cache.addAll(urlsToCache).catch((error) => {
                    console.error('Erro de pré-cacheamento no Service Worker:', error);
                });
            })
    );
    self.skipWaiting();
});

// Intercepta as requisições HTTP e serve do cache se offline, ou atualiza o cache dinamicamente
self.addEventListener('fetch', event => {
    // Ignora requisições de APIs ou métodos POST para evitar cachear sincronizações
    if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                // Retorna o cache se existir, e inicia a busca na rede em background para atualizar
                const networkFetch = fetch(event.request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        return caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, networkResponse.clone());
                            return networkResponse;
                        });
                    }
                    return networkResponse;
                }).catch(err => {
                    console.log('Erro de rede ao buscar:', event.request.url, 'Servindo do cache.');
                });

                return cachedResponse || networkFetch;
            })
    );
});

// Ativação e limpeza de caches obsoletos de versões anteriores
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        console.log('Deletando cache antigo obsoletado:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});
