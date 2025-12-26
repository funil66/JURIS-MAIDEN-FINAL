// ============================================
// LogísticaJus - Service Worker para PWA
// ============================================

const CACHE_NAME = 'juris-maiden-v1';
const OFFLINE_URL = '/offline.html';

// Arquivos para cache inicial
const PRECACHE_ASSETS = [
    '/funil',
    '/offline.html',
    '/manifest.json',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Pre-caching assets');
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
    self.skipWaiting();
});

// Ativação e limpeza de caches antigos
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Estratégia de fetch: Network First com fallback para cache
self.addEventListener('fetch', (event) => {
    // Ignorar requisições não-GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Ignorar requisições de API e Livewire (sempre online)
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/livewire') || 
        url.pathname.startsWith('/api') ||
        url.pathname.includes('filament')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Se a resposta for válida, armazena no cache
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Se offline, tenta buscar do cache
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Se não tiver no cache, mostra página offline
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                    return new Response('Offline', { status: 503 });
                });
            })
    );
});

// Sincronização em background (quando voltar online)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-data') {
        console.log('[SW] Sincronizando dados...');
        // Aqui pode adicionar lógica para sincronizar dados pendentes
    }
});

// Push notifications
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body || 'Nova notificação',
            icon: '/images/icons/icon-192x192.png',
            badge: '/images/icons/icon-72x72.png',
            vibrate: [100, 50, 100],
            data: {
                url: data.url || '/funil'
            },
            actions: [
                { action: 'open', title: 'Abrir' },
                { action: 'close', title: 'Fechar' }
            ]
        };
        event.waitUntil(
            self.registration.showNotification(data.title || 'LogísticaJus', options)
        );
    }
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.action === 'open' || !event.action) {
        const url = event.notification.data?.url || '/funil';
        event.waitUntil(
            clients.openWindow(url)
        );
    }
});
